<?php
session_start();

// Guard: user must have come from signup
if (!isset($_SESSION["temp_user"])) {
    header("Location: signup.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // =========================
    // 1. GET + CLEAN INPUT
    // =========================
    $nom_prenom        = trim($_POST["nom_prenom"] ?? "");
    $cin               = trim($_POST["cin"] ?? "");
    $prenom_pere       = trim($_POST["prenom_pere"] ?? "");
    $prenom_grand_pere = trim($_POST["prenom_grand_pere"] ?? "");
    $prenom_nom_mere   = trim($_POST["prenom_nom_mere"] ?? "");
    $birthdate         = trim($_POST["birthdate"] ?? "");
    $address           = trim($_POST["address"] ?? "");
    $email             = trim($_POST["email"] ?? "");
    $phone             = trim($_POST["phone"] ?? "");

    // =========================
    // 2. VALIDATION
    // =========================
    if (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]{3,}$/u", $nom_prenom)) {
        $error = "Nom et prénom invalide";
    } elseif (!preg_match("/^[0-9]{18}$/", $cin)) {
        $error = "CIN invalide (18 chiffres requis)";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]{2,}$/u", $prenom_pere)) {
        $error = "Prénom du père invalide";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]{2,}$/u", $prenom_grand_pere)) {
        $error = "Prénom du grand-père invalide";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]{3,}$/u", $prenom_nom_mere)) {
        $error = "Prénom et nom de la mère invalide";
    } elseif (empty($birthdate)) {
        $error = "Date de naissance requise";
    } else {
        $birth = new DateTime($birthdate);
        $today = new DateTime();
        $age   = $today->diff($birth)->y;
        if ($age < 18) {
            $error = "Vous devez avoir au moins 18 ans";
        }
    }

    if (!isset($error) && strlen($address) < 5) {
        $error = "Adresse invalide (min 5 caractères)";
    }

    if (!isset($error) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide";
    }

    if (!isset($error) && !preg_match("/^0[567][0-9]{8}$/", $phone)) {
        $error = "Téléphone invalide (ex: 0612345678)";
    }

    // =========================
    // 3. CHECK CIN + EMAIL NOT ALREADY USED
    // =========================
    if (!isset($error)) {
        require_once "config.php";

        // check CIN
        $stmt = $conn->prepare("SELECT id FROM users WHERE cin = ?");
        $stmt->bind_param("s", $cin);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Ce CIN est déjà utilisé";
        }
        $stmt->close();
    }

    if (!isset($error)) {
        // check email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Cet email est déjà utilisé";
        }
        $stmt->close();
    }

    // =========================
    // 4. INSERT INTO DATABASE
    // =========================
    if (!isset($error)) {
        $hashed_password = $_SESSION["temp_user"]["password"];

        $stmt = $conn->prepare("
            INSERT INTO users 
            (nom_prenom, cin, prenom_pere, prenom_grand_pere, prenom_nom_mere, birthdate, address, email, phone, password_hash, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            "ssssssssss",
            $nom_prenom,
            $cin,
            $prenom_pere,
            $prenom_grand_pere,
            $prenom_nom_mere,
            $birthdate,
            $address,
            $email,
            $phone,
            $hashed_password
        );

    if ($stmt->execute()) {
    $user_id = $conn->insert_id;
    $_SESSION["user"] = ["id" => $user_id];
    unset($_SESSION["temp_user"]);
    header("Location: profilUser.php");
    exit;
    } else {
    $error = "Erreur lors de l'inscription. Veuillez réessayer.";
}

$stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/RegisterStyle.css">
</head>
<body>

<?php if (isset($error)): ?>
    <p style="color:red;"><b><?= $error ?></b></p>
<?php endif; ?>

<form id="registerForm" action="register.php" method="POST">

    <h1 id="userInfoTitle">Enter your informations</h1>

    <input class="userInfo" id="nom_prenom" name="nom_prenom" type="text" placeholder="Nom et prénom"
           value="<?= htmlspecialchars($_POST['nom_prenom'] ?? '') ?>">

    <input class="userInfo" id="cin" name="cin" type="text" placeholder="Numéro d'identification national"
           value="<?= htmlspecialchars($_POST['cin'] ?? '') ?>">

    <input class="userInfo" id="prenom_pere" name="prenom_pere" type="text" placeholder="Prénom du père"
           value="<?= htmlspecialchars($_POST['prenom_pere'] ?? '') ?>">

    <input class="userInfo" id="prenom_grand_pere" name="prenom_grand_pere" type="text" placeholder="Prénom du grand-père"
           value="<?= htmlspecialchars($_POST['prenom_grand_pere'] ?? '') ?>">

    <input class="userInfo" id="prenom_nom_mere" name="prenom_nom_mere" type="text" placeholder="Prénom et nom de la mère"
           value="<?= htmlspecialchars($_POST['prenom_nom_mere'] ?? '') ?>">

    <input class="userInfo" id="birthdate" name="birthdate" type="date"
           value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>">

    <input class="userInfo" id="address" name="address" type="text" placeholder="Adresse"
           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">

    <input class="userInfo" id="email" name="email" type="email" placeholder="Email"
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

    <input class="userInfo" id="phone" name="phone" type="text" placeholder="Téléphone"
           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

    <button type="submit" id="registerButton">Register</button>

</form>

<script src="../JSFiles/register.js"></script>
</body>
</html>