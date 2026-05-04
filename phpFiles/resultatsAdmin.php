<?php
session_start();

// Guard : must be admin
if (!isset($_SESSION["admin"])) {
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
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/resultsStyle.css">
</head>
<body>

<div id="resultsFlex">
    <aside id="sideBarUser">
        <button class="sideBarButton" onclick="window.location='gestionnaireDeCompte.php'">account management</button>
        <button class="sideBarButton" onclick="window.location='notifsAdmin.php'">notifs</button>
        <button class="sideBarButton" onclick="window.location='resultatsAdmin.php'">results</button>
        <button class="sideBarButton" onclick="window.location='lotteryMaking.php'">lottery making</button>
        <button class="sideBarButton" onclick="window.location='logout.php'">logout</button>
    </aside>

    <div id="resultsGrid">
        <h1 id="resultsTitle">The Winners Of This Lottery</h1>

        <?php if (!$tirage): ?>
            <p>Aucun tirage effectué pour le moment</p>

        <?php else: ?>
            <p>Date du tirage : <b><?= htmlspecialchars($tirage["date_tirage"]) ?></b></p>
            <p>Nombre de gagnants : <b><?= htmlspecialchars($tirage["nb_gagnants"]) ?></b></p>

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

<script src="../javaScript/resultsAdmin.js"></script>
</body>
</html>