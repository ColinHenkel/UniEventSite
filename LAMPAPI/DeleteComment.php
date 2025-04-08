<?php
    session_start();

    // DB connection
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
        exit();
    }

    // Check if user is logged in
    if (!isset($_SESSION["userId"])) {
        http_response_code(403);
        echo json_encode(["error" => "Unauthorized"]);
        exit();
    }

    // Validate and get comment ID
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or missing comment ID"]);
        exit();
    }

    $commentId = (int) $_GET['id'];
    $userId = $_SESSION["userId"];

    // Check if the comment belongs to the current user
    $sql = "SELECT UID FROM Comments WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(["error" => "Comment not found"]);
        exit();
    }

    // Delete the comment
    $sql = "DELETE FROM Comments WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $commentId);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "Failed to delete comment"]);
    }

    $stmt->close();
    $conn->close();
?>
