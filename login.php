
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

if (isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
        $error = "Enter a valid email and password.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT student_id, fullname, email, password_hash, role
                 FROM students
                 WHERE email = :email
                 LIMIT 1"
            );

            $stmt->execute(["email" => $email]);
            $user = $stmt->fetch();

            if (
                $user &&
                !empty($user["password_hash"]) &&
                password_verify($password, $user["password_hash"])
            ) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = (int)$user["student_id"];
                $_SESSION["fullname"] = $user["fullname"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["last_activity"] = time();

                $update = $pdo->prepare(
                    "UPDATE students
                     SET last_login = NOW()
                     WHERE student_id = :id"
                );

                $update->execute(["id" => $user["student_id"]]);

                header("Location: dashboard.php");
                exit;
            }

            $error = "Invalid email or password.";
        } catch (PDOException $e) {
            error_log("Login database error: " . $e->getMessage());
            $error = "Login is temporarily unavailable. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>StudentHub</h1>
        <nav><a href="index.html">Home</a> | <a href="register.html">Register</a></nav>
    </header>

    <main>
        <section>
            <h2>Login to StudentHub</h2>

            <?php if (isset($_GET["timeout"])): ?>
                <p role="status">Your session expired due to inactivity. Please log in again.</p>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <p role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       autocomplete="username" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       autocomplete="current-password" required>

                <button type="submit">Login</button>
            </form>

            <p>Don't have an account? <a href="register.html">Register here</a>.</p>
        </section>
    </main>
</body>
</html>
