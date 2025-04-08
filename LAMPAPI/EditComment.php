<?php
// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if request method is PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "error" => "Only PUT method is allowed"]);
    exit();
}

// Get PUT data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['comment_id']) || !is_numeric($data['comment_id'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid or missing comment_id"]);
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
$commentId = (int) $data['comment_id'];
$commentText = trim($data['comment_text']);
$rating = (int) $data['rating'];

// DB connection
$conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Verify comment exists and belongs to the current user
$checkCommentSql = "SELECT ID FROM Comments WHERE ID = ? AND UID = ?";
$checkCommentStmt = $conn->prepare($checkCommentSql);
$checkCommentStmt->bind_param("ii", $commentId, $userId);
$checkCommentStmt->execute();
$checkCommentResult = $checkCommentStmt->get_result();

if ($checkCommentResult->num_rows === 0) {
    $checkCommentStmt->close();
    $conn->close();
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Comment not found or you don't have permission to edit it"]);
    exit();
}
$checkCommentStmt->close();

// Update the comment
$updateSql = "UPDATE Comments SET Text = ?, rating = ?, timestamp = CURRENT_TIMESTAMP WHERE ID = ? AND UID = ?";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("siii", $commentText, $rating, $commentId, $userId);

if ($updateStmt->execute()) {
    $updateStmt->close();
    $conn->close();
    echo json_encode(["success" => true, "message" => "Comment updated successfully"]);
} else {
    $updateStmt->close();
    $conn->close();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Failed to update comment: " . $conn->error]);
}
?>