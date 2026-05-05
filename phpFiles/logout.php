<?php
session_start();

// remove all session variables
session_unset();

// destroy the session completely
session_destroy();

// optional safety (clear cookie)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// redirect to landing page
header("Location: landingPage.php");
exit;
?>