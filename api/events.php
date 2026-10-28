
<?php
// StudentHub JSON API for event CRUD.

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

ini_set("session.use_strict_mode", "1");
session_start();

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// Only logged-in administrators can manage events.
if (
    !isset($_SESSION["user_id"], $_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    respond(401, [
        "success" => false,
        "message" => "Please log in as an administrator."
    ]);
}

// Allow only GET and POST.
$method = $_SERVER["REQUEST_METHOD"];

if (!in_array($method, ["GET", "POST"], true)) {
    header("Allow: GET, POST");
    respond(405, [
        "success" => false,
        "message" => "Method not allowed."
    ]);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli("localhost", "root", "", "studenthub_db");
    $conn->set_charset("utf8mb4");

    // GET: retrieve all events.
    if ($method === "GET") {
        $stmt = $conn->prepare(
            "SELECT event_id, event_name, event_date, venue,
                    category, description, poster
             FROM events
             ORDER BY event_id DESC"
        );

        $stmt->execute();
        $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $conn->close();

        respond(200, [
            "success" => true,
            "message" => "Events retrieved successfully.",
            "events" => $events
        ]);
    }

    // POST: read and validate JSON.
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
        $conn->close();
        respond(400, [
            "success" => false,
            "message" => "Invalid JSON request."
        ]);
    }

    $action = $data["action"] ?? "";

    // Validate fields for adding or updating an event.
    if (in_array($action, ["add", "update"], true)) {
        $name = trim((string)($data["event_name"] ?? ""));
        $date = trim((string)($data["event_date"] ?? ""));
        $venue = trim((string)($data["venue"] ?? ""));
        $category = trim((string)($data["category"] ?? ""));
        $description = trim((string)($data["description"] ?? ""));

        if ($name === "" || strlen($name) > 150) {
            $conn->close();
            respond(422, [
                "success" => false,
                "message" => "Event name is required (maximum 150 characters)."
            ]);
        }

        $dateObject = DateTime::createFromFormat("!Y-m-d", $date);

        if (!$dateObject || $dateObject->format("Y-m-d") !== $date) {
            $conn->close();
            respond(422, [
                "success" => false,
                "message" => "Enter a valid event date."
            ]);
        }

        if ($venue === "" || strlen($venue) > 150) {
            $conn->close();
            respond(422, [
                "success" => false,
                "message" => "Venue is required (maximum 150 characters)."
            ]);
        }

        if ($category === "" || strlen($category) > 50) {
            $conn->close();
            respond(422, [
                "success" => false,
                "message" => "Category is required (maximum 50 characters)."
            ]);
        }

        if (strlen($description) > 5000) {
            $conn->close();
            respond(422, [
                "success" => false,
                "message" => "Description must be 5000 characters or fewer."
            ]);
        }
    }

    // ADD: insert a new event.
    if ($action === "add") {
        $stmt = $conn->prepare(
            "INSERT INTO events
             (event_name, event_date, venue, category, description)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssss",
            $name,
            $date,
            $venue,
            $category,
            $description
        );

        $stmt->execute();
        $newId = $conn->insert_id;
        $stmt->close();
        $conn->close();

        respond(201, [
            "success" => true,
            "message" => "Event added successfully.",
            "event_id" => $newId
        ]);
    }

    // UPDATE: modify an existing event.
    if ($action === "update") {
        $id = filter_var($data["event_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $conn->close();
            respond(422, [
                "success" => false,
                "message" => "A valid event ID is required."
            ]);
        }

        $stmt = $conn->prepare(
            "UPDATE events
             SET event_name = ?, event_date = ?, venue = ?,
                 category = ?, description = ?
             WHERE event_id = ?"
        );

        $stmt->bind_param(
            "sssssi",
            $name,
            $date,
            $venue,
            $category,
            $description,
            $id
        );

        $stmt->execute();
        $found = $stmt->affected_rows > 0;

        if (!$found) {
            $check = $conn->prepare(
                "SELECT event_id FROM events WHERE event_id = ?"
            );
            $check->bind_param("i", $id);
            $check->execute();
            $found = $check->get_result()->num_rows > 0;
            $check->close();
        }

        $stmt->close();
        $conn->close();

        if (!$found) {
            respond(404, [
                "success" => false,
                "message" => "Event not found."
            ]);
        }

        respond(200, [
            "success" => true,
            "message" => "Event updated successfully."
        ]);
    }

    // DELETE: remove an event by ID.
    if ($action === "delete") {
        $id = filter_var($data["event_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            $conn->close();
            respond(422, [
                "success" => false,
                "message" => "A valid event ID is required."
            ]);
        }

        $stmt = $conn->prepare(
            "DELETE FROM events WHERE event_id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();
        $conn->close();

        if (!$deleted) {
            respond(404, [
                "success" => false,
                "message" => "Event not found."
            ]);
        }

        respond(200, [
            "success" => true,
            "message" => "Event deleted successfully."
        ]);
    }

    $conn->close();

    respond(400, [
        "success" => false,
        "message" => "Unknown action. Use add, update, or delete."
    ]);

} catch (Throwable $ex) {
    error_log("StudentHub events API error: " . $ex->getMessage());

    respond(500, [
        "success" => false,
        "message" => "A server error occurred. Check the PHP error log."
    ]);
}
?>
