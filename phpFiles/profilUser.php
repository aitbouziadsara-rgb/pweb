<?php
session_start();

// Guard : not logged in → back to login
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION["user"];

// =========================
// FETCH FRESH DATA FROM DB
// =========================
require_once "config.php";

$stmt = $conn->prepare("
    SELECT nom_prenom, cin, prenom_pere, prenom_grand_pere,
           prenom_nom_mere, birthdate, address, email, phone, status
    FROM users WHERE id = ?
");
$stmt->bind_param("i", $user["id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// =========================
// CHECK IF BLOCKED
// =========================
if ($user["status"] === "blocked") {
    unset($_SESSION["user"]);
    header("Location: login.php");
    exit;
}

// =========================
// FETCH NOTIFICATIONS
// =========================
$stmt = $conn->prepare("
    SELECT message, created_at FROM notifications
    WHERE user_id = ? OR user_id IS NULL
    AND is_read = 0
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $_SESSION["user"]["id"]);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();

// =========================
// FETCH TIRAGE RESULTS
// =========================
$stmt = $conn->prepare("
    SELECT t.date_tirage, 
           CASE WHEN g.user_id IS NOT NULL THEN 'Gagnant' ELSE 'Non gagnant' END as resultat
    FROM inscriptions i
    JOIN tirages t ON i.tirage_id = t.id
    LEFT JOIN gagnants g ON g.tirage_id = t.id AND g.user_id = i.user_id
    WHERE i.user_id = ?
    ORDER BY t.date_tirage DESC
");
$stmt->bind_param("i", $_SESSION["user"]["id"]);
$stmt->execute();
$results = $stmt->get_result();
$stmt->close();

// =========================
// FETCH CURRENT OPEN TIRAGE
// =========================
$tirage = $conn->query("
    SELECT * FROM tirages WHERE status = 'open' LIMIT 1
")->fetch_assoc();

// =========================
// CHECK IF ALREADY INSCRIBED
// =========================
$alreadyInscribed = false;
if ($tirage) {
    $stmt = $conn->prepare("
        SELECT id FROM inscriptions 
        WHERE user_id = ? AND tirage_id = ?
    ");
    $stmt->bind_param("ii", $_SESSION["user"]["id"], $tirage["id"]);
    $stmt->execute();
    $stmt->store_result();
    $alreadyInscribed = $stmt->num_rows > 0;
    $stmt->close();
}

// =========================
// HANDLE INSCRIPTION
// =========================
if (isset($_POST["inscrire"]) && $tirage) {

    // CHECK 1 : 5 tirages ban
    $stmt = $conn->prepare("
        SELECT COUNT(*) as nb FROM gagnants 
        WHERE user_id = ? 
        AND tirage_id > (? - 5)
    ");
    $stmt->bind_param("ii", $_SESSION["user"]["id"], $tirage["id"]);
    $stmt->execute();
    $banned = $stmt->get_result()->fetch_assoc()["nb"] > 0;
    $stmt->close();

    // CHECK 2 : 18+ on draw date
    $birth      = new DateTime($user["birthdate"]);
    $dateTirage = new DateTime($tirage["date_tirage"]);
    $age        = $dateTirage->diff($birth)->y;

    if ($banned) {
        $inscriptionError = "Vous ne pouvez pas participer aux 5 tirages suivant votre victoire";
    } elseif ($age < 18) {
        $inscriptionError = "Vous devez avoir au moins 18 ans le jour du tirage";
    } elseif ($user["status"] === "pending") {
        $inscriptionError = "Votre compte doit être validé par un administrateur";
    } else {
        // ALL CHECKS PASSED → INSERT
        $stmt = $conn->prepare("
            INSERT INTO inscriptions (user_id, tirage_id) VALUES (?, ?)
        ");
        $stmt->bind_param("ii", $_SESSION["user"]["id"], $tirage["id"]);
        $stmt->execute();
        $stmt->close();
        $inscriptionSuccess = "✅ Vous êtes inscrit au tirage du " . $tirage["date_tirage"] . " !";
        $alreadyInscribed   = true;
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
    <aside id="sideBarUser">
        <button class="sideBarButton" onclick="showSection('profilSection')">profil</button>
        <button class="sideBarButton" onclick="showSection('notifsSection')">notifs</button>
        <button class="sideBarButton" onclick="showSection('resultsSection')">results</button>
        <button class="sideBarButton" onclick="showSection('inscriptionSection')">s'inscrire</button>
        <a href="logout.php">
            <button class="sideBarButton" id="logoutButton">Se déconnecter</button>
        </a>
    </aside>

    <!-- ===================== -->
    <!-- SECTION : PROFIL      -->
    <!-- ===================== -->
    <div id="profilSection" class="section">
        <div id="profilInfoFlex">
            <h1 id="nameOfUserProfil">The user profil</h1>

            <?php if ($user["status"] === "pending"): ?>
                <p style="color:orange;"><b>⚠️ Votre compte est en attente de validation</b></p>
            <?php endif; ?>

            <h2 id="userName">full name : <?= htmlspecialchars($user["nom_prenom"]) ?></h2>
            <p class="userProfilInfo">unique identification number : <?= htmlspecialchars($user["cin"]) ?></p>
            <p class="userProfilInfo">father's name : <?= htmlspecialchars($user["prenom_pere"]) ?></p>
            <p class="userProfilInfo">grand father name : <?= htmlspecialchars($user["prenom_grand_pere"]) ?></p>
            <p class="userProfilInfo">mother's name : <?= htmlspecialchars($user["prenom_nom_mere"]) ?></p>
            <p class="userProfilInfo">birth date : <?= htmlspecialchars($user["birthdate"]) ?></p>
            <p class="userProfilInfo">adress : <?= htmlspecialchars($user["address"]) ?></p>
            <p class="userProfilInfo">gmail : <?= htmlspecialchars($user["email"]) ?></p>
            <p class="userProfilInfo">number : <?= htmlspecialchars($user["phone"]) ?></p>
        </div>
    </div>

    <!-- ===================== -->
    <!-- SECTION : NOTIFS      -->
    <!-- ===================== -->
    <div id="notifsSection" class="section" style="display:none;">
        <h1>Notifications</h1>
        <?php if ($notifications->num_rows === 0): ?>
            <p>Aucune notification</p>
        <?php else: ?>
            <?php while ($notif = $notifications->fetch_assoc()): ?>
                <div class="notifCard">
                    <p><?= htmlspecialchars($notif["message"]) ?></p>
                    <small><?= $notif["created_at"] ?></small>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- ===================== -->
    <!-- SECTION : RESULTS     -->
    <!-- ===================== -->
    <div id="resultsSection" class="section" style="display:none;">
        <h1>Mes résultats</h1>
        <?php if ($results->num_rows === 0): ?>
            <p>Vous n'avez participé à aucun tirage</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date du tirage</th>
                        <th>Résultat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $results->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row["date_tirage"]) ?></td>
                            <td style="color: <?= $row['resultat'] === 'Gagnant' ? 'green' : 'red' ?>">
                                <?= $row["resultat"] ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- ===================== -->
    <!-- SECTION : INSCRIPTION -->
    <!-- ===================== -->
    <div id="inscriptionSection" class="section" style="display:none;">
        <h1>S'inscrire au tirage</h1>

        <?php if (!$tirage): ?>
            <p>Aucun tirage ouvert pour le moment</p>

        <?php else: ?>
            <p>Date du tirage : <b><?= htmlspecialchars($tirage["date_tirage"]) ?></b></p>
            <p>Inscriptions ouvertes jusqu'au : <b><?= htmlspecialchars($tirage["date_fermeture"]) ?></b></p>

           <?php if (isset($inscriptionError)): ?>
                <script>
                    alert("<?= $inscriptionError ?>");
                </script>
            <?php endif; ?>



            <?php if (isset($inscriptionSuccess)): ?>
                <script>
                    alert("<?= $inscriptionSuccess ?>");
                </script>
            <?php endif; ?>>

            <?php if ($alreadyInscribed): ?>
                <p style="color:green;">✅ Vous êtes déjà inscrit à ce tirage</p>
            <?php else: ?>
                <form method="POST" action="profileUser.php">
                    <button type="submit" name="inscrire">
                        🎯 Je m'inscris au tirage
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

<script>
function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(s => s.style.display = 'none');
    document.getElementById(sectionId).style.display = 'block';
}
</script>

<script src="../javaScript/profilUser.js"></script>
</body>
</html>