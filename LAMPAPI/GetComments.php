<?php
    header("Content-Type: application/json");

    // DB connection
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
        exit();
    }

    // Start the session and get the current user's ID
    session_start();
    $currentUserId = isset($_SESSION["userId"]) ? $_SESSION["userId"] : null;

    // Validate and get event ID
    if (!isset($_GET['eventId']) || !is_numeric($_GET['eventId'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or missing eventId"]);
        exit();
    }

    $eventId = (int) $_GET['eventId'];

    // Fetch comments
    $sql = "
        SELECT c.ID AS comment_id, u.Username, c.Text, c.rating, c.timestamp, c.UID AS comment_user_id
        FROM Comments c
        JOIN Users u ON c.UID = u.UID
        WHERE c.Event_ID = ?
        ORDER BY c.timestamp DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get comments
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(["error" => "No comments found for this event"]);
        exit();
    }

    $comments = array();
    while ($row = $result->fetch_assoc()) {
        $comments[] = array(
            "id" => $row['comment_id'],
            "username" => $row['Username'],
            "text" => $row['Text'],
            "rating" => $row['rating'],
            "timestamp" => $row['timestamp'],
            "isOwner" => $row['comment_user_id'] == $currentUserId
        );
    }

    // Close the statement and connection then return comments
    $stmt->close();
    $conn->close();
    echo json_encode($comments);
?>
