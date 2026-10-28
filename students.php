
<?php
require_once __DIR__ . "/db.php";

$message = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $enrollment = trim($_POST["enrollment"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $year = filter_input(INPUT_POST, "year", FILTER_VALIDATE_INT);
    $gender = trim($_POST["gender"] ?? "");

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

    if ($course === "" || strlen($course) > 100) {
        $errors[] = "Enter a valid course.";
    }

    if ($year === false || $year < 1 || $year > 4) {
        $errors[] = "Select an academic year from 1 to 4.";
    }

    $allowedGenders = ["Female", "Male", "Other", "Prefer not to say"];

    if (!in_array($gender, $allowedGenders, true)) {
        $errors[] = "Select a valid gender.";
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO students
                    (fullname, email, mobile, enrollment, course, year, gender)
                    VALUES
                    (:fullname, :email, :mobile, :enrollment, :course, :year, :gender)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "fullname" => $fullname,
                "email" => $email,
                "mobile" => $mobile,
                "enrollment" => $enrollment,
                "course" => $course,
                "year" => $year,
                "gender" => $gender
            ]);

            $message = "Student record saved successfully.";
        } catch (PDOException $e) {
            error_log("Student insert failed: " . $e->getMessage());

            if ($e->getCode() === "23000") {
                $errors[] = "Email or enrollment number already exists.";
            } else {
                $errors[] = "Unable to save the student record.";
            }
        }
    }
}

$stmt = $pdo->query(
    "SELECT student_id, fullname, email, enrollment, course, year
     FROM students
     ORDER BY student_id DESC"
);

$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records - StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1>StudentHub</h1>
    <nav><a href="index.html">Home</a> | <a href="register.html">Register</a></nav>
</header>

<main>
    <section>
        <h2>Add Student</h2>

        <?php if ($message !== ""): ?>
            <p role="status">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <?php if ($errors): ?>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="students.php">
            <label for="fullname">Full Name</label>
            <input id="fullname" name="fullname" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>

            <label for="mobile">Mobile Number</label>
            <input id="mobile" name="mobile" type="tel" required>

            <label for="enrollment">Enrollment Number</label>
            <input id="enrollment" name="enrollment" required>

            <label for="course">Course</label>
            <input id="course" name="course" required>

            <label for="year">Academic Year</label>
            <select id="year" name="year" required>
                <option value="">Select year</option>
                <option value="1">First Year</option>
                <option value="2">Second Year</option>
                <option value="3">Third Year</option>
                <option value="4">Fourth Year</option>
            </select>

            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="">Select gender</option>
                <option value="Female">Female</option>
                <option value="Male">Male</option>
                <option value="Other">Other</option>
                <option value="Prefer not to say">Prefer not to say</option>
            </select>

            <button type="submit">Save Student</button>
        </form>
    </section>

    <section>
        <h2>Student Records</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Enrollment</th>
                        <th>Course</th>
                        <th>Year</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= (int)$student["student_id"] ?></td>
                            <td><?= htmlspecialchars($student["fullname"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($student["email"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($student["enrollment"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($student["course"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= (int)$student["year"] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
