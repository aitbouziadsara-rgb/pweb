<?php
session_start();

if (isset($_GET["logout"])) {
    unset($_SESSION["admin"]);
    header("Location: login.php");
    exit;
}

// Already logged in → redirect
if (isset($_SESSION["user"])) {
    header("Location: profileUser.php");
    exit;
}
if (isset($_SESSION["admin"])) {
    header("Location: gestionnaireDeCompte.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // =========================
    // 1. GET + CLEAN INPUT
    // =========================
    $email    = trim($_POST["gmail"] ?? "");
    $password = $_POST["password"] ?? "";

    // =========================
    // 2. VALIDATION
    // =========================
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide";
    } elseif (empty($password)) {
        $error = "Mot de passe requis";
    }

    // =========================
    // 3. CHECK USERS TABLE FIRST
    // =========================
    if (!isset($error)) {
        require_once "config.php";

        $stmt = $conn->prepare("
            SELECT id, password_hash, status 
            FROM users 
            WHERE email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user["password_hash"])) {
            // found in users table
            if ($user["status"] === "pending") {
                $error = "Votre compte est en attente de validation";
            } elseif ($user["status"] === "blocked") {
                $error = "Votre compte a été bloqué";
            } else {
                // active user → login
                $_SESSION["user"] = ["id" => $user["id"]];
                header("Location: profileUser.php");
                exit;
            }
        } else {
            // =========================
            // 4. NOT A USER → CHECK ADMINS TABLE
            // =========================
            $stmt = $conn->prepare("
                SELECT id, password_hash 
                FROM admins 
                WHERE email = ?
            ");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($admin && password_verify($password, $admin["password_hash"])) {
                // found in admins table
                $_SESSION["admin"] = ["id" => $admin["id"]];
                header("Location: gestionnaireDeCompte.php");
                exit;
            } else {
                $error = "Email ou mot de passe incorrect";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/loginStyle.css">
</head>
<body>

<?php if (isset($error)): ?>
    <p style="color:red;"><b><?= $error ?></b></p>
<?php endif; ?>

<form action="login.php" method="POST" id="loginFlex">

    <h1 id="login">Access Your Account</h1>

    <input name="gmail" type="email" placeholder="email :" id="gmailLogin"
           value="<?= htmlspecialchars($_POST['gmail'] ?? '') ?>">

    <input name="password" type="password" placeholder="password :" id="passwordLogin">

    <button type="submit" id="buttonLogIn">Login</button>

</form>

<script src="../javaScript/login.js"></script>
</body>
</html>