<?php
    header("Content-Type: application/json; charset=UTF-8");
    
    // Enable error reporting for debugging but prevent HTML output
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    
    // Start session to get user ID
    session_start();
    $userId = $_SESSION["userId"] ?? null;
    
    if (!$userId) {
        http_response_code(401);
        echo json_encode(array("message" => "User not authenticated"));
        exit();
    }
    
    // Connect to the database
    $conn = new mysqli("localhost", "Admin", "colin201", "EventSite");
    
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(array("message" => "Database connection failed: " . $conn->connect_error));
        exit();
    }
    
    try {
        // Get current user's email and university
        $userQuery = "SELECT email, university FROM Users WHERE UID = ?";
        $stmt = $conn->prepare($userQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userResult = $stmt->get_result();
        
        if ($userResult->num_rows == 0) {
            http_response_code(404);
            echo json_encode(array("message" => "User not found"));
            exit();
        }
        
        $userRow = $userResult->fetch_assoc();
        $userEmail = $userRow['email'];
        $userUniversity = $userRow['university'];
        
        // Extract email domain
        $emailParts = explode('@', $userEmail);
        $userEmailDomain = $emailParts[1] ?? '';
        
        // Get user's RSO memberships
        $rsoQuery = "SELECT RSO_ID FROM `Join` WHERE User_ID = ?";
        $stmt = $conn->prepare($rsoQuery);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $rsoResult = $stmt->get_result();
        
        $userRsos = array();
        while ($rsoRow = $rsoResult->fetch_assoc()) {
            $userRsos[] = $rsoRow['RSO_ID'];
        }
        
        // Simplified query that's less likely to cause SQL errors
        $query = "
            SELECT 
                r.ID AS RSO_ID,
                r.Name AS RSO_Name,
                COUNT(DISTINCT j.User_ID) AS Member_Count,
                a.university AS University,
                a.username AS Admin_Username,
                a.email AS Admin_Email,
                SUBSTRING_INDEX(a.email, '@', -1) AS University_Domain
            FROM 
                RSOs r
            LEFT JOIN 
                `Join` j ON r.ID = j.RSO_ID
            LEFT JOIN 
                RSO_Creates rc ON r.ID = rc.RSO_ID
            LEFT JOIN 
                Admins ad ON rc.Admin_ID = ad.ID
            LEFT JOIN 
                Users a ON ad.ID = a.UID
            GROUP BY 
                r.ID, r.Name, a.university, a.username, a.email
            ORDER BY 
                r.Name ASC
        ";
        
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $rsos = array();
        
        while ($row = $result->fetch_assoc()) {
            // Check if user is a member
            $isMember = in_array($row['RSO_ID'], $userRsos);
            
            // Check if email domain matches
            $adminEmailDomain = $row['University_Domain'] ?? '';
            $canJoin = ($adminEmailDomain === $userEmailDomain && !$isMember);
            
            // Add these fields to the result
            $row['Is_Member'] = $isMember;
            $row['Can_Join'] = $canJoin;
            
            $rsos[] = $row;
        }
        
        echo json_encode($rsos);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array("message" => "Error: " . $e->getMessage()));
    }
    
    $conn->close();
?>