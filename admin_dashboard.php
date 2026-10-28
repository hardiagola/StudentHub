
<?php
require_once __DIR__ . "/auth.php";
requireRole("admin");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>StudentHub Administration</h1>
    </header>

    <main>
        <section>
            <h2>Admin Dashboard</h2>
            <p>Welcome, <?= htmlspecialchars($_SESSION["fullname"], ENT_QUOTES, "UTF-8") ?>!</p>
            <p>You are logged in as an administrator.</p>

            <form action="logout.php" method="POST">
                <button type="submit">Logout</button>
            </form>
        </section>
    </main>
</body>
</html>
