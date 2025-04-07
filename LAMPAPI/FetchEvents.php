<?php
    header("Content-Type: application/json; charset=UTF-8");

    // Connect to the database
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("message" => "Database connection failed: " . $conn->connect_error));
        exit();
    }

    // Query to get all events and their details
    $query = "
            SELECT
        e.Event_ID,
        e.Event_name,
        e.Desc AS Description,
        e.Date,
        e.Start,
        e.End,
        l.Lname AS Location,
        l.Address,
        u.Name AS University,
        CASE
            WHEN pe.Event_ID IS NOT NULL THEN 'Public'
            WHEN pr.Event_ID IS NOT NULL THEN 'Private'
            WHEN re.Event_ID IS NOT NULL THEN 'RSO'
        END AS Event_Type,
        CASE
            WHEN re.Event_ID IS NOT NULL THEN r.Name
            ELSE NULL
        END AS RSO_Name,
        a.username AS Created_By
    FROM
        Events e
    JOIN
        Location l ON e.Lname = l.Lname
    JOIN
        University u ON e.University = u.Name
    JOIN
        Admins ad ON e.Admin = ad.ID
    JOIN
        Users a ON ad.ID = a.UID
    LEFT JOIN
        Public_Events pe ON e.Event_ID = pe.Event_ID
    LEFT JOIN
        Private_Events pr ON e.Event_ID = pr.Event_ID
    LEFT JOIN
        RSO_Events re ON e.Event_ID = re.Event_ID
    LEFT JOIN
        RSOs r ON re.RSO_ID = r.ID
    ORDER BY
        e.Date, e.Start;
    ";

    $result = $conn->query($query);

    if (!$result) {
        http_response_code(500);
        echo json_encode(array("message" => "Query failed: " . $conn->error));
        exit();
    }

    $events = array();

    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    echo json_encode($events);
    $conn->close();
?>