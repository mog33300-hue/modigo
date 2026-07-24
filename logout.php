<?php

session_start();

/* =====================================================
   CLEAR SESSION
===================================================== */

$_SESSION = [];

/* =====================================================
   DELETE COOKIE
===================================================== */

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* =====================================================
   DESTROY
===================================================== */

session_destroy();

/* =====================================================
   REDIRECTION
===================================================== */

header("Location: login.php");

exit;

?>