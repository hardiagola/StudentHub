
<?php
require_once __DIR__ . "/auth.php";
requireRole("admin");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = null;
$error = "";
$studentCount = 0;
$eventCount = 0;
$registrationCount = 0;
$latestRegistrations = [];
$auditLogs = [];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

try {
    $conn = new mysqli("localhost", "root", "", "studenthub_db");
    $conn->set_charset("utf8mb4");

    // Create an audit log for opening the admin dashboard.
    $userId = (int)$_SESSION["user_id"];
    $action = "VIEW_ADMIN_DASHBOARD";
    $details = "Administrator opened the dashboard.";

    $log = $conn->prepare(
        "INSERT INTO audit_logs (user_id, action, details)
         VALUES (?, ?, ?)"
    );
    $log->bind_param("iss", $userId, $action, $details);
    $log->execute();
    $log->close();

    // Student count.
    $result = $conn->query("SELECT COUNT(*) AS total FROM students");
    $studentCount = (int)$result->fetch_assoc()["total"];

    // Event count.
    $result = $conn->query("SELECT COUNT(*) AS total FROM events");
    $eventCount = (int)$result->fetch_assoc()["total"];

    // Registration count.
    $result = $conn->query("SELECT COUNT(*) AS total FROM registrations");
    $registrationCount = (int)$result->fetch_assoc()["total"];

    // Latest registrations.
    // Assumes registrations has registration_id, student_id,
    // event_id, and registration_date columns.
    $sql = "
        SELECT r.registration_id,
               r.registration_date,
               s.fullname,
               e.event_name
        FROM registrations r
        LEFT JOIN students s ON r.student_id = s.student_id
        LEFT JOIN events e ON r.event_id = e.event_id
        ORDER BY r.registration_id DESC
        LIMIT 10
    ";

    $result = $conn->query($sql);
    $latestRegistrations = $result->fetch_all(MYSQLI_ASSOC);

    // Latest audit entries.
    $result = $conn->query(
        "SELECT action, details, created_at
         FROM audit_logs
         ORDER BY log_id DESC
         LIMIT 10"
    );
    $auditLogs = $result->fetch_all(MYSQLI_ASSOC);

} catch (Throwable $ex) {
    error_log("Admin dashboard error: " . $ex->getMessage());
    $error = "Some dashboard information could not be loaded. Check the database schema and connection.";
} finally {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudentHub</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
        }

        .stat-card {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2c3e50;
        }

        .stat-card h3 {
            margin-top: 0;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0;
        }

        .dashboard-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .dashboard-nav a {
            display: inline-block;
            padding: 10px 14px;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .dashboard-table-wrap {
            overflow-x: auto;
        }

        .dashboard-table-wrap table {
            min-width: 600px;
        }

        .dashboard-error {
            background: #fde8e8;
            color: #8a1c1c;
            padding: 12px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<header>
    <h1>StudentHub Administration</h1>
    <nav aria-label="Admin navigation">
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="students.php">Manage Students</a></li>
            <li><a href="admin_events.php">Manage Events</a></li>
            <li><a href="events_ajax.html">AJAX Events</a></li>
        </ul>
    </nav>
</header>

<main>
    <section>
        <h2>Welcome, <?= e($_SESSION["fullname"] ?? "Administrator") ?>!</h2>
        <p>You are logged in as an administrator.</p>

        <?php if ($error !== ""): ?>
            <p class="dashboard-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>

        <div class="dashboard-nav">
            <a href="students.php">Manage Students</a>
            <a href="admin_events.php">Manage Events</a>
            <a href="events_ajax.html">AJAX Event Management</a>
        </div>
    </section>

    <section>
        <h2>Dashboard Statistics</h2>

        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Students</h3>
                <p class="stat-number"><?= $studentCount ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Events</h3>
                <p class="stat-number"><?= $eventCount ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Registrations</h3>
                <p class="stat-number"><?= $registrationCount ?></p>
            </div>
        </div>
    </section>

    <section>
        <h2>Latest Registrations</h2>

        <div class="dashboard-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Registration ID</th>
                        <th>Student</th>
                        <th>Event</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($latestRegistrations)): ?>
                    <tr>
                        <td colspan="4">No registrations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($latestRegistrations as $registration): ?>
                        <tr>
                            <td><?= (int)$registration["registration_id"] ?></td>
                            <td><?= e($registration["fullname"] ?? "Unknown student") ?></td>
                            <td><?= e($registration["event_name"] ?? "Unknown event") ?></td>
                            <td><?= e($registration["registration_date"] ?? "Not available") ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h2>Recent Audit Logs</h2>

        <div class="dashboard-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Date and Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($auditLogs)): ?>
                    <tr>
                        <td colspan="3">No audit entries found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><?= e($log["action"]) ?></td>
                            <td><?= e($log["details"]) ?></td>
                            <td><?= e($log["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <form action="logout.php" method="POST">
            <button type="submit">Logout</button>
        </form>
    </section>
</main>

<footer>
    <p>StudentHub Administration</p>
</footer>
</body>
</html>
