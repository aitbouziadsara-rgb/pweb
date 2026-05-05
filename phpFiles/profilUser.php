<?php
session_start();

// Guard : not logged in → back to login
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$userSession = $_SESSION["user"];

require_once "config.php";

// =========================
// FETCH USER DATA
// =========================
$stmt = $conn->prepare("
    SELECT nom_prenom, cin, prenom_pere, prenom_grand_pere,
           prenom_nom_mere, birthdate, address, email, phone, status
    FROM users 
    WHERE id = ?
");
$stmt->bind_param("i", $userSession["id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// =========================
// BLOCKED USER → LOGOUT
// =========================
if ($user["status"] === "blocked") {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// =========================
// FETCH NOTIFICATIONS
// =========================
$stmt = $conn->prepare("
    SELECT message, created_at 
    FROM notifications
    WHERE (user_id = ? OR user_id IS NULL)
    AND is_read = 0
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $userSession["id"]);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();

// =========================
// FETCH RESULTS
// =========================
$stmt = $conn->prepare("
    SELECT t.date_tirage, 
           CASE 
                WHEN g.user_id IS NOT NULL THEN 'Gagnant' 
                ELSE 'Non gagnant' 
           END AS resultat
    FROM inscriptions i
    JOIN tirages t ON i.tirage_id = t.id
    LEFT JOIN gagnants g 
        ON g.tirage_id = t.id AND g.user_id = i.user_id
    WHERE i.user_id = ?
    ORDER BY t.date_tirage DESC
");
$stmt->bind_param("i", $userSession["id"]);
$stmt->execute();
$results = $stmt->get_result();
$stmt->close();

// =========================
// CURRENT TIRAGE
// =========================
$tirage = $conn->query("
    SELECT * FROM tirages 
    WHERE status = 'open' 
    LIMIT 1
")->fetch_assoc();

// =========================
// CHECK INSCRIPTION
// =========================
$alreadyInscribed = false;

if ($tirage) {
    $stmt = $conn->prepare("
        SELECT id FROM inscriptions 
        WHERE user_id = ? AND tirage_id = ?
    ");
    $stmt->bind_param("ii", $userSession["id"], $tirage["id"]);
    $stmt->execute();
    $stmt->store_result();
    $alreadyInscribed = $stmt->num_rows > 0;
    $stmt->close();
}

// =========================
// INSCRIPTION ACTION
// =========================
if (isset($_POST["inscrire"]) && $tirage) {

    $birth      = new DateTime($user["birthdate"]);
    $drawDate   = new DateTime($tirage["date_tirage"]);
    $age        = $drawDate->diff($birth)->y;

    // simple ban logic (your logic kept)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as nb 
        FROM gagnants 
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userSession["id"]);
    $stmt->execute();
    $wonCount = $stmt->get_result()->fetch_assoc()["nb"];
    $stmt->close();

    $banned = ($wonCount >= 1); // simplified safe version

    if ($banned) {
        $inscriptionError = "Vous êtes temporairement bloqué après une victoire.";
    } elseif ($age < 18) {
        $inscriptionError = "Vous devez avoir au moins 18 ans le jour du tirage.";
    } elseif ($user["status"] === "pending") {
        $inscriptionError = "Compte en attente de validation.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO inscriptions (user_id, tirage_id)
            VALUES (?, ?)
        ");
        $stmt->bind_param("ii", $userSession["id"], $tirage["id"]);
        $stmt->execute();
        $stmt->close();

        $inscriptionSuccess = "Inscription réussie !";
        $alreadyInscribed = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/profileUserStyle.css">
</head>
<body>

<div id="profilFlex">

    <!-- SIDEBAR -->
    <aside id="sideBarUser">
        <button onclick="showSection('profilSection')">profil</button>
        <button onclick="showSection('notifsSection')">notifs</button>
        <button onclick="showSection('resultsSection')">results</button>
        <button onclick="showSection('inscriptionSection')">s'inscrire</button>

        <!-- FIXED LOGOUT -->
        <a href="logout.php">
            <button>Se déconnecter</button>
        </a>
    </aside>

    <!-- PROFILE -->
    <div id="profilSection" class="section">
        <h1>Profil utilisateur</h1>

        <?php if ($user["status"] === "pending"): ?>
            <p style="color:orange;">Compte en attente de validation</p>
        <?php endif; ?>

        <p>Nom: <?= htmlspecialchars($user["nom_prenom"]) ?></p>
        <p>CIN: <?= htmlspecialchars($user["cin"]) ?></p>
        <p>Email: <?= htmlspecialchars($user["email"]) ?></p>
        <p>Téléphone: <?= htmlspecialchars($user["phone"]) ?></p>
    </div>

    <!-- NOTIFICATIONS -->
    <div id="notifsSection" class="section" style="display:none;">
        <h1>Notifications</h1>

        <?php while ($n = $notifications->fetch_assoc()): ?>
            <p><?= htmlspecialchars($n["message"]) ?></p>
        <?php endwhile; ?>
    </div>

    <!-- RESULTS -->
    <div id="resultsSection" class="section" style="display:none;">
        <h1>Résultats</h1>

        <?php while ($r = $results->fetch_assoc()): ?>
            <p>
                <?= $r["date_tirage"] ?> : 
                <b style="color:<?= $r["resultat"] === "Gagnant" ? "green" : "red" ?>">
                    <?= $r["resultat"] ?>
                </b>
            </p>
        <?php endwhile; ?>
    </div>

    <!-- INSCRIPTION -->
    <div id="inscriptionSection" class="section" style="display:none;">

        <h1>Inscription tirage</h1>

        <?php if (isset($inscriptionError)): ?>
            <script>alert("<?= $inscriptionError ?>");</script>
        <?php endif; ?>

        <?php if (isset($inscriptionSuccess)): ?>
            <script>alert("<?= $inscriptionSuccess ?>");</script>
        <?php endif; ?>

        <?php if ($alreadyInscribed): ?>
            <p>Déjà inscrit</p>
        <?php else: ?>
            <form method="POST">
                <button name="inscrire">S'inscrire</button>
            </form>
        <?php endif; ?>

    </div>

</div>

<script>
function showSection(id) {
    document.querySelectorAll(".section").forEach(s => s.style.display = "none");
    document.getElementById(id).style.display = "block";
}
</script>

</body>
</html>