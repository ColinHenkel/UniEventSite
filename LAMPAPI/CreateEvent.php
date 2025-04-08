<?php
    // Database connection
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");

    // Grab session userid and university
    session_start();
    $university = $_SESSION["userUni"];
    $userId = $_SESSION["userId"];

    // Check connection
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("message" => "Database connection failed: " . $conn->connect_error));
        exit();
    }
    
    // Get the posted data
    $data = json_decode(file_get_contents("php://input"));

    // Check if data is complete
    if (
        empty($data->name) || 
        empty($data->category) || 
        empty($data->description) || 
        empty($data->date) || 
        empty($data->startTime) || 
        empty($data->endTime) || 
        empty($data->locationname) || 
        empty($data->locationaddr) || 
        empty($data->latitude) || 
        empty($data->longitude) || 
        empty($data->phone) || 
        empty($data->email)
    ) {
        // Set response code to 400 (bad request) and tell the user
        http_response_code(400);
        echo json_encode(array("message" => "Unable to create event. Data is incomplete."));
        exit();
    }

    try {
        // Check if the location exists by name
        $locationQuery = "SELECT Lname FROM Location WHERE Lname = ?";
        $locationStmt = $conn->prepare($locationQuery);
        $locationStmt->bind_param("s", $data->locationname);
        $locationStmt->execute();
        $locationResult = $locationStmt->get_result();
        
        if ($locationResult->num_rows == 0) {
            // Location doesn't exist so create it
            $insertLocationQuery = "INSERT INTO Location (Lname, Address, Latitude, Longitude) VALUES (?, ?, ?, ?)";
            $insertLocationStmt = $conn->prepare($insertLocationQuery);
            $insertLocationStmt->bind_param("ssdd", 
                $data->locationname, 
                $data->locationaddr, 
                $data->latitude, 
                $data->longitude
            );
            
            if (!$insertLocationStmt->execute()) {
                throw new Exception("Error creating location: " . $insertLocationStmt->error);
            }
            $insertLocationStmt->close();
        }
        $locationStmt->close();
        
        // Set approved based on category
        $approved = ($data->category === "public") ? 0 : 1;
        
        // Check if the event's time overlaps with existing events using the trigger
        $insertEventQuery = "INSERT INTO Events (Start, End, Date, Lname, Event_name, `Desc`, University, Admin, Approved) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertEventStmt = $conn->prepare($insertEventQuery);
        $insertEventStmt->bind_param("sssssssii", 
            $data->startTime, 
            $data->endTime, 
            $data->date, 
            $data->locationname,
            $data->name, 
            $data->description, 
            $university,
            $userId,
            $approved
        );

        if ($insertEventStmt->execute()) {
            $eventId = $conn->insert_id;
            if ($data->category === "public") {
                $insertPublicQuery = "INSERT INTO Public_Events (Event_ID) VALUES (?)";
                $insertPublicStmt = $conn->prepare($insertPublicQuery);
                $insertPublicStmt->bind_param("i", $eventId);
                if (!$insertPublicStmt->execute()) {
                    http_response_code(500);
                    echo json_encode(array("message" => "Error creating public event: " . $insertPublicStmt->error));
                    exit();
                }
                $insertPublicStmt->close();
            } else if ($data->category === "private") {
                $insertPrivateQuery = "INSERT INTO Private_Events (Event_ID) VALUES (?)";
                $insertPrivateStmt = $conn->prepare($insertPrivateQuery);
                $insertPrivateStmt->bind_param("i", $eventId);
                if (!$insertPrivateStmt->execute()) {
                    http_response_code(500);
                    echo json_encode(array("message" => "Error creating private event: " . $insertPrivateStmt->error));
                    exit();
                }
                $insertPrivateStmt->close();
            } else if ($data->category === "rso") {
                $findRSOQuery = "SELECT RSO_ID FROM RSO_Creates WHERE Admin_ID = ?";
                $findRSOStmt = $conn->prepare($findRSOQuery);
                $findRSOStmt->bind_param("i", $userId);
                $findRSOStmt->execute();
                $rsoResult = $findRSOStmt->get_result();
                $rsoData = $rsoResult->fetch_assoc();
                $rsoId = $rsoData["RSO_ID"];
                $findRSOStmt->close();
    
                $insertRsoQuery = "INSERT INTO RSO_Events (Event_ID, RSO_ID) VALUES (?, ?)";
                $insertRsoStmt = $conn->prepare($insertRsoQuery);
                $insertRsoStmt->bind_param("ii", $eventId, $rsoId);
                if (!$insertRsoStmt->execute()) {
                    http_response_code(500);
                    echo json_encode(array("message" => "Error creating RSO event: " . $insertRsoStmt->error));
                    exit();
                }
                $insertRsoStmt->close();
            }
        } else {
            // Set response code - 503 service unavailable
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create event: " . $insertEventStmt->error));
        }
    } catch (Exception $e) {
        // Catch any error, including the signal from the trigger
        http_response_code(400);
        echo json_encode(array("message" => $e->getMessage()));
        exit();
    }

    $insertEventStmt->close();
    $conn->close();
?>
