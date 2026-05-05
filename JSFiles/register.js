document.getElementById("registerForm").addEventListener("submit", function(e) {

    let errors = [];

    // 1. get values
    let fullName = document.getElementById("nom_prenom").value.trim();
    let fatherName = document.getElementById("prenom_pere").value.trim();
    let grandFatherName = document.getElementById("prenom_grand_pere").value.trim();
    let cin = document.getElementById("cin").value.trim();
    let birthdate = document.getElementById("birthdate").value;
    let email = document.getElementById("email").value.trim();
    let phone = document.getElementById("phone").value.trim();
    let motherfullName = document.getElementById("prenom_nom_mere").value.trim();

    // 2. required fields
    if(!fullName || !cin || !birthdate || !email || !phone){
        errors.push("Veuillez remplir tous les champs obligatoires");
    }

    // 3. name validation
    let namePattern = /^[A-Za-z\s]+$/;

    if(fullName && !namePattern.test(fullName)){
        errors.push("Nom invalide (lettres uniquement)");
    }

    if(fatherName && !namePattern.test(fatherName)){
        errors.push("Prénom du père invalide");
    }

    if(grandFatherName && !namePattern.test(grandFatherName)){
        errors.push("Prénom du grand-père invalide");
    }

    if(motherfullName && !namePattern.test(motherfullName)){
        errors.push("Nom de la mère invalide");
    }

    // 4. CIN
    if(cin && !/^[0-9]{18}$/.test(cin)){
        errors.push("CIN doit contenir 18 chiffres");
    }

    // 5. email
    if(email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){
        errors.push("Email invalide");
    }

    // 6. phone
    if(phone && !/^[0-9]{9,10}$/.test(phone)){
        errors.push("Téléphone invalide");
    }

    // 7. age
    if(birthdate){
        let today = new Date();
        let birth = new Date(birthdate);

        let age = today.getFullYear() - birth.getFullYear();
        let m = today.getMonth() - birth.getMonth();

        if(m < 0 || (m === 0 && today.getDate() < birth.getDate())){
            age--;
        }

        if(age < 18){
            errors.push("Vous devez avoir au moins 18 ans");
        }
    }

    // 8. STOP OR SUBMIT
    if(errors.length > 0){
        e.preventDefault(); // only block if errors exist
        alert(errors.join("\n"));
        return;
    }
});