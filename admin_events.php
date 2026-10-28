
<?php
require_once __DIR__ . "/auth.php";
requireRole("admin");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = null;
$message = "";
$messageType = "success";
$uploadDir = __DIR__ . "/uploads/events/";
$uploadUrl = "uploads/events/";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function removePoster($filename, $directory) {
    if (!$filename || basename($filename) !== $filename) {
        return;
    }

    $path = $directory . $filename;

    if (is_file($path)) {
        unlink($path);
    }
}

function savePoster($file, $directory) {
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Poster upload failed.");
    }

    if ($file["size"] > 2 * 1024 * 1024) {
        throw new RuntimeException("Poster must be 2 MB or smaller.");
    }

    if (!is_uploaded_file($file["tmp_name"])) {
        throw new RuntimeException("Invalid uploaded file.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowed[$mime]) || getimagesize($file["tmp_name"]) === false) {
        throw new RuntimeException(
            "Only valid JPG, PNG, or WebP images are allowed."
        );
    }

    if (!is_dir($directory) &&
        !mkdir($directory, 0755, true) &&
        !is_dir($directory)) {
        throw new RuntimeException("Could not create the upload directory.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowed[$mime];

    if (!move_uploaded_file($file["tmp_name"], $directory . $filename)) {
        throw new RuntimeException("Could not save the poster.");
    }

    return $filename;
}

try {
    $conn = new mysqli("localhost", "root", "", "studenthub_db");
    $conn->set_charset("utf8mb4");

    // Handle all changes through POST.
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $token = $_POST["csrf_token"] ?? "";

        if (!is_string($token) ||
            !hash_equals($_SESSION["csrf_token"], $token)) {
            throw new RuntimeException(
                "Invalid form token. Refresh the page and try again."
            );
        }

        $action = $_POST["action"] ?? "";

        // DELETE event
        if ($action === "delete") {
            $id = filter_var(
                $_POST["event_id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException("Invalid event ID.");
            }

            $conn->begin_transaction();

            $stmt = $conn->prepare(
                "SELECT poster FROM events WHERE event_id = ? FOR UPDATE"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $event = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$event) {
                $conn->rollback();
                throw new RuntimeException("Event not found.");
            }

            $stmt = $conn->prepare(
                "DELETE FROM events WHERE event_id = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            removePoster($event["poster"], $uploadDir);
            $message = "Event deleted successfully.";
        }

        // ADD or UPDATE event
        elseif ($action === "save") {
            $rawId = trim((string)($_POST["event_id"] ?? ""));
$id = $rawId === "" ? null : filter_var($rawId, FILTER_VALIDATE_INT);

            $name = trim($_POST["event_name"] ?? "");
            $date = trim($_POST["event_date"] ?? "");
            $venue = trim($_POST["venue"] ?? "");
            $category = trim($_POST["category"] ?? "");
            $description = trim($_POST["description"] ?? "");

            if ($id === false || ($id !== null && $id < 1)) {
                throw new RuntimeException("Invalid event ID.");
            }

            if ($name === "" || strlen($name) > 150) {
                throw new RuntimeException("Enter an event name (maximum 150 characters).");
            }

            $dateObject = DateTime::createFromFormat("!Y-m-d", $date);

            if (!$dateObject || $dateObject->format("Y-m-d") !== $date) {
                throw new RuntimeException("Enter a valid event date.");
            }

            if ($venue === "" || strlen($venue) > 150) {
                throw new RuntimeException("Enter a venue (maximum 150 characters).");
            }

            if ($category === "" || strlen($category) > 50) {
                throw new RuntimeException("Enter a category (maximum 50 characters).");
            }

            if (strlen($description) > 5000) {
                throw new RuntimeException("Description must be 5000 characters or fewer.");
            }

            $conn->begin_transaction();
            $oldPoster = null;

            if ($id) {
                $stmt = $conn->prepare(
                    "SELECT poster FROM events WHERE event_id = ? FOR UPDATE"
                );
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$existing) {
                    $conn->rollback();
                    throw new RuntimeException("Event not found.");
                }

                $oldPoster = $existing["poster"];
            }

            $newPoster = savePoster(
                $_FILES["poster"] ?? null,
                $uploadDir
            );

            $poster = $newPoster ?? $oldPoster;

            if ($id) {
                $stmt = $conn->prepare(
                    "UPDATE events
                     SET event_name = ?, event_date = ?, venue = ?,
                         category = ?, description = ?, poster = ?
                     WHERE event_id = ?"
                );

                $stmt->bind_param(
                    "ssssssi",
                    $name,
                    $date,
                    $venue,
                    $category,
                    $description,
                    $poster,
                    $id
                );

                $stmt->execute();
                $stmt->close();
                $message = "Event updated successfully.";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO events
                     (event_name, event_date, venue, category, description, poster)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssssss",
                    $name,
                    $date,
                    $venue,
                    $category,
                    $description,
                    $poster
                );

                $stmt->execute();
                $stmt->close();
                $message = "Event added successfully.";
            }

            $conn->commit();

            if ($newPoster !== null && $oldPoster !== null) {
                removePoster($oldPoster, $uploadDir);
            }
        } else {
            throw new RuntimeException("Unknown action.");
        }
    }
} catch (Throwable $ex) {
    if ($conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }

    if ($ex instanceof mysqli_sql_exception) {
        error_log("Event management database error: " . $ex->getMessage());
        $message = "A database error occurred. Please check the event data.";
    } else {
        $message = $ex->getMessage();
    }

    $messageType = "error";
} finally {
    if ($conn instanceof mysqli) {
        $conn->close();
    }
}

// Load events for display and edit.
$events = [];
$editEvent = null;

try {
    $conn = new mysqli("localhost", "root", "", "studenthub_db");
    $conn->set_charset("utf8mb4");

    $stmt = $conn->prepare(
        "SELECT event_id, event_name, event_date, venue,
                category, description, poster
         FROM events ORDER BY event_date DESC, event_id DESC"
    );
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $editId = filter_var($_GET["edit"] ?? null, FILTER_VALIDATE_INT);

    if ($editId) {
        foreach ($events as $event) {
            if ((int)$event["event_id"] === $editId) {
                $editEvent = $event;
                break;
            }
        }
    }

    $conn->close();
} catch (Throwable $ex) {
    error_log("Event listing error: " . $ex->getMessage());
    $message = "Could not load event records. Check your database connection.";
    $messageType = "error";

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
    <title>Event Management - StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .event-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .event-form .full-width {
            grid-column: 1 / -1;
        }

        .event-message {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .event-message.success {
            background: #e4f7e8;
            color: #185b2a;
        }

        .event-message.error {
            background: #fde8e8;
            color: #8a1c1c;
        }

        .event-table-wrap {
            overflow-x: auto;
        }

        .event-table-wrap table {
            min-width: 750px;
        }

        .event-poster {
            max-width: 110px;
            max-height: 85px;
            object-fit: contain;
        }

        .event-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .event-actions form {
            margin: 0;
        }

        .event-actions button,
        .event-actions a {
            padding: 7px 10px;
            background: #2c3e50;
            color: white;
            border: 0;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
<header>
    <h1>StudentHub - Event Management</h1>
</header>

<main>
    <section>
        <h2><?= $editEvent ? "Edit Event" : "Add Event" ?></h2>

        <?php if ($message !== ""): ?>
            <p class="event-message <?= e($messageType) ?>" role="status">
                <?= e($message) ?>
            </p>
        <?php endif; ?>

        <form action="admin_events.php" method="POST"
              enctype="multipart/form-data" class="event-form">

            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="event_id"
                   value="<?= e($editEvent["event_id"] ?? "") ?>">

            <div>
                <label for="event_name">Event Name</label>
                <input id="event_name" name="event_name" maxlength="150"
                       required value="<?= e($editEvent["event_name"] ?? "") ?>">
            </div>

            <div>
                <label for="event_date">Event Date</label>
                <input id="event_date" name="event_date" type="date"
                       required value="<?= e($editEvent["event_date"] ?? "") ?>">
            </div>

            <div>
                <label for="venue">Venue</label>
                <input id="venue" name="venue" maxlength="150"
                       required value="<?= e($editEvent["venue"] ?? "") ?>">
            </div>

            <div>
                <label for="category">Category</label>
                <input id="category" name="category" maxlength="50"
                       required value="<?= e($editEvent["category"] ?? "") ?>">
            </div>

            <div class="full-width">
                <label for="description">Description</label>
                <textarea id="description" name="description"
                          rows="4" maxlength="5000"><?= e($editEvent["description"] ?? "") ?></textarea>
            </div>

            <div class="full-width">
                <label for="poster">Event Poster (JPG, PNG, WebP; maximum 2 MB)</label>
                <input id="poster" name="poster" type="file"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                <?php if (!empty($editEvent["poster"])): ?>
                    <p>Current poster:</p>
                    <img class="event-poster"
                         src="<?= e($uploadUrl . $editEvent["poster"]) ?>"
                         alt="Current event poster">
                    <p>Leave the upload field empty to keep the current poster.</p>
                <?php endif; ?>
            </div>

            <div class="full-width">
                <button type="submit">
                    <?= $editEvent ? "Update Event" : "Add Event" ?>
                </button>

                <?php if ($editEvent): ?>
                    <a href="admin_events.php">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section>
        <h2>Existing Events</h2>

        <div class="event-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Category</th>
                        <th>Poster</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($events)): ?>
                    <tr><td colspan="7">No events found.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?= (int)$event["event_id"] ?></td>
                            <td><?= e($event["event_name"]) ?></td>
                            <td><?= e($event["event_date"]) ?></td>
                            <td><?= e($event["venue"]) ?></td>
                            <td><?= e($event["category"]) ?></td>
                            <td>
                                <?php if (!empty($event["poster"])): ?>
                                    <img class="event-poster"
                                         src="<?= e($uploadUrl . $event["poster"]) ?>"
                                         alt="Poster for <?= e($event["event_name"]) ?>">
                                <?php else: ?>
                                    No poster
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="event-actions">
                                    <a href="admin_events.php?edit=<?= (int)$event["event_id"] ?>">
                                        Edit
                                    </a>

                                    <form method="POST" action="admin_events.php"
                                          onsubmit="return confirm('Delete this event?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($_SESSION["csrf_token"]) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="event_id"
                                               value="<?= (int)$event["event_id"] ?>">
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
</body>
</html>
