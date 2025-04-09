<?php
    // Check if the user is a superadmin
    session_start();
    
    if (!isset($_SESSION["userId"])) {
        http_response_code(403);
        echo json_encode(array("status" => "error", "message" => "Unauthorized. Only superadmins can approve events."));
        exit();
    }
    
    // Get superadmin's user ID
    $userId = $_SESSION["userId"];
    
    // Connect to the database
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => "Database connection failed: " . $conn->connect_error));
        exit();
    }
    
    // Get the posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->eventId) || !isset($data->action)) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Missing event ID or action"));
        exit();
    }
    
    // Verify this superadmin is authorized for the event's university
    $verifyQuery = "
        SELECT 
            sa.ID, e.University, sa.University AS SuperAdminUniversity
        FROM 
            Events e
        JOIN 
            SuperAdmins sa ON sa.ID = ? AND sa.University = e.University
        WHERE 
            e.Event_ID = ?
    ";
    
    $stmt = $conn->prepare($verifyQuery);
    $stmt->bind_param("ii", $userId, $data->eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(403);
        echo json_encode(array("status" => "error", "message" => "Not authorized to manage this event"));
        exit();
    }
    
    // Verify this is a Public event
    $eventTypeQuery = "
        SELECT 1 FROM Public_Events WHERE Event_ID = ?
    ";
    $stmt = $conn->prepare($eventTypeQuery);
    $stmt->bind_param("i", $data->eventId);
    $stmt->execute();
    $eventTypeResult = $stmt->get_result();
    
    if ($eventTypeResult->num_rows == 0) {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "This is not a public event"));
        exit();
    }
    
    // Process the action
    if ($data->action === "approve") {
        $approved = 1;
        $message = "Event approved successfully";
    } else if ($data->action === "reject") {
        // For rejection delete the event
        $deleteQuery = "DELETE FROM Events WHERE Event_ID = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $data->eventId);
        
        if ($stmt->execute()) {
            echo json_encode(array("status" => "success", "message" => "Event rejected and removed"));
        } else {
            http_response_code(500);
            echo json_encode(array("status" => "error", "message" => "Error rejecting event: " . $stmt->error));
        }
        
        $conn->close();
        exit();
    } else {
        http_response_code(400);
        echo json_encode(array("status" => "error", "message" => "Invalid action"));
        exit();
    }
    
    // Update event approval status
    $updateQuery = "UPDATE Events SET Approved = ? WHERE Event_ID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $approved, $data->eventId);
    
    if ($stmt->execute()) {
        echo json_encode(array("status" => "success", "message" => $message));
    } else {
        http_response_code(500);
        echo json_encode(array("status" => "error", "message" => "Error updating event: " . $stmt->error));
    }
    
    $conn->close();
?>