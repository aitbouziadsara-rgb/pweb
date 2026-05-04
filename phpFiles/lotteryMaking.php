<?php
session_start();

// Guard : must be admin
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require_once "config.php";

// =========================
// FETCH CURRENT TIRAGE
// =========================
$tirage = $conn->query("
    SELECT * FROM tirages 
    WHERE status != 'done' 
    ORDER BY created_at DESC 
    LIMIT 1
")->fetch_assoc();

// =========================
// HANDLE FORM SUBMISSIONS
// =========================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST["action"] ?? "";

    // ─────────────────────────
    // STEP 1 : SET NB_GAGNANTS
    // ─────────────────────────
    if ($action === "set_nb_gagnants") {
        $nb = (int)$_POST["nb_gagnants"];

        if ($nb <= 0) {
            $error = "Nombre de gagnants invalide";
        } else {
            if ($tirage) {
                // update existing
                $stmt = $conn->prepare("UPDATE tirages SET nb_gagnants = ? WHERE id = ?");
                $stmt->bind_param("ii", $nb, $tirage["id"]);
            } else {
                // create new tirage
                $stmt = $conn->prepare("INSERT INTO tirages (nb_gagnants) VALUES (?)");
                $stmt->bind_param("i", $nb);
            }
            $stmt->execute();
            $stmt->close();
            $success = "Nombre de gagnants enregistré !";

            // refresh tirage data
            $tirage = $conn->query("
                SELECT * FROM tirages 
                WHERE status != 'done' 
                ORDER BY created_at DESC 
                LIMIT 1
            ")->fetch_assoc();
        }
    }

    // ─────────────────────────
    // STEP 2 : SET DATE_TIRAGE
    // ─────────────────────────
    elseif ($action === "set_date_tirage") {
        $date_tirage = $_POST["date_tirage"] ?? "";

        if (empty($date_tirage)) {
            $error = "Date du tirage requise";
        } elseif ($date_tirage <= date("Y-m-d")) {
            $error = "La date du tirage doit être dans le futur";
        } elseif (!$tirage || !$tirage["nb_gagnants"]) {
            $error = "Définissez d'abord le nombre de gagnants";
        } else {
            $stmt = $conn->prepare("UPDATE tirages SET date_tirage = ? WHERE id = ?");
            $stmt->bind_param("si", $date_tirage, $tirage["id"]);
            $stmt->execute();
            $stmt->close();
            $success = "Date du tirage enregistrée !";

            // refresh
            $tirage = $conn->query("
                SELECT * FROM tirages 
                WHERE status != 'done' 
                ORDER BY created_at DESC 
                LIMIT 1
            ")->fetch_assoc();
        }
    }

    // ─────────────────────────────────
    // STEP 3 : SET INSCRIPTION DATES
    // ─────────────────────────────────
    elseif ($action === "set_inscription_dates") {
        $date_ouverture  = $_POST["date_ouverture"] ?? "";
        $date_fermeture  = $_POST["date_fermeture"] ?? "";

        if (empty($date_ouverture) || empty($date_fermeture)) {
            $error = "Les deux dates sont requises";
        } elseif ($date_ouverture >= $date_fermeture) {
            $error = "La date d'ouverture doit être avant la date de fermeture";
        } elseif ($date_fermeture >= $tirage["date_tirage"]) {
            $error = "La date de fermeture doit être avant la date du tirage";
        } elseif (!$tirage || !$tirage["date_tirage"]) {
            $error = "Définissez d'abord la date du tirage";
        } else {
            $stmt = $conn->prepare("
                UPDATE tirages 
                SET date_ouverture = ?, date_fermeture = ?, status = 'open'
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $date_ouverture, $date_fermeture, $tirage["id"]);
            $stmt->execute();
            $stmt->close();

            // notify all users that inscriptions are open
            $msg = "Les inscriptions au tirage au sort sont ouvertes du " . $date_ouverture . " au " . $date_fermeture;
            $conn->query("INSERT INTO notifications (user_id, message) VALUES (NULL, '$msg')");

            $success = "Dates d'inscription enregistrées ! Les utilisateurs ont été notifiés.";

            // refresh
            $tirage = $conn->query("
                SELECT * FROM tirages 
                WHERE status != 'done' 
                ORDER BY created_at DESC 
                LIMIT 1
            ")->fetch_assoc();
        }
    }

    // ─────────────────────────
    // STEP 4 : LAUNCH TIRAGE
    // ─────────────────────────
    elseif ($action === "launch_tirage") {
        $today = date("Y-m-d");

        if ($tirage["date_tirage"] !== $today) {
            $error = "Le tirage ne peut être lancé qu'à la date fixée : " . $tirage["date_tirage"];
        } else {
            // get all inscribed users
            $stmt = $conn->prepare("
                SELECT user_id FROM inscriptions WHERE tirage_id = ?
            ");
            $stmt->bind_param("i", $tirage["id"]);
            $stmt->execute();
            $participants = $stmt->get_result();
            $stmt->close();

            // build pool with chances
            $pool = [];
            while ($row = $participants->fetch_assoc()) {
                $uid = $row["user_id"];

                // count inscriptions without winning
                $stmt2 = $conn->prepare("
                    SELECT COUNT(*) as chances FROM inscriptions
                    WHERE user_id = ?
                    AND tirage_id NOT IN (
                        SELECT tirage_id FROM gagnants WHERE user_id = ?
                    )
                ");
                $stmt2->bind_param("ii", $uid, $uid);
                $stmt2->execute();
                $chances = $stmt2->get_result()->fetch_assoc()["chances"];
                $stmt2->close();

                for ($i = 0; $i < $chances; $i++) {
                    $pool[] = $uid;
                }
            }

            if (empty($pool)) {
                $error = "Aucun participant inscrit pour ce tirage";
            } else {
                // pick winners
                shuffle($pool);
                $pool    = array_unique($pool);
                $winners = array_slice($pool, 0, $tirage["nb_gagnants"]);

                // save winners
                foreach ($winners as $winner_id) {
                    $stmt = $conn->prepare("
                        INSERT INTO gagnants (user_id, tirage_id) VALUES (?, ?)
                    ");
                    $stmt->bind_param("ii", $winner_id, $tirage["id"]);
                    $stmt->execute();
                    $stmt->close();

                    // notify winner
                    $msg = "Félicitations ! Vous avez été sélectionné(e) pour le Hadj !";
                    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $stmt->bind_param("is", $winner_id, $msg);
                    $stmt->execute();
                    $stmt->close();
                }

                // notify non winners
                $conn->query("
                    INSERT INTO notifications (user_id, message)
                    SELECT i.user_id, 'Le tirage au sort a eu lieu. Vous n\'avez pas été sélectionné(e) cette fois.'
                    FROM inscriptions i
                    WHERE i.tirage_id = {$tirage['id']}
                    AND i.user_id NOT IN (
                        SELECT user_id FROM gagnants WHERE tirage_id = {$tirage['id']}
                    )
                ");

                // update tirage status to done
                $stmt = $conn->prepare("UPDATE tirages SET status = 'done' WHERE id = ?");
                $stmt->bind_param("i", $tirage["id"]);
                $stmt->execute();
                $stmt->close();

                header("Location: resultatsAdmin.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/lotteryMakingStyle.css">
</head>
<body>

<div id="lotteryFlex">
    <aside id="sideBarUser">
        <button class="sideBarButton" onclick="window.location='gestionnaireDeCompte.php'">account management</button>
        <button class="sideBarButton" onclick="window.location='notifsAdmin.php'">notifs</button>
        <button class="sideBarButton" onclick="window.location='resultatsAdmin.php'">results</button>
        <button class="sideBarButton" onclick="window.location='lotteryMaking.php'">lottery making</button>
        <button class="sideBarButton" onclick="window.location='logout.php'">logout</button>
    </aside>

    <div id="lotteryMakingFlex">
        <h1 id="lotteryMakingTitle">make a lottery</h1>

        <?php if (isset($error)): ?>
            <p style="color:red;"><b><?= $error ?></b></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p style="color:green;"><b><?= $success ?></b></p>
        <?php endif; ?>

        <div id="inputs">

            <!-- ─────────────────────── -->
            <!-- STEP 1 : NB GAGNANTS   -->
            <!-- ─────────────────────── -->
            <form method="POST" action="lotteryMaking.php">
                <input type="hidden" name="action" value="set_nb_gagnants">
                <label>number of winners :</label>
                <input type="number" name="nb_gagnants" class="inputLotteryMaking"
                       placeholder="number of winners"
                       value="<?= $tirage['nb_gagnants'] ?? '' ?>">
                <button type="submit" class="buttonLotteryMaking">Save</button>
            </form>

            <!-- ─────────────────────── -->
            <!-- STEP 2 : DATE TIRAGE   -->
            <!-- ─────────────────────── -->
            <?php if ($tirage && $tirage["nb_gagnants"]): ?>
            <form method="POST" action="lotteryMaking.php">
                <input type="hidden" name="action" value="set_date_tirage">
                <label>lottery date :</label>
                <input type="date" name="date_tirage" class="inputLotteryMaking"
                       value="<?= $tirage['date_tirage'] ?? '' ?>">
                <button type="submit" class="buttonLotteryMaking">Save</button>
            </form>
            <?php endif; ?>

            <!-- ───────────────────────────── -->
            <!-- STEP 3 : INSCRIPTION DATES   -->
            <!-- ───────────────────────────── -->
            <?php if ($tirage && $tirage["date_tirage"]): ?>
            <form method="POST" action="lotteryMaking.php">
                <input type="hidden" name="action" value="set_inscription_dates">
                <label>start of inscriptions date :</label>
                <input type="date" name="date_ouverture" class="inputLotteryMaking"
                       value="<?= $tirage['date_ouverture'] ?? '' ?>">
                <label>end of inscriptions date :</label>
                <input type="date" name="date_fermeture" class="inputLotteryMaking"
                       value="<?= $tirage['date_fermeture'] ?? '' ?>">
                <button type="submit" class="buttonLotteryMaking">Save</button>
            </form>
            <?php endif; ?>

            <!-- ─────────────────────── -->
            <!-- STEP 4 : LAUNCH        -->
            <!-- ─────────────────────── -->
            <?php if ($tirage && $tirage["date_tirage"] === date("Y-m-d")): ?>
            <form method="POST" action="lotteryMaking.php">
                <input type="hidden" name="action" value="launch_tirage">
                <button type="submit" id="buttonLotteryMaking"
                        onclick="return confirm('Lancer le tirage au sort ?')">
                    🎯 Lancer le tirage
                </button>
            </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="../javaScript/lotteryMaking.js"></script>
</body>
</html>