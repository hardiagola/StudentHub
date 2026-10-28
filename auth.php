
<?php
// Configure the session cookie before starting the session.
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set("session.use_strict_mode", "1");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"]) &&
                    $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

// Allow 30 minutes of inactivity.
$idleTimeout = 1800;

function requireLogin(): void {
    global $idleTimeout;

    if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
        header("Location: login.php");
        exit;
    }

    if (
        isset($_SESSION["last_activity"]) &&
        time() - $_SESSION["last_activity"] > $idleTimeout
    ) {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                [
                    "expires" => time() - 42000,
                    "path" => $params["path"],
                    "domain" => $params["domain"],
                    "secure" => $params["secure"],
                    "httponly" => $params["httponly"],
                    "samesite" => "Lax"
                ]
            );
        }

        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }

    $_SESSION["last_activity"] = time();
}

function requireRole(string $role): void {
    requireLogin();

    if ($_SESSION["role"] !== $role) {
        http_response_code(403);
        exit("Access denied: you are not authorized to view this page.");
    }
}
?>
