
<?php
require_once __DIR__ . "/auth.php";
requireLogin();

if ($_SESSION["role"] === "admin") {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SESSION["role"] === "student") {
    header("Location: student_dashboard.php");
    exit;
}

http_response_code(403);
exit("Access denied: invalid user role.");
?>
