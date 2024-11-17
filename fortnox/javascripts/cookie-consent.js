document.addEventListener("DOMContentLoaded", function() {

    const cookieConsent = document.getElementById("cookieConsent");
    const acceptButton = document.getElementById("acceptCookies");
    const declineButton = document.getElementById("declineCookies");
    const cookieMessageDialog = document.getElementById("cookieMessageDialog");
    const closeCookieDialog = document.getElementById("closeCookieDialog");

    if (localStorage.getItem("cookieConsent") === "accepted") {
        cookieConsent.style.display = "none";
    } else {
        cookieConsent.style.display = "block";
    }

    acceptButton.addEventListener("click", function() {
        localStorage.setItem("cookieConsent", "accepted");
        cookieConsent.style.display = "none";
    });

    declineButton.addEventListener("click", function() {
        localStorage.removeItem("cookieConsent");
        cookieMessageDialog.style.display = "flex";
    });

    closeCookieDialog.addEventListener("click", function() {
        cookieMessageDialog.style.display = "none";
        window.location.href = "../logic/logout.php";
    });
    
});
