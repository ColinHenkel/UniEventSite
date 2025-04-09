<?php
    header("Content-Type: application/json; charset=UTF-8");
    
    // Start session to get user ID
    session_start();
    $userId = $_SESSION["userId"] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "User not authenticated"));
        exit();
    }
    
    // Get the request body
    $data = json_decode(file_get_contents('php://input'), true);
    $rsoId = $data['rso_id'] ?? null;
    
    if (!$rsoId) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "RSO ID is required"));
        exit();
    }
    
    // Connect to the database
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Database connection failed: " . $conn->connect_error));
        exit();
    }
    
    // Check if user already a member of this RSO
    $checkQuery = "SELECT * FROM `Join` WHERE User_ID = ? AND RSO_ID = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $userId, $rsoId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "You are already a member of this RSO"));
        exit();
    }
    
    // Get user email
    $userQuery = "SELECT email FROM Users WHERE UID = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userResult = $stmt->get_result();
    
    if ($userResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "User not found"));
        exit();
    }
    
    $userRow = $userResult->fetch_assoc();
    $userEmail = $userRow['email'];
    $emailParts = explode('@', $userEmail);
    $userEmailDomain = $emailParts[1] ?? '';
    
    // Get RSO admin email domain
    $rsoQuery = "
        SELECT a.email
        FROM RSOs r
        JOIN RSO_Creates rc ON r.ID = rc.RSO_ID
        JOIN Admins ad ON rc.Admin_ID = ad.ID
        JOIN Users a ON ad.ID = a.UID
        WHERE r.ID = ?
    ";
    $stmt = $conn->prepare($rsoQuery);
    $stmt->bind_param("i", $rsoId);
    $stmt->execute();
    $rsoResult = $stmt->get_result();
    
    if ($rsoResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "RSO not found"));
        exit();
    }
    
    $rsoRow = $rsoResult->fetch_assoc();
    $adminEmail = $rsoRow['email'];
    $adminEmailParts = explode('@', $adminEmail);
    $adminEmailDomain = $adminEmailParts[1] ?? '';
    
    // Check if domains match
    if ($userEmailDomain !== $adminEmailDomain) {
        http_response_code(403);
        echo json_encode(array(
            "success" => false, 
            "message" => "Your email domain does not match the RSO's university domain",
            "user_domain" => $userEmailDomain,
            "rso_domain" => $adminEmailDomain
        ));
        exit();
    }
    
    // Add user to RSO
    $joinQuery = "INSERT INTO `Join` (User_ID, RSO_ID) VALUES (?, ?)";
    $stmt = $conn->prepare($joinQuery);
    $stmt->bind_param("ii", $userId, $rsoId);
    
    if ($stmt->execute()) {
        echo json_encode(array("success" => true, "message" => "Successfully joined the RSO"));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Database error: " . $conn->error));
    }
    
    $conn->close();
?>