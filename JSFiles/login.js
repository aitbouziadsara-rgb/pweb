document.getElementById("loginFlex").addEventListener("submit", function (e) {

    let email = document.getElementById("gmailLogin").value;
    let password = document.getElementById("passwordLogin").value;

    let errors = [];

    // 1. required fields
    if (!email || !password) {
        errors.push("Veuillez remplir tous les champs");
    }

    // 2. email validation
    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailPattern.test(email)) {
        errors.push("Email invalide");
    }

    // 3. password validation (basic rule)
    if (password && password.length < 6) {
        errors.push("Le mot de passe doit contenir au moins 6 caractères");
    }

    // stop submission if errors exist
    if (errors.length > 0) {
        e.preventDefault();
        alert(errors.join("\n"));
        return;
    }

});