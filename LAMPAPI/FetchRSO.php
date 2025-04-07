<?php
    header("Content-Type: application/json; charset=UTF-8");
    
    // Connect to the database
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("message" => "Database connection failed: " . $conn->connect_error));
        exit();
    }
    
    // Query to get all RSOs and their details
    $query = "
        SELECT 
    r.ID AS RSO_ID,
    r.Name AS RSO_Name,
    COUNT(j.User_ID) AS Member_Count,
    u.university AS University,
    a.username AS Admin_Name,
    GROUP_CONCAT(DISTINCT u2.username SEPARATOR ', ') AS Members
FROM 
    RSOs r
LEFT JOIN 
    `Join` j ON r.ID = j.RSO_ID
LEFT JOIN 
    Users u ON j.User_ID = u.UID
LEFT JOIN 
    RSO_Creates rc ON r.ID = rc.RSO_ID
LEFT JOIN 
    Admins ad ON rc.Admin_ID = ad.ID
LEFT JOIN 
    Users a ON ad.ID = a.UID
LEFT JOIN 
    Users u2 ON j.User_ID = u2.UID
GROUP BY 
    r.ID, r.Name, u.university, a.username
ORDER BY 
    r.Name ASC;
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(array("message" => "Query failed: " . $conn->error));
        exit();
    }
    
    $rsos = array();
    
    while ($row = $result->fetch_assoc()) {
        $rsos[] = $row;
    }
    
    echo json_encode($rsos);
    $conn->close();
?>