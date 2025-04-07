<?php

    // Retrieve admin ID from session
    session_start();
    $userId = $_SESSION["userId"];

    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("message" => "Database connection failed: " . $conn->connect_error));
        exit();
    }

    // Extract data from request
    $data = json_decode(file_get_contents("php://input"), true);
    $name = trim($data['name']);
    $university = trim($data['university']);
    $adminEmail = trim($data['adminEmail']);
    $memberEmails = $data['memberEmails'];

    // Validate all members exist
    $allEmails = array_merge([$adminEmail], $memberEmails);
    $memberIds = [];

    foreach ($memberEmails as $email) {
        $stmt = $conn->prepare("SELECT UID FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $response = array('success' => false, 'message' => "User with email $email does not exist.");
            echo json_encode($response);
            exit();
        }

        $userRow = $result->fetch_assoc();
        $memberIds[] = $userRow['UID'];
        $stmt->close();
    }

    // Start actual transaction
    $conn->begin_transaction();
    try {
        // Check if RSO already exists
        $stmt = $conn->prepare("SELECT ID FROM RSOs WHERE Name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response = array('success' => false, 'message' => "RSO with name $name already exists.");
            echo json_encode($response);
            exit();
        }
        $stmt->close();

        // Insert new RSO
        $stmt = $conn->prepare("INSERT INTO RSOs (Name) VALUES (?)");
        $stmt->bind_param("s", $name);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting RSO: " . $stmt->error);
        }

        $rsoId = $conn->insert_id;
        $stmt->close();

        // Create relationship between RSO and admin
        $stmt = $conn->prepare("INSERT INTO RSO_Creates (RSO_ID, Admin_ID) VALUES (?, ?)");
        $stmt->bind_param("ii", $rsoId, $userId);
        if (!$stmt->execute()) {
            throw new Exception("Error creating RSO_Admin relationship: " . $stmt->error);
        }
        $stmt->close();

        // Add admin to RSO members
        $stmt = $conn->prepare("INSERT INTO `Join` (User_ID, RSO_ID) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $rsoId);
        if (!$stmt->execute()) {
            throw new Exception("Error adding admin to RSO members: " . $stmt->error);
        }
        $stmt->close();

        // Add other members to RSO
        foreach ($memberIds as $memberId) {
            $stmt = $conn->prepare("INSERT INTO `Join` (User_ID, RSO_ID) VALUES (?, ?)");
            $stmt->bind_param("ii", $memberId, $rsoId);
            if (!$stmt->execute()) {
                throw new Exception("Error adding member to RSO: " . $stmt->error);
            }
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();
        $response = array('success' => true, 'message' => "RSO created successfully.");
        echo json_encode($response);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $response = array('success' => false, 'message' => "Transaction failed: " . $e->getMessage());
        echo json_encode($response);
    }

    $conn->close();
?>
