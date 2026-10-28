
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = null;
$message = "";
$messageType = "success";
$errors = [];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

try {
    $conn = new mysqli("localhost", "root", "", "studenthub_db");
    $conn->set_charset("utf8mb4");

    // DELETE operation
    if ($_SERVER["REQUEST_METHOD"] === "POST" &&
        ($_POST["action"] ?? "") === "delete") {

        $id = filter_var(
            $_POST["student_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            $message = "Invalid student ID.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare(
                "DELETE FROM students WHERE student_id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $message = "Student deleted successfully.";
            } else {
                $message = "Student record was not found.";
                $messageType = "error";
            }

            $stmt->close();
        }
    }

    // INSERT and UPDATE operations
    if ($_SERVER["REQUEST_METHOD"] === "POST" &&
        ($_POST["action"] ?? "") === "save") {

        $id = filter_var(
            $_POST["student_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $fullname = trim($_POST["fullname"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $mobile = trim($_POST["mobile"] ?? "");
        $enrollment = trim($_POST["enrollment"] ?? "");
        $course = trim($_POST["course"] ?? "");
        $year = filter_var(
            $_POST["year"] ?? null,
            FILTER_VALIDATE_INT
        );
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
            $errors[] = "Select a valid academic year.";
        }

        $allowedGenders = [
            "Female", "Male", "Other", "Prefer not to say"
        ];

        if (!in_array($gender, $allowedGenders, true)) {
            $errors[] = "Select a valid gender.";
        }

        // Check duplicate email or enrollment, excluding the current record.
        if (empty($errors)) {
            if ($id) {
                $check = $conn->prepare(
                    "SELECT student_id FROM students
                     WHERE (email = ? OR enrollment = ?)
                     AND student_id <> ?"
                );
                $check->bind_param("ssi", $email, $enrollment, $id);
            } else {
                $check = $conn->prepare(
                    "SELECT student_id FROM students
                     WHERE email = ? OR enrollment = ?"
                );
                $check->bind_param("ss", $email, $enrollment);
            }

            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $errors[] = "Email or enrollment number already exists.";
            }

            $check->close();
        }

        if (empty($errors)) {
            if ($id) {
                // UPDATE
                $stmt = $conn->prepare(
                    "UPDATE students
                     SET fullname = ?, email = ?, mobile = ?,
                         enrollment = ?, course = ?, year = ?, gender = ?
                     WHERE student_id = ?"
                );

                $stmt->bind_param(
                    "sssssisi",
                    $fullname,
                    $email,
                    $mobile,
                    $enrollment,
                    $course,
                    $year,
                    $gender,
                    $id
                );

                $stmt->execute();

                if ($stmt->affected_rows >= 0) {
                    $message = "Student updated successfully.";
                }

                $stmt->close();
            } else {
                // INSERT
                $stmt = $conn->prepare(
                    "INSERT INTO students
                     (fullname, email, mobile, enrollment,
                      course, year, gender)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssssis",
                    $fullname,
                    $email,
                    $mobile,
                    $enrollment,
                    $course,
                    $year,
                    $gender
                );

                $stmt->execute();
                $message = "Student added successfully.";
                $stmt->close();
            }
        } else {
            $message = implode(" ", $errors);
            $messageType = "error";
        }
    }

    // Search and course filter
    $search = trim($_GET["search"] ?? "");
    $courseFilter = trim($_GET["course"] ?? "");

    $sql = "SELECT student_id, fullname, email, mobile,
                   enrollment, course, year, gender
            FROM students
            WHERE (
                fullname LIKE ?
                OR email LIKE ?
                OR course LIKE ?
            )";

    $searchTerm = "%" . $search . "%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = "sss";

    if ($courseFilter !== "") {
        $sql .= " AND course = ?";
        $params[] = $courseFilter;
        $types .= "s";
    }

    $sql .= " ORDER BY student_id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Load courses for the filter dropdown.
    $courseResult = $conn->query(
        "SELECT DISTINCT course FROM students ORDER BY course"
    );
    $courses = $courseResult->fetch_all(MYSQLI_ASSOC);

} catch (mysqli_sql_exception $ex) {
    error_log("Student management error: " . $ex->getMessage());
    http_response_code(500);
    $message = "A database error occurred. Please check your database configuration.";
    $messageType = "error";
    $students = $students ?? [];
    $courses = $courses ?? [];
} finally {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}

$editId = filter_var($_GET["edit"] ?? null, FILTER_VALIDATE_INT);
$editStudent = null;

if ($editId) {
    foreach ($students as $student) {
        if ((int)$student["student_id"] === $editId) {
            $editStudent = $student;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .crud-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .crud-form .full-width {
            grid-column: 1 / -1;
        }

        .crud-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .crud-actions form {
            margin: 0;
        }

        .crud-actions button,
        .crud-actions a {
            display: inline-block;
            padding: 7px 12px;
            border: none;
            border-radius: 4px;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .crud-message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        .crud-message.success {
            background: #e4f7e8;
            color: #185b2a;
        }

        .crud-message.error {
            background: #fde8e8;
            color: #8a1c1c;
        }

        .crud-search {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: end;
        }

        .crud-search .search-field {
            flex: 1;
            min-width: 180px;
        }

        .crud-search button,
        .crud-search a {
            padding: 9px 14px;
            text-decoration: none;
        }

        .table-container {
            overflow-x: auto;
        }

        .table-container table {
            min-width: 850px;
        }
    </style>
</head>
<body>
<header>
    <h1>StudentHub</h1>
    <nav aria-label="Main navigation">
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="register.html">Register</a></li>
            <li><a href="events.html">Events</a></li>
        </ul>
    </nav>
</header>

<main>
    <section>
        <h2><?= $editStudent ? "Edit Student" : "Add Student" ?></h2>

        <?php if ($message !== ""): ?>
            <div class="crud-message <?= e($messageType) ?>" role="status">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <form action="students.php" method="POST" class="crud-form">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="student_id"
                   value="<?= e($editStudent["student_id"] ?? "") ?>">

            <div>
                <label for="fullname">Full Name</label>
                <input id="fullname" name="fullname" required
                       value="<?= e($editStudent["fullname"] ?? "") ?>">
            </div>

            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required
                       value="<?= e($editStudent["email"] ?? "") ?>">
            </div>

            <div>
                <label for="mobile">Mobile Number</label>
                <input id="mobile" name="mobile" type="tel"
                       maxlength="10" required
                       value="<?= e($editStudent["mobile"] ?? "") ?>">
            </div>

            <div>
                <label for="enrollment">Enrollment Number</label>
                <input id="enrollment" name="enrollment" required
                       value="<?= e($editStudent["enrollment"] ?? "") ?>">
            </div>

            <div>
                <label for="course">Course</label>
                <select id="course" name="course" required>
                    <option value="">Select course</option>
                    <?php foreach (["BTech AIML", "BTech CE", "BTech IT", "Other"] as $c): ?>
                        <option value="<?= e($c) ?>"
                            <?= ($editStudent["course"] ?? "") === $c ? "selected" : "" ?>>
                            <?= e($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="year">Academic Year</label>
                <select id="year" name="year" required>
                    <option value="">Select year</option>
                    <?php for ($y = 1; $y <= 4; $y++): ?>
                        <option value="<?= $y ?>"
                            <?= (string)($editStudent["year"] ?? "") === (string)$y ? "selected" : "" ?>>
                            Year <?= $y ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">Select gender</option>
                    <?php foreach (["Female", "Male", "Other", "Prefer not to say"] as $g): ?>
                        <option value="<?= e($g) ?>"
                            <?= ($editStudent["gender"] ?? "") === $g ? "selected" : "" ?>>
                            <?= e($g) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="full-width crud-actions">
                <button type="submit">
                    <?= $editStudent ? "Update Student" : "Add Student" ?>
                </button>
                <?php if ($editStudent): ?>
                    <a href="students.php">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section>
        <h2>Manage Students</h2>

        <form action="students.php" method="GET" class="crud-search">
            <div class="search-field">
                <label for="search">Search name, email, or course</label>
                <input type="search" id="search" name="search"
                       value="<?= e($search ?? "") ?>"
                       placeholder="Enter search term">
            </div>

            <div>
                <label for="courseFilter">Filter by course</label>
                <select id="courseFilter" name="course">
                    <option value="">All courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= e($c["course"]) ?>"
                            <?= ($courseFilter ?? "") === $c["course"] ? "selected" : "" ?>>
                            <?= e($c["course"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit">Search / Filter</button>
            <a href="students.php">Clear</a>
        </form>

        <p>Total records displayed: <?= count($students ?? []) ?></p>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Enrollment</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="9">No student records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= (int)$student["student_id"] ?></td>
                            <td><?= e($student["fullname"]) ?></td>
                            <td><?= e($student["email"]) ?></td>
                            <td><?= e($student["mobile"]) ?></td>
                            <td><?= e($student["enrollment"]) ?></td>
                            <td><?= e($student["course"]) ?></td>
                            <td><?= (int)$student["year"] ?></td>
                            <td><?= e($student["gender"]) ?></td>
                            <td>
                                <div class="crud-actions">
                                    <a href="students.php?edit=<?= (int)$student["student_id"] ?>">
                                        Edit
                                    </a>

                                    <form action="students.php" method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this student?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="student_id"
                                               value="<?= (int)$student["student_id"] ?>">
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<footer>
    <p>&copy; 2026 StudentHub. All rights reserved.</p>
</footer>
</body>
</html>
