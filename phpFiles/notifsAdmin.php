<?php
session_start();

// Guard : must be admin
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

// =========================
// MARK ALL AS READ
// =========================
if (isset($_GET["markread"])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id IS NULL");
    header("Location: notifsAdmin.php");
    exit;
}

// =========================
// FETCH ADMIN NOTIFICATIONS
// (notifications sent to all = user_id IS NULL)
// =========================
$notifs = $conn->query("
    SELECT message, is_read, created_at 
    FROM notifications 
    WHERE user_id IS NULL
    ORDER BY created_at DESC
");

// count unread
$unread = $conn->query("
    SELECT COUNT(*) as nb FROM notifications 
    WHERE user_id IS NULL AND is_read = 0
")->fetch_assoc()["nb"];
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/notifsStyle.css">
</head>
<body>

<aside id="sideBarUser">
    <button class="sideBarButton" onclick="window.location='gestionnaireDeCompte.php'">account management</button>
    <button class="sideBarButton" onclick="window.location='notifsAdmin.php'">
        notifs <?= $unread > 0 ? "($unread)" : "" ?>
    </button>
    <button class="sideBarButton" onclick="window.location='resultatsAdmin.php'">results</button>
    <button class="sideBarButton" onclick="window.location='lotteryMaking.php'">lottery making</button>
    <button class="sideBarButton" onclick="window.location='logout.php'">logout</button>
</aside>

<div id="notifsPage">
    <h1 id="notifsTitle">Your Notifications</h1>

    <?php if ($unread > 0): ?>
        <a href="notifsAdmin.php?markread=1">
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

<script src="../javaScript/notifsAdmin.js"></script>
</body>
</html>