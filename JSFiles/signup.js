document.getElementById("signInForm").addEventListener("submit", function(e) {

    let gmail = document.getElementById("gmail").value;
    let password = document.getElementById("password").value;
    let passwordAgain = document.getElementById("passwordAgain").value;
    let verificationCode = document.getElementById("verificationCode").value;

    let errors = [];

    // 1. required fields
    if (!gmail || !password || !passwordAgain || !verificationCode) {
        errors.push("Tu as oublié de remplir certains champs obligatoires");
    }

    // 2. email validation
    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (gmail && !emailPattern.test(gmail)) {
        errors.push("Email invalide");
    }

    // 3. password length
    if (password && password.length < 8) {
        errors.push("Le mot de passe doit contenir au moins 8 caractères");
    }

    // 4. password match
    if (password && passwordAgain && password !== passwordAgain) {
        errors.push("Les mots de passe ne correspondent pas");
    }

    // 5. verification code
    let codePattern = /^[0-9]{6}$/;
    if (verificationCode && !codePattern.test(verificationCode)) {
        errors.push("Le code de vérification doit contenir exactement 6 chiffres");
    }

    // stop submission if errors
    if (errors.length > 0) {
         e.preventDefault();
        alert(errors.join("\n"));
       
        return;
    }

  
});