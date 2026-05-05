<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // =========================
    // 1. GET + CLEAN INPUT
    // =========================
    $gmail            = trim($_POST["gmail"] ?? "");
    $password         = $_POST["password"] ?? "";
    $passwordAgain    = $_POST["passwordAgain"] ?? "";
    $verificationCode = trim($_POST["verificationCode"] ?? "");

    // =========================
    // 2. VALIDATION
    // =========================
    if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide";
    } elseif ($password !== $passwordAgain) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (strlen($password) < 6) {
        $error = "Mot de passe trop court (min 6 caractères)";
    } elseif (!preg_match("/^[0-9]{6}$/", $verificationCode)) {
        $error = "Code de vérification invalide (6 chiffres requis)";
    }

    // =========================
    // 3. CHECK EMAIL NOT ALREADY USED IN DB
    // =========================
    if (!isset($error)) {
        require_once "config.php";

        // prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $gmail);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Cet email est déjà utilisé";
        }
        $stmt->close();
    }

    // =========================
    // 4. SUCCESS → SAVE IN SESSION + REDIRECT
    // =========================
    if (!isset($error)) {
        $_SESSION["temp_user"] = [
            "email"    => htmlspecialchars($gmail),
            "password" => password_hash($password, PASSWORD_DEFAULT)
        ];

        header("Location: register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/signInStyle.css">
</head>
<body>

<!-- Show error if any -->
<?php if (isset($error)): ?>
    <p style="color:red;"><b><?= $error ?></b></p>
<?php endif; ?>

<form action="signIn.php" method="POST" id="signInForm">

    <div id="flexSignIn"></div>

    <h1 id="signInTitle">Create Your Account</h1>

    <input placeholder="gmail :" id="gmail" name="gmail" type="email"
           value="<?= htmlspecialchars($_POST['gmail'] ?? '') ?>">




           

    <input id="password" placeholder="password :" name="password" type="password">

    <input id="passwordAgain" placeholder="password again:" name="passwordAgain" type="password">

    <input id="verificationCode" placeholder="verification code :" name="verificationCode" type="text"
           value="<?= htmlspecialchars($_POST['verificationCode'] ?? '') ?>">

    <button type="submit" id="buttonSignIn">sign in</button>

</form>

<script src="../JSFiles/signup.js"></script>

</body>
</html>