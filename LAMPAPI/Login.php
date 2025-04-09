<?php
    
    $inData = getRequestInfo();
    session_start();
    
    $login = $inData["login"];
    $password = $inData["password"];
    
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    
    if ($conn->connect_error) {
        returnWithError($conn->connect_error);
    } else {
        // Check if the user exists and password matches
        $stmt = $conn->prepare("SELECT UID, firstname, lastname, university, email FROM Users WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $login, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $userId = $row["UID"];
            $firstname = $row["firstname"];
            $lastname = $row["lastname"];
            $email = $row["email"];

            $_SESSION["userId"] = $userId;
            $_SESSION["userUni"] = $row["university"];
            
            // Check if user is Admin
            $isAdmin = false;
            $adminStmt = $conn->prepare("SELECT * FROM Admins WHERE ID = ?");
            $adminStmt->bind_param("i", $userId);
            $adminStmt->execute();
            if ($adminStmt->get_result()->num_rows > 0) {
                $isAdmin = true;
            }
            $adminStmt->close();
            
            // Check if user is SuperAdmin
            $isSuperAdmin = false;
            $superAdminStmt = $conn->prepare("SELECT * FROM SuperAdmins WHERE ID = ?");
            $superAdminStmt->bind_param("i", $userId);
            $superAdminStmt->execute();
            if ($superAdminStmt->get_result()->num_rows > 0) {
                $isSuperAdmin = true;
            }
            $superAdminStmt->close();
            
            // Determine role
            $role = "student";
            if ($isSuperAdmin) {
                $role = "superadmin";
            } else if ($isAdmin) {
                $role = "admin";
            }
            
            // Return user information including role
            returnWithInfo($userId, $firstname . " " . $lastname, $email, $role);
        } else {
            returnWithError("Invalid Username/Password");
        }
        
        $stmt->close();
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
    
    function returnWithInfo($userId, $name, $email, $role) {
        $retValue = '{"userId":' . $userId . ',"name":"' . $name . '","email":"' . $email . '","role":"' . $role . '","error":""}';
        sendResultInfoAsJson($retValue);
    }
?>