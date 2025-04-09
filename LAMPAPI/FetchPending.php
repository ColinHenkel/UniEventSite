<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION["userId"])) {
        http_response_code(401);
        echo json_encode(array("message" => "Not logged in. Please log in first."));
        exit();
    }

    // Connect to the database
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("message" => "Database connection failed: " . $conn->connect_error));
        exit();
    }

    $userId = $_SESSION["userId"];

    // Check if the user is in the SuperAdmins table
    $superadminQuery = "SELECT University FROM SuperAdmins WHERE ID = ?";
    $stmt = $conn->prepare($superadminQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        http_response_code(403);
        echo json_encode(array("message" => "Unauthorized. Only superadmins can view pending events."));
        exit();
    }

    $row = $result->fetch_assoc();
    $university = $row["University"];

    // Query to get pending public events for this university
    $query = "
        SELECT 
            e.Event_ID,
            e.Event_name,
            e.Desc,
            e.Date,
            e.Start,
            e.End,
            e.Lname,
            e.University,
            u.username AS Admin_Name
        FROM 
            Events e
        JOIN
            Public_Events pe ON e.Event_ID = pe.Event_ID
        JOIN
            Users u ON e.Admin = u.UID
        WHERE 
            e.Approved = 0 AND
            e.University = ?
        ORDER BY 
            e.Date, e.Start
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $university);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = array();
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($events);
    $conn->close();
?>