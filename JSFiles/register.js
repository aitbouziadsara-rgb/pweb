document.getElementById("registerForm").addEventListener("submit", function(e) {

    e.preventDefault(); // stop form submission

    //  Récupération des valeurs
    let fullName = document.getElementById("nom_prenom").value;
    let fatherName = document.getElementById("prenom_pere").value;
    let grandFatherName = document.getElementById("prenom_grand_pere").value;
    let cin = document.getElementById("cin").value;
    let birthdate = document.getElementById("birthdate").value;
    let email = document.getElementById("email").value;
    let phone = document.getElementById("phone").value;
    let motherfullName = document.getElementById("prenom_nom_mere").value;

    //  tableau des erreurs
    let errors = [];

    //  1. champs obligatoires
    if(!fullName || !cin || !birthdate || !email || !phone){
        errors.push("tu as oublié de remplir certians  champs  obligatoires");
    }

    //  2. validation des noms et prenoms
     let namePattern = /^[A-Za-z\s]+$/;   //\s =espace + tabulation + saut de ligne

    if(fullName && !namePattern.test(fullName)){    //test est une méthode qui vérifie si le nom correspond au pattern
    errors.push("Le nom doit contenir uniquement des lettres");
    }


    //  prénom du père
    if(fatherName && !namePattern.test(fatherName)){ 
    errors.push("Le prénom du père doit contenir uniquement des lettres");
}

   //  prénom du grand-père
    if(grandFatherName && !namePattern.test(grandFatherName)){
    errors.push("Le prénom du grand-père doit contenir uniquement des lettres");
}

//  prénom de la mèr
    if(motherfullName && !namePattern.test(motherfullName)){
    errors.push("Le nom et prénom de la mère doit contenir uniquement des lettres");
}



   //  3. validation CIN
    let cinPattern = /^[0-9]{8}$/;
    if(cin && !cinPattern.test(cin)){
        errors.push("Le CIN doit contenir uniquement 8 chiffres");
    }

    //  3. validation email
    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if(email && !emailPattern.test(email)){
        errors.push("Email invalide");
    }

    //  4. validation téléphone
    let phonePattern = /^[0-9]{9,10}$/;
    if(phone && !phonePattern.test(phone)){
        errors.push("Numéro de téléphone invalide");
    }

    //  5. calcul âge
    if(birthdate){
        let today = new Date();
        let birth = new Date(birthdate);

        let age = today.getFullYear() - birth.getFullYear();
        let monthDiff = today.getMonth() - birth.getMonth();


        /*
         Cas 1 :
            le mois de naissance n’est pas encore arrivé
        Cas 2 :
            on est dans le même mois
            mais le jour de naissance n’est pas encore passé
                    */
        if(monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())){
            age--;
        }

        if(age < 18){
            errors.push("Vous devez avoir au moins 18 ans");
        }
    }

    //  6. affichage erreurs
    if(errors.length > 0){
        alert(errors.join("\n"));
        return;
    }

    //  succès
    alert("Inscription réussie !");
    this.submit(); //envoie le formulaire si tout est bon
});

document.getElementById("registerButton").addEventListener("click", function() {
    window.location.href = "profilUser.html";
});