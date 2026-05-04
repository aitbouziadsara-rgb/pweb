<?php
session_start();

// Guard : must be admin
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

// =========================
// HANDLE ACTIONS
// =========================
if (isset($_GET["action"]) && isset($_GET["user_id"])) {
    $action  = $_GET["action"];
    $user_id = (int)$_GET["user_id"]; // (int) to prevent SQL injection

    if ($action === "validate") {
        $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // send notification to user
        $msg = "Votre compte a été validé. Vous pouvez maintenant vous connecter.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $msg);
        $stmt->execute();
        $stmt->close();

    } elseif ($action === "block") {
        $stmt = $conn->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // send notification to user
        $msg = "Votre compte a été bloqué. Contactez l'administrateur.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $msg);
        $stmt->execute();
        $stmt->close();

    } elseif ($action === "delete") {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    // redirect to avoid resubmission on refresh
    header("Location: gestionnaireDeCompte.php");
    exit;
}

// =========================
// FETCH ALL USERS
// =========================
$result = $conn->query("
    SELECT id, nom_prenom, cin, email, phone, birthdate, 
           prenom_pere, prenom_grand_pere, prenom_nom_mere,
           address, status, created_at 
    FROM users 
    ORDER BY 
        CASE status 
            WHEN 'pending' THEN 1 
            WHEN 'active'  THEN 2 
            WHEN 'blocked' THEN 3 
        END,
        created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/gestionnaireDeCompteStyle.css">
</head>
<body>

<div id="managementFlex">
    <aside id="sideBarUser">
        <button class="sideBarButton" onclick="showSection('accountSection')">account management</button>
        <button class="sideBarButton" onclick="window.location='notifsAdmin.php'">notifs</button>
        <button class="sideBarButton" onclick="window.location='resultatsAdmin.php'">results</button>
        <button class="sideBarButton" onclick="window.location='lotteryMaking.php'">lottery making</button>
        <button class="sideBarButton" onclick="window.location='logout.php'">logout</button>
    </aside>

    <div id="management">F
        <h1 id="accountManagement">Account Management</h1>

        <?php if ($result->num_rows === 0): ?>
            <p>Aucun compte utilisateur</p>
        <?php else: ?>
            <?php while ($user = $result->fetch_assoc()): ?>
                <div class="request" style="border-left: 4px solid 
                    <?= $user['status'] === 'pending' ? 'orange' : 
                       ($user['status'] === 'active'  ? 'green'  : 'red') ?>">

                    <h2 class="nameOfAccount"><?= htmlspecialchars($user["nom_prenom"]) ?></h2>

                    <span class="statusBadge">
                        <?= $user["status"] === 'pending' ? '⏳ En attente' : 
                           ($user["status"] === 'active'  ? '✅ Actif'      : '🚫 Bloqué') ?>
                    </span>

                    <!-- MORE INFO (hidden by default) -->
                    <div class="moreInfoDiv" id="info_<?= $user['id'] ?>" style="display:none;">
                        <p><b>CIN :</b> <?= htmlspecialchars($user["cin"]) ?></p>
                        <p><b>Email :</b> <?= htmlspecialchars($user["email"]) ?></p>
                        <p><b>Téléphone :</b> <?= htmlspecialchars($user["phone"]) ?></p>
                        <p><b>Date de naissance :</b> <?= htmlspecialchars($user["birthdate"]) ?></p>
                        <p><b>Père :</b> <?= htmlspecialchars($user["prenom_pere"]) ?></p>
                        <p><b>Grand-père :</b> <?= htmlspecialchars($user["prenom_grand_pere"]) ?></p>
                        <p><b>Mère :</b> <?= htmlspecialchars($user["prenom_nom_mere"]) ?></p>
                        <p><b>Adresse :</b> <?= htmlspecialchars($user["address"]) ?></p>
                        <p><b>Inscrit le :</b> <?= $user["created_at"] ?></p>
                    </div>

                    <div class="flexButton">
                        <!-- MORE INFO TOGGLE -->
                        <button class="moreInfo"
                                onclick="toggleInfo(<?= $user['id'] ?>)">
                            more info
                        </button>

                        <!-- VALIDATE (only for pending) -->
                        <?php if ($user["status"] === "pending"): ?>
                            <a href="gestionnaireDeCompte.php?action=validate&user_id=<?= $user['id'] ?>">
                                <button class="acceptButton">✅ Valider</button>
                            </a>
                        <?php endif; ?>

                        <!-- BLOCK (only for active) -->
                        <?php if ($user["status"] === "active"): ?>
                            <a href="gestionnaireDeCompte.php?action=block&user_id=<?= $user['id'] ?>">
                                <button class="blockButton">🚫 Bloquer</button>
                            </a>
                        <?php endif; ?>

                        <!-- DELETE (always) -->
                        <a href="gestionnaireDeCompte.php?action=delete&user_id=<?= $user['id'] ?>"
                           onclick="return confirm('Supprimer ce compte ?')">
                            <button class="deleteButton">🗑️ Supprimer</button>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleInfo(userId) {
    const div = document.getElementById('info_' + userId);
    div.style.display = div.style.display === 'none' ? 'block' : 'none';
}

function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(s => s.style.display = 'none');
    document.getElementById(sectionId).style.display = 'block';
}
</script>

<script src="../javaScript/gestionnaireDeCompte.js"></script>
</body>
</html>