
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$errors = [];
$message = "";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Please submit the registration form using POST.");
}

// Read submitted values
$fullname = trim($_POST["fullname"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$enrollment = trim($_POST["enrollment"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$course = trim($_POST["course"] ?? "");
$year = trim($_POST["year"] ?? "");
$gender = trim($_POST["gender"] ?? "");
$terms = $_POST["terms"] ?? "";

// Server-side validation
if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,99}$/", $fullname)) {
    $errors[] = "Enter a valid full name.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Enter a valid email address.";
}

if (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {
    $errors[] = "Enter a valid 10-digit Indian mobile number.";
}

if ($enrollment === "" || strlen($enrollment) > 30) {
    $errors[] = "Enter a valid enrollment number.";
}

if (
    strlen($password) < 8 ||
    !preg_match("/[A-Z]/", $password) ||
    !preg_match("/[a-z]/", $password) ||
    !preg_match("/[0-9]/", $password) ||
    !preg_match("/[^A-Za-z0-9]/", $password)
) {
    $errors[] = "Password must contain at least 8 characters, uppercase and lowercase letters, a number, and a special character.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

$allowedCourses = ["BTech AIML", "BTech CE", "BTech IT", "Other"];

if (!in_array($course, $allowedCourses, true)) {
    $errors[] = "Select a valid course.";
}

if (!in_array($year, ["1", "2", "3", "4"], true)) {
    $errors[] = "Select a valid academic year.";
}

$allowedGenders = [
    "Female",
    "Male",
    "Other",
    "Prefer not to say"
];

if (!in_array($gender, $allowedGenders, true)) {
    $errors[] = "Select a valid gender.";
}

if ($terms !== "accepted") {
    $errors[] = "You must accept the terms and conditions.";
}

if (empty($errors)) {
    $conn = null;

    try {
        // XAMPP default credentials; change if yours are different.
        $conn = new mysqli(
            "localhost",
            "root",
            "",
            "studenthub_db"
        );

        $conn->set_charset("utf8mb4");

        // Check for duplicate email or enrollment.
        $check = $conn->prepare(
            "SELECT student_id FROM students
             WHERE email = ? OR enrollment = ?
             LIMIT 1"
        );

        $check->bind_param("ss", $email, $enrollment);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "This email or enrollment number is already registered.";
        }

        $check->close();

        if (empty($errors)) {
            // Never store the original password.
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO students
                (fullname, email, mobile, enrollment,
                 course, year, gender, password_hash)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $yearNumber = (int)$year;

            $stmt->bind_param(
                "sssssiss",
                $fullname,
                $email,
                $mobile,
                $enrollment,
                $course,
                $yearNumber,
                $gender,
                $passwordHash
            );

            $stmt->execute();
            $stmt->close();

            $message = "Registration successful! Your details have been saved.";
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Registration database error: " . $e->getMessage());

        if ((int)$e->getCode() === 1062) {
            $errors[] = "This email or enrollment number is already registered.";
        } else {
            $errors[] = "Registration could not be completed. Please try again.";
        }
    } finally {
        if ($conn instanceof mysqli) {
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Result - StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>StudentHub</h1>
    </header>

    <main>
        <section>
            <?php if (!empty($errors)): ?>
                <h2>Registration Failed</h2>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><a href="register.html">Return to Registration</a></p>
            <?php else: ?>
                <h2>Registration Successful</h2>
                <p><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>
                <p><a href="index.html">Return to Home</a></p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
