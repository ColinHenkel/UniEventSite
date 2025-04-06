<?php

    $inData = getRequestInfo();
    
    $firstName = $inData["firstName"];
    $lastName = $inData["lastName"];
    $email = $inData["email"];
    $login = $inData["login"];
    $password = $inData["password"];
    $role = $inData["role"];
    
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    
    if ($conn->connect_error) {
        returnWithError($conn->connect_error);
    } else {
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // Insert into Users table first
            $stmt = $conn->prepare("INSERT INTO Users (firstname, lastname, email, login, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $firstName, $lastName, $email, $login, $password);
            $stmt->execute();
            
            // Get the new user ID
            $userId = $conn->insert_id;
            
            // Based on role, insert into appropriate role table
            if ($role === "admin") {
                $stmt = $conn->prepare("INSERT INTO Admins (ID) VALUES (?)");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
            } else if ($role === "superadmin") {
                $stmt = $conn->prepare("INSERT INTO SuperAdmins (ID) VALUES (?)");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
            }
            
            // Commit the transaction
            $conn->commit();
            
            returnWithInfo($userId);
        } catch (Exception $e) {
            // Rollback the transaction if error
            $conn->rollback();
            returnWithError($e->getMessage());
        }
        
        $conn->close();
    }
    
    function getRequestInfo() {
        return json_decode(file_get_contents('php://input'), true);
    }
    
    function sendResultInfoAsJson($obj) {
        header('Content-type: application/json');
        echo $obj;
    }
    
    function returnWithError($err) {
        $retValue = '{"error":"' . $err . '"}';
        sendResultInfoAsJson($retValue);
    }
    
    function returnWithInfo($userId) {
        $retValue = '{"userId":' . $userId . ',"error":""}';
        sendResultInfoAsJson($retValue);
    }
?>