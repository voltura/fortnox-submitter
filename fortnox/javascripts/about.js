document.addEventListener("DOMContentLoaded", function() {
    const user = "support";
    const domain = "voltura.se";
    const email = `${user}@${domain}`;
    document.getElementById("email").innerHTML = `<a href="mailto:${email}">${email}</a>`;
});
