<?php
session_start();



require_once "config.php";

// =========================
// FETCH LAST WINNERS
// =========================
$lastWinners = $conn->query("
    SELECT u.nom_prenom, t.date_tirage
    FROM gagnants g
    JOIN users u ON g.user_id = u.id
    JOIN tirages t ON g.tirage_id = t.id
    WHERE t.status = 'done'
    ORDER BY t.date_tirage DESC
    LIMIT 6
");
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../cssFiles/landingPageStyle.css">
</head>
<body>

<div id="landingPage">

    <!-- ─────────────────────── -->
    <!-- PART 1 : HERO           -->
    <!-- ─────────────────────── -->
    <div id="part1Landing">
        <div id="blured">
            <h1 id="landingPageTitle">the hadj lottery</h1>
            <p id="landingPara">
                Your Journey to Mecca Begins Here.
                Millions dream of performing Hajj, but only a few are chosen each year.
                Register now for your chance to answer the call and embark on the journey of a lifetime.
            </p>
            <div id="buttons">
                <button id="loginButton"
                        onclick="window.location='login.php'">
                    login
                </button>
                <button id="signInButton"
                      onclick="window.location='signIn.php'">
                    sign in
                </button>
            </div>
        </div>
    </div>

    <!-- ─────────────────────── -->
    <!-- PART 2 : RULES          -->
    <!-- ─────────────────────── -->
    <div id="part2Landing">
        <div id="modalitésInscription">
            <h2>Modalités d'inscription</h2>
            <p class="landingP">• Une personne peut créer un compte.</p>
            <p class="landingP">• Le compte doit être validé par un administrateur.</p>
            <p class="landingP">• Une personne avec un compte validé peut s'inscrire à un tirage uniquement si les inscriptions sont ouvertes.</p>
            <p class="landingP">• Une personne avec un compte validé peut s'inscrire à un tirage uniquement si elle a au moins 18 ans le jour du tirage.</p>
            <p class="landingP">• Une personne avec un compte validé peut s'inscrire à un tirage uniquement si elle n'est pas bloquée.</p>
            <p class="landingP">• Des notifications sont envoyées aux utilisateurs lors de l'ouverture des inscriptions.</p>
        </div>

        <div id="theRules">
            <h2>Les règles du tirage</h2>
            <p class="landingP">• L'administrateur ne peut fixer la date du tirage qu'après avoir fixé le nombre de gagnants.</p>
            <p class="landingP">• L'administrateur ne peut fixer les dates d'inscription qu'après avoir fixé la date du tirage.</p>
            <p class="landingP">• On ne peut ouvrir un nouveau tirage qu'après la finalisation du dernier tirage.</p>
            <p class="landingP">• Le tirage ne peut être lancé qu'à la date fixée.</p>
            <p class="landingP">• Le tirage produit une liste de gagnants uniques.</p>
            <p class="landingP">• Une personne gagnante ne peut pas participer aux 5 tirages suivants.</p>
            <p class="landingP">• Une personne non gagnante apparaît autant de fois qu'elle s'est inscrite sans gagner.</p>
            <p class="landingP">• Des notifications sont envoyées après le tirage.</p>
        </div>
    </div>

    <!-- ─────────────────────── -->
    <!-- PART 3 : LAST WINNERS   -->
    <!-- ─────────────────────── -->
    <h3 id="winnerLandingTitle">the last winners :</h3>
    <div id="lastYearsWinners">
        <?php if ($lastWinners->num_rows === 0): ?>
            <p>Aucun tirage effectué pour le moment</p>
        <?php else: ?>
            <?php while ($winner = $lastWinners->fetch_assoc()): ?>
                <div class="winner">
                    <h4 class="winnerName">
                        <?= htmlspecialchars($winner["nom_prenom"]) ?>
                    </h4>
                    <div class="yearOfWin">
                        <?= date("Y", strtotime($winner["date_tirage"])) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

</div>


</body>
</html>