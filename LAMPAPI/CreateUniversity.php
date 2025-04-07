<?php
    // Database connection
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");

    // Check connection
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("message" => "Database connection failed: " . $conn->connect_error));
        exit();
    }

    // Get the posted data
    $data = json_decode(file_get_contents("php://input"));

    // Extract university data from the request
    $uniName = $data->universityName;
    $uniAddress = $data->universityAddress;
    $uniDesc = $data->universityDescription;
    $population = $data->studentCount;

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO University (Name, Address, Description, StudentPopulation) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $uniName, $uniAddress, $uniDesc, $population);

    if($stmt->execute()) {
        $response['success'] = "University created successfully!";
    } else {
        $response['error'] = "Error creating university: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
?>
