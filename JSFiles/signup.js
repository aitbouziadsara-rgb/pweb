document.getElementById("buttonSignIn").addEventListener("click", function(e) {

    e.preventDefault(); // stop submission

    //  Récupération des valeurs
    let gmail = document.getElementById("gmail").value;
    let password = document.getElementById("password").value;
    let passwordAgain = document.getElementById("passwordAgain").value;
    let verificationCode = document.getElementById("verificationCode").value;

    //  tableau des erreurs
    let errors = [];

    //  1. champs obligatoires
    if (!gmail || !password || !passwordAgain || !verificationCode) {
        errors.push("Tu as oublié de remplir certains champs obligatoires");
    }

    //  2. validation email
    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (gmail && !emailPattern.test(gmail)) {
        errors.push("Email invalide");
    }

    //  3. validation mot de passe (min 8 caractères)
    if (password && password.length < 8) {
    errors.push("Le mot de passe doit contenir au moins 8 caractères");
}

    //  4. confirmation mot de passe
    if (password && passwordAgain && password !== passwordAgain) {
        errors.push("Les mots de passe ne correspondent pas");
    }

    //  5. validation code de vérification (6 chiffres)
    let codePattern = /^[0-9]{6}$/;
    if (verificationCode && !codePattern.test(verificationCode)) {
        errors.push("Le code de vérification doit contenir exactement 6 chiffres");
    }

    //  6. affichage erreurs
    if (errors.length > 0) {
        alert(errors.join("\n"));
        return;
    }

    // succès
    alert("Compte créé avec succès !");
    document.getElementById("buttonSignIn").addEventListener("click", function() {
    window.location.href = "Register.html";
});
});