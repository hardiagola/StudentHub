
<?php
$errors = [];
$message = "";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Please submit the registration form using POST.");
}

// Read form values
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
if (!preg_match("/^[A-Za-z][A-Za-z '\-]{1,59}$/", $fullname)) {
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
    $errors[] = "Please select a valid course.";
}

if (!in_array($year, ["1", "2", "3", "4"], true)) {
    $errors[] = "Please select a valid academic year.";
}

$allowedGenders = [
    "Female",
    "Male",
    "Other",
    "Prefer not to say"
];

if (!in_array($gender, $allowedGenders, true)) {
    $errors[] = "Please select a gender option.";
}

if ($terms !== "accepted") {
    $errors[] = "You must accept the terms and conditions.";
}

// Save valid registrations to JSON
if (empty($errors)) {
    $dataDirectory = __DIR__ . "/data";

    if (!is_dir($dataDirectory)) {
        if (!mkdir($dataDirectory, 0700, true) &&
            !is_dir($dataDirectory)) {
            $errors[] = "Unable to create the data directory.";
        }
    }

    $file = $dataDirectory . "/registrations.json";

    if (empty($errors)) {
        $handle = fopen($file, "c+");

        if ($handle === false) {
            $errors[] = "Unable to open the JSON storage file.";
        } elseif (!flock($handle, LOCK_EX)) {
            fclose($handle);
            $errors[] = "Unable to lock the JSON storage file.";
        } else {
            rewind($handle);
            $contents = stream_get_contents($handle);
            $records = [];

            if ($contents === false) {
                $errors[] = "Unable to read the JSON storage file.";
            } elseif (trim($contents) !== "") {
                $decoded = json_decode($contents, true);

                if (json_last_error() !== JSON_ERROR_NONE ||
                    !is_array($decoded)) {
                    $errors[] = "The JSON file contains invalid data.";
                } else {
                    $records = $decoded;
                }
            }

            if (empty($errors)) {
                $records[] = [
                    "fullname" => $fullname,
                    "email" => $email,
                    "mobile" => $mobile,
                    "enrollment" => $enrollment,
                    "password_hash" => password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),
                    "course" => $course,
                    "year" => $year,
                    "gender" => $gender,
                    "registered_at" => date("Y-m-d H:i:s")
                ];

                $json = json_encode(
                    $records,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                );

                if ($json === false) {
                    $errors[] = "Unable to convert registration data to JSON.";
                } else {
                    rewind($handle);

                    if (!ftruncate($handle, 0)) {
                        $errors[] = "Unable to prepare the storage file.";
                    } elseif (fwrite($handle, $json . PHP_EOL) === false ||
                              !fflush($handle)) {
                        $errors[] = "Unable to save the registration.";
                    } else {
                        $message = "Registration submitted successfully!";
                    }
                }
            }

            flock($handle, LOCK_UN);
            fclose($handle);
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
                        <li><?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?></li>
                    <?php endforeach; ?>
                </ul>

                <p><a href="register.html">Return to Registration</a></p>
            <?php else: ?>
                <h2>Registration Successful</h2>

                <p><?= htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?></p>

                <p><a href="index.html">Return to Home</a></p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
