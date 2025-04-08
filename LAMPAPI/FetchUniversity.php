<?php
    session_start();

    header("Content-Type: application/json; charset=UTF-8");

    // Check if university is stored in session
    if (isset($_SESSION["userUni"])) {
        echo json_encode([["University_Name" => $_SESSION["userUni"]]]);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(["error" => "University not found in session."]);
    }
?>
