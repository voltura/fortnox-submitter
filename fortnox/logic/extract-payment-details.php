<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

header('Content-Type: application/json');

if (empty($_FILES['payment_pdf'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a PDF file.']);
    exit;
}

$file = $_FILES['payment_pdf'];
$max_size = 25 * 1024 * 1024;

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to upload PDF.']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['status' => 'error', 'message' => 'File is too large. Max allowed size is 25 MB.']);
    exit;
}

$file_data = file_get_contents($file['tmp_name']);

if ($file_data === false || substr($file_data, 0, 4) !== '%PDF') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid PDF file.']);
    exit;
}

$text = extract_pdf_text_from_file($file['tmp_name'], $file_data);
$details = extract_payment_details($text);

echo json_encode([
    'status' => 'success',
    'message' => 'Payment details extracted.',
    'details' => $details,
    'text_found' => trim($text) !== '',
    'text_preview' => mb_safe_substr(clean_result_text($text), 0, 5000)
]);
exit;

function extract_pdf_text_from_file($pdf_path, $pdf_data)
{
    $vendor_autoload = __DIR__ . '/../vendor/autoload.php';

    if (is_file($vendor_autoload)) {
        require_once $vendor_autoload;

        if (class_exists('Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($pdf_path);

                return clean_result_text($pdf->getText());
            } catch (Throwable $exception) {
            }
        }
    }

    return extract_pdf_text_fallback($pdf_data);
}

function extract_pdf_text_fallback($pdf_data)
{
    $text_parts = [];

    if (preg_match_all('/(<<[\s\S]*?>>)\s*stream\r?\n?([\s\S]*?)\r?\n?endstream/', $pdf_data, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $decoded = decode_pdf_stream(trim_pdf_stream_newlines($match[2]), $match[1]);

            if ($decoded === null || $decoded === '') {
                continue;
            }

            $stream_text = extract_text_from_pdf_content($decoded);

            if ($stream_text !== '') {
                $text_parts[] = $stream_text;
            }
        }
    }

    return clean_result_text(implode("\n", $text_parts));
}

function trim_pdf_stream_newlines($stream)
{
    if (substr($stream, 0, 2) === "\r\n") {
        $stream = substr($stream, 2);
    } elseif (substr($stream, 0, 1) === "\n" || substr($stream, 0, 1) === "\r") {
        $stream = substr($stream, 1);
    }

    if (substr($stream, -2) === "\r\n") {
        $stream = substr($stream, 0, -2);
    } elseif (substr($stream, -1) === "\n" || substr($stream, -1) === "\r") {
        $stream = substr($stream, 0, -1);
    }

    return $stream;
}

function decode_pdf_stream($stream, $dictionary)
{
    $decoded = $stream;

    if (stripos($dictionary, 'ASCIIHexDecode') !== false) {
        $decoded = decode_ascii_hex($decoded);
    }

    if (stripos($dictionary, 'FlateDecode') !== false) {
        $inflated = @gzuncompress($decoded);

        if ($inflated === false) {
            $inflated = @gzdecode($decoded);
        }

        if ($inflated === false && strlen($decoded) > 2) {
            $inflated = @gzinflate(substr($decoded, 2));
        }

        if ($inflated === false) {
            return null;
        }

        $decoded = $inflated;
    } elseif (stripos($dictionary, 'LZWDecode') !== false || stripos($dictionary, 'DCTDecode') !== false) {
        return null;
    }

    return $decoded;
}

function decode_ascii_hex($value)
{
    $hex = preg_replace('/[^0-9A-Fa-f]/', '', $value);

    if (strlen($hex) % 2 === 1) {
        $hex .= '0';
    }

    $decoded = @hex2bin($hex);

    return $decoded === false ? '' : $decoded;
}

function extract_text_from_pdf_content($content)
{
    $text = '';

    if (preg_match_all('/BT([\s\S]*?)ET/', $content, $blocks)) {
        foreach ($blocks[1] as $block) {
            $text .= tokenize_pdf_text_block($block) . "\n";
        }
    } else {
        $text = tokenize_pdf_text_block($content);
    }

    return $text;
}

function tokenize_pdf_text_block($block)
{
    $text = '';
    $length = strlen($block);

    for ($i = 0; $i < $length; $i++) {
        $char = $block[$i];

        if ($char === '(') {
            $text .= decode_text_bytes(read_pdf_literal_string($block, $i)) . ' ';
            continue;
        }

        if ($char === '<' && ($i + 1 >= $length || $block[$i + 1] !== '<')) {
            $text .= decode_text_bytes(read_pdf_hex_string($block, $i)) . ' ';
            continue;
        }

        if ($char === "'" || substr($block, $i, 2) === 'T*' || substr($block, $i, 2) === 'Td' || substr($block, $i, 2) === 'TD') {
            $text .= "\n";
        }
    }

    return $text;
}

function read_pdf_literal_string($content, &$index)
{
    $index++;
    $depth = 1;
    $bytes = '';
    $length = strlen($content);

    while ($index < $length && $depth > 0) {
        $char = $content[$index];

        if ($char === '\\') {
            $index++;

            if ($index >= $length) {
                break;
            }

            $escaped = $content[$index];

            if ($escaped === "\r" || $escaped === "\n") {
                if ($escaped === "\r" && $index + 1 < $length && $content[$index + 1] === "\n") {
                    $index++;
                }
            } elseif ($escaped >= '0' && $escaped <= '7') {
                $octal = $escaped;

                for ($j = 0; $j < 2 && $index + 1 < $length && $content[$index + 1] >= '0' && $content[$index + 1] <= '7'; $j++) {
                    $index++;
                    $octal .= $content[$index];
                }

                $bytes .= chr(octdec($octal));
            } else {
                $bytes .= decode_pdf_escape($escaped);
            }
        } elseif ($char === '(') {
            $depth++;
            $bytes .= $char;
        } elseif ($char === ')') {
            $depth--;

            if ($depth > 0) {
                $bytes .= $char;
            }
        } else {
            $bytes .= $char;
        }

        $index++;
    }

    return $bytes;
}

function decode_pdf_escape($escaped)
{
    switch ($escaped) {
        case 'n':
            return "\n";
        case 'r':
            return "\r";
        case 't':
            return "\t";
        case 'b':
            return "\b";
        case 'f':
            return "\f";
        default:
            return $escaped;
    }
}

function read_pdf_hex_string($content, &$index)
{
    $index++;
    $hex = '';
    $length = strlen($content);

    while ($index < $length && $content[$index] !== '>') {
        $hex .= $content[$index];
        $index++;
    }

    return decode_ascii_hex($hex);
}

function decode_text_bytes($bytes)
{
    if ($bytes === '') {
        return '';
    }

    if (substr($bytes, 0, 2) === "\xFE\xFF") {
        return decode_utf16_be(substr($bytes, 2));
    }

    if (looks_like_utf16_be($bytes)) {
        return decode_utf16_be($bytes);
    }

    if (preg_match('//u', $bytes)) {
        return $bytes;
    }

    return cp1252_to_utf8($bytes);
}

function looks_like_utf16_be($bytes)
{
    $length = strlen($bytes);

    if ($length < 4 || $length % 2 !== 0) {
        return false;
    }

    $zero_high_bytes = 0;
    $pairs = (int)($length / 2);

    for ($i = 0; $i < $length; $i += 2) {
        if ($bytes[$i] === "\x00") {
            $zero_high_bytes++;
        }
    }

    return $zero_high_bytes / $pairs > 0.6;
}

function decode_utf16_be($bytes)
{
    $result = '';
    $length = strlen($bytes);

    for ($i = 0; $i + 1 < $length; $i += 2) {
        $code = (ord($bytes[$i]) << 8) + ord($bytes[$i + 1]);

        if ($code >= 0xD800 && $code <= 0xDBFF && $i + 3 < $length) {
            $next = (ord($bytes[$i + 2]) << 8) + ord($bytes[$i + 3]);

            if ($next >= 0xDC00 && $next <= 0xDFFF) {
                $code = 0x10000 + (($code - 0xD800) << 10) + ($next - 0xDC00);
                $i += 2;
            }
        }

        $result .= codepoint_to_utf8($code);
    }

    return $result;
}

function cp1252_to_utf8($bytes)
{
    $map = [
        0x80 => 0x20AC, 0x82 => 0x201A, 0x83 => 0x0192, 0x84 => 0x201E,
        0x85 => 0x2026, 0x86 => 0x2020, 0x87 => 0x2021, 0x88 => 0x02C6,
        0x89 => 0x2030, 0x8A => 0x0160, 0x8B => 0x2039, 0x8C => 0x0152,
        0x8E => 0x017D, 0x91 => 0x2018, 0x92 => 0x2019, 0x93 => 0x201C,
        0x94 => 0x201D, 0x95 => 0x2022, 0x96 => 0x2013, 0x97 => 0x2014,
        0x98 => 0x02DC, 0x99 => 0x2122, 0x9A => 0x0161, 0x9B => 0x203A,
        0x9C => 0x0153, 0x9E => 0x017E, 0x9F => 0x0178
    ];

    $result = '';
    $length = strlen($bytes);

    for ($i = 0; $i < $length; $i++) {
        $byte = ord($bytes[$i]);
        $codepoint = $map[$byte] ?? $byte;
        $result .= codepoint_to_utf8($codepoint);
    }

    return $result;
}

function codepoint_to_utf8($codepoint)
{
    if ($codepoint <= 0x7F) {
        return chr($codepoint);
    }

    if ($codepoint <= 0x7FF) {
        return chr(0xC0 | ($codepoint >> 6)) . chr(0x80 | ($codepoint & 0x3F));
    }

    if ($codepoint <= 0xFFFF) {
        return chr(0xE0 | ($codepoint >> 12)) . chr(0x80 | (($codepoint >> 6) & 0x3F)) . chr(0x80 | ($codepoint & 0x3F));
    }

    return chr(0xF0 | ($codepoint >> 18)) . chr(0x80 | (($codepoint >> 12) & 0x3F)) . chr(0x80 | (($codepoint >> 6) & 0x3F)) . chr(0x80 | ($codepoint & 0x3F));
}

function clean_result_text($text)
{
    $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
    $text = preg_replace('/ *\R+ */u', "\n", $text);
    $text = preg_replace('/(?<!^)(?=(?:OCR|Bankgiro|Plusgiro|IBAN|Att betala|Belopp|Summa|Totalt)\b)/iu', "\n", $text);

    return trim($text);
}

function mb_safe_substr($text, $start, $length)
{
    if (function_exists('mb_substr')) {
        return mb_substr($text, $start, $length);
    }

    return substr($text, $start, $length);
}

function extract_payment_details($text)
{
    return [
        'amount' => extract_amount($text),
        'ocr' => extract_ocr_number($text),
        'account' => extract_account($text)
    ];
}

function extract_amount($text)
{
    $decimal_amount_pattern = '(?:\d{1,3}(?:[ .]\d{3})+|\d+)[,.]\d{2}';
    $amount_pattern = '(?:\d{1,3}(?:[ .]\d{3})+|\d+)(?:[,.]\d{2})?';
    $label_pattern = '(?:summa\s+att\s+betala|att\s+betala|belopp\s+att\s+betala|fakturabelopp|totalt\s+belopp|total(?:t)?|belopp)';
    $candidates = [];

    if (preg_match_all('/oss\s*tillhanda[^\d]*(?:\d{4}-\d{2}-\d{2})?[^\d]{0,30}(' . $decimal_amount_pattern . ')\s*(?:kr|sek)\b/iu', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            add_amount_candidate($candidates, $match[1], $match[0], 120);
        }
    }

    if (preg_match_all('/\b\d{4}-\d{2}-\d{2}\s+(' . $decimal_amount_pattern . ')\s*(?:kr|sek)\b/iu', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            add_amount_candidate($candidates, $match[1], $match[0], 95);
        }
    }

    if (preg_match_all('/(' . $label_pattern . ')[^\d]{0,80}(' . $amount_pattern . ')(?:\s*(?:kr|sek))?/iu', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            add_amount_candidate($candidates, $match[2], $match[0], 80);
        }
    }

    if (preg_match_all('/(' . $amount_pattern . ')\s*(?:kr|sek)\b/iu', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            add_amount_candidate($candidates, $match[1], $match[0], 35);
        }
    }

    if (empty($candidates)) {
        return empty_detail('Amount');
    }

    usort($candidates, function ($a, $b) {
        if ($a['score'] === $b['score']) {
            return $b['numeric'] <=> $a['numeric'];
        }

        return $b['score'] <=> $a['score'];
    });

    return [
        'label' => 'Amount',
        'value' => format_swedish_amount($candidates[0]['numeric']),
        'raw' => $candidates[0]['raw'],
        'confidence' => confidence_from_score($candidates[0]['score'])
    ];
}

function add_amount_candidate(&$candidates, $raw_amount, $context, $score)
{
    $digits = preg_replace('/\D+/', '', $raw_amount);
    $has_money_shape = preg_match('/[,.]\d{2}$/u', $raw_amount)
        || preg_match('/\d[ .]\d{3}/u', $raw_amount)
        || preg_match('/\b(?:kr|sek)\b/iu', $context);

    if (!$has_money_shape && strlen($digits) > 6) {
        return;
    }

    $numeric = parse_amount($raw_amount);

    if ($numeric === null || $numeric <= 0) {
        return;
    }

    if (preg_match('/\b(?:moms|vat|skatt|exkl|frakt)\b/iu', $context)) {
        $score -= 25;
    }

    if (preg_match('/\b(?:att\s+betala|summa\s+att\s+betala|fakturabelopp)\b/iu', $context)) {
        $score += 20;
    }

    $candidates[] = [
        'raw' => trim($raw_amount),
        'numeric' => $numeric,
        'score' => $score
    ];
}

function parse_amount($amount)
{
    $value = str_replace(["\xc2\xa0", ' '], '', trim($amount));
    $last_comma = strrpos($value, ',');
    $last_dot = strrpos($value, '.');

    if ($last_comma !== false && $last_dot !== false) {
        if ($last_comma > $last_dot) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }
    } elseif ($last_comma !== false) {
        $value = str_replace(',', '.', $value);
    } elseif ($last_dot !== false && strlen($value) - $last_dot - 1 !== 2) {
        $value = str_replace('.', '', $value);
    }

    return is_numeric($value) ? (float)$value : null;
}

function format_swedish_amount($amount)
{
    return number_format($amount, 2, ',', ' ');
}

function extract_ocr_number($text)
{
    $candidates = [];

    if (preg_match_all('/\b(?:ocr(?:[\s-]*(?:nr|nummer|no|reference))?|betalningsreferens|referensnummer)\b[^\d]{0,60}([0-9][0-9\s-]{2,30}[0-9])/iu', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            add_ocr_candidate($candidates, $match[1], 85);
        }
    }

    if (empty($candidates)) {
        return empty_detail('OCR number');
    }

    usort($candidates, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    return [
        'label' => 'OCR number',
        'value' => $candidates[0]['value'],
        'raw' => $candidates[0]['raw'],
        'confidence' => confidence_from_score($candidates[0]['score'])
    ];
}

function add_ocr_candidate(&$candidates, $raw, $score)
{
    $digits = preg_replace('/\D+/', '', $raw);
    $length = strlen($digits);

    if ($length < 2 || $length > 25) {
        return;
    }

    if (luhn_is_valid($digits)) {
        $score += 10;
    }

    $candidates[] = [
        'value' => $digits,
        'raw' => trim($raw),
        'score' => $score
    ];
}

function luhn_is_valid($digits)
{
    $sum = 0;
    $alternate = false;

    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $number = (int)$digits[$i];

        if ($alternate) {
            $number *= 2;

            if ($number > 9) {
                $number -= 9;
            }
        }

        $sum += $number;
        $alternate = !$alternate;
    }

    return $sum % 10 === 0;
}

function extract_account($text)
{
    $patterns = [
        ['type' => 'Bankgiro', 'pattern' => '/\b(?:bankgiro(?:t)?|bg)\b[^\d]{0,40}(\d{2,4}[\s-]?\d{3,4})/iu'],
        ['type' => 'Plusgiro', 'pattern' => '/\b(?:plusgiro(?:konto)?|pg)\b[^\d]{0,40}([0-9][0-9\s-]{3,15}[0-9])/iu'],
        ['type' => 'IBAN', 'pattern' => '/\b(SE\d{2}(?:\s?\d{4}){5}\s?\d{4})\b/iu'],
        ['type' => 'Account', 'pattern' => '/\b(?:konto|kontonummer|bankkonto)\b[^\d]{0,40}([0-9][0-9\s-]{5,25}[0-9])/iu']
    ];

    foreach ($patterns as $entry) {
        if (preg_match($entry['pattern'], $text, $match)) {
            $value = normalize_account_value($match[1], $entry['type']);

            return [
                'label' => $entry['type'],
                'value' => $value,
                'raw' => trim($match[1]),
                'confidence' => $entry['type'] === 'Account' ? 0.65 : 0.9
            ];
        }
    }

    return empty_detail('Account');
}

function normalize_account_value($value, $type)
{
    if ($type === 'IBAN') {
        return strtoupper(preg_replace('/\s+/', '', $value));
    }

    $digits = preg_replace('/\D+/', '', $value);

    if ($type === 'Bankgiro' && strlen($digits) > 4) {
        return substr($digits, 0, -4) . '-' . substr($digits, -4);
    }

    return trim(preg_replace('/\s+/', ' ', $value));
}

function empty_detail($label)
{
    return [
        'label' => $label,
        'value' => '',
        'raw' => '',
        'confidence' => 0
    ];
}

function confidence_from_score($score)
{
    return min(0.99, max(0.2, $score / 100));
}
