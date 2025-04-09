<?php
    // Enable CORS
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Content-Type: application/json");

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(["success" => false, "error" => "Only POST method is allowed"]);
        exit();
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (!isset($data['event_id']) || !is_numeric($data['event_id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid or missing event_id"]);
        exit();
    }

    if (!isset($data['comment_text']) || empty(trim($data['comment_text']))) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Comment text cannot be empty"]);
        exit();
    }

    if (!isset($data['rating']) || !is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Rating must be between 1-5"]);
        exit();
    }

    // Get user ID from session
    session_start();
    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "error" => "User not logged in"]);
        exit();
    }

    $userId = $_SESSION['userId'];
    $eventId = (int) $data['event_id'];
    $commentText = trim($data['comment_text']);
    $rating = (int) $data['rating'];

    // DB connection
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error]);
        exit();
    }

    // Verify event exists
    $checkEventSql = "SELECT Event_ID FROM Events WHERE Event_ID = ?";
    $checkEventStmt = $conn->prepare($checkEventSql);
    $checkEventStmt->bind_param("i", $eventId);
    $checkEventStmt->execute();
    $checkEventResult = $checkEventStmt->get_result();

    if ($checkEventResult->num_rows === 0) {
        $checkEventStmt->close();
        $conn->close();
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Event not found"]);
        exit();
    }
    $checkEventStmt->close();

    // Check if user already submitted a comment for this event
    $checkCommentSql = "SELECT ID FROM Comments WHERE Event_ID = ? AND UID = ?";
    $checkCommentStmt = $conn->prepare($checkCommentSql);
    $checkCommentStmt->bind_param("ii", $eventId, $userId);
    $checkCommentStmt->execute();
    $checkCommentResult = $checkCommentStmt->get_result();

    if ($checkCommentResult->num_rows > 0) {
        // User already commented - update existing comment
        $commentRow = $checkCommentResult->fetch_assoc();
        $commentId = $commentRow['ID'];
        
        $updateSql = "UPDATE Comments SET Text = ?, rating = ?, timestamp = CURRENT_TIMESTAMP WHERE ID = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sii", $commentText, $rating, $commentId);
        
        if ($updateStmt->execute()) {
            $updateStmt->close();
            $checkCommentStmt->close();
            $conn->close();
            echo json_encode(["success" => true, "message" => "Comment updated successfully"]);
        } else {
            $updateStmt->close();
            $checkCommentStmt->close();
            $conn->close();
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Failed to update comment: " . $conn->error]);
        }
    } else {
        // Insert new comment
        $insertSql = "INSERT INTO Comments (Event_ID, UID, Text, rating) VALUES (?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iisi", $eventId, $userId, $commentText, $rating);
        
        if ($insertStmt->execute()) {
            $insertStmt->close();
            $checkCommentStmt->close();
            $conn->close();
            echo json_encode(["success" => true, "message" => "Comment submitted successfully"]);
        } else {
            $insertStmt->close();
            $checkCommentStmt->close();
            $conn->close();
            http_response_code(500);
            echo json_encode(["success" => false, "error" => "Failed to submit comment: " . $conn->error]);
        }
    }
?>