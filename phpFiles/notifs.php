<?php
session_start();

// Guard : must be logged in user
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

// =========================
// MARK ALL AS READ
// =========================
if (isset($_GET["markread"])) {
    $stmt = $conn->prepare("
        UPDATE notifications SET is_read = 1 
        WHERE user_id = ? OR user_id IS NULL
    ");
    $stmt->bind_param("i", $_SESSION["user"]["id"]);
    $stmt->execute();
    $stmt->close();
    header("Location: notifs.php");
    exit;
}

// =========================
// FETCH USER NOTIFICATIONS
// (personal + broadcast)
// =========================
$stmt = $conn->prepare("
    SELECT message, is_read, created_at 
    FROM notifications 
    WHERE user_id = ? OR user_id IS NULL
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $_SESSION["user"]["id"]);
$stmt->execute();
$notifs = $stmt->get_result();
$stmt->close();

// count unread
$stmt = $conn->prepare("
    SELECT COUNT(*) as nb FROM notifications 
    WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0
");
$stmt->bind_param("i", $_SESSION["user"]["id"]);
$stmt->execute();
$unread = $stmt->get_result()->fetch_assoc()["nb"];
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/notifsStyle.css">
</head>
<body>

<aside id="sideBarUser">
    <button class="sideBarButton" onclick="window.location='profileUser.php'">profil</button>
    <button class="sideBarButton" onclick="window.location='notifs.php'">
        notifs <?= $unread > 0 ? "($unread)" : "" ?>
    </button>
    <button class="sideBarButton" onclick="window.location='resultats.php'">results</button>
    <button class="sideBarButton" onclick="window.location='logout.php'">logout</button>
</aside>

<div id="notifsPage">
    <h1 id="notifsTitle">Your Notifications</h1>

    <?php if ($unread > 0): ?>
        <a href="notifs.php?markread=1">
            <button>Mark all as read</button>
        </a>
    <?php endif; ?>

    <div id="notifs">
        <?php if ($notifs->num_rows === 0): ?>
            <p>Aucune notification</p>
        <?php else: ?>
            <?php while ($notif = $notifs->fetch_assoc()): ?>
                <div class="notif" style="opacity: <?= $notif['is_read'] ? '0.5' : '1' ?>">
                    <p class="theContextOfUser">
                        <?= htmlspecialchars($notif["message"]) ?>
                    </p>
                    <small><?= $notif["created_at"] ?></small>
                    <?php if (!$notif["is_read"]): ?>
                        <span style="color:orange;"> 🔔 nouveau</span>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>


</body>
</html>