
<?php
require_once __DIR__ . "/db.php";

try {
    $stmt = $pdo->query("SELECT DATABASE() AS database_name");
    $result = $stmt->fetch();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Test</title>
    </head>
    <body>
        <h1>StudentHub Database Test</h1>
        <p>Database connection successful!</p>
        <p>Connected database:
            <?= htmlspecialchars($result["database_name"], ENT_QUOTES, "UTF-8") ?>
        </p>
    </body>
    </html>
    <?php
} catch (PDOException $e) {
    error_log("Database test failed: " . $e->getMessage());
    http_response_code(500);
    echo "Database query failed.";
}
?>
