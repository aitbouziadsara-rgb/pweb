<?php
session_start();

// Guard : must be logged in user
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

// =========================
// FETCH LAST DONE TIRAGE
// =========================
$tirage = $conn->query("
    SELECT * FROM tirages 
    WHERE status = 'done' 
    ORDER BY date_tirage DESC 
    LIMIT 1
")->fetch_assoc();

// =========================
// FETCH WINNERS
// =========================
$winners = null;
if ($tirage) {
    $stmt = $conn->prepare("
        SELECT u.nom_prenom 
        FROM gagnants g
        JOIN users u ON g.user_id = u.id
        WHERE g.tirage_id = ?
        ORDER BY g.created_at ASC
    ");
    $stmt->bind_param("i", $tirage["id"]);
    $stmt->execute();
    $winners = $stmt->get_result();
    $stmt->close();
}

// =========================
// CHECK IF CURRENT USER WON
// =========================
$userWon = false;
if ($tirage) {
    $stmt = $conn->prepare("
        SELECT id FROM gagnants 
        WHERE user_id = ? AND tirage_id = ?
    ");
    $stmt->bind_param("ii", $_SESSION["user"]["id"], $tirage["id"]);
    $stmt->execute();
    $stmt->store_result();
    $userWon = $stmt->num_rows > 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/resultsStyle.css">
</head>
<body>

<div id="resultsFlex">
    <aside id="sideBarUser">
        <button class="sideBarButton" onclick="window.location='profileUser.php'">profil</button>
        <button class="sideBarButton" onclick="window.location='notifs.php'">notifs</button>
        <button class="sideBarButton" onclick="window.location='resultats.php'">results</button>
        <button class="sideBarButton" onclick="window.location='logout.php'">logout</button>
    </aside>

    <div id="resultsGrid">
        <h1 id="resultsTitle">The Winners Of This Lottery</h1>

        <?php if (!$tirage): ?>
            <p>Aucun tirage effectué pour le moment</p>

        <?php else: ?>
            <p>Date du tirage : <b><?= htmlspecialchars($tirage["date_tirage"]) ?></b></p>

            <!-- personal result banner -->
            <?php if ($userWon): ?>
                <div style="background:green; color:white; padding:15px; border-radius:8px; margin:10px 0;">
                    🎉 Félicitations ! Vous faites partie des gagnants !
                </div>
            <?php else: ?>
                <div style="background:#f0f0f0; padding:15px; border-radius:8px; margin:10px 0;">
                    Vous n'avez pas été sélectionné(e) cette fois. Bonne chance au prochain tirage !
                </div>
            <?php endif; ?>

            <div id="theWinners">
                <?php if ($winners->num_rows === 0): ?>
                    <p>Aucun gagnant trouvé</p>
                <?php else: ?>
                    <?php while ($winner = $winners->fetch_assoc()): ?>
                        <div class="theWinner">
                            <h2><?= htmlspecialchars($winner["nom_prenom"]) ?></h2>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>



        <?php endif; ?>

    </div>
</div>


</body>
</html>