<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'connection.php'; // DB connection

// Configuration
$clientID = '145056233418-lbpkussp1fn4ihff4ah9rukmo52etc2m.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'http://localhost/lab/google_login.php';

// Create Google Client
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// Step 1: Handle OAuth callback
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        die('OAuth Error: ' . htmlspecialchars($token['error_description']));
    }
    $client->setAccessToken($token['access_token']);

    // Step 2: Get Google profile info
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    $email = $google_account_info->email;
    $name = $google_account_info->name;

    // Step 3: Check if email exists in faculty DB
    $stmt = $conn->prepare("SELECT faculty_id, name, department, phone, email 
                            FROM faculty WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Faculty exists → fetch details
        $stmt->bind_result($faculty_id, $db_name, $department, $phone, $db_email);
        $stmt->fetch();

        // Step 4: Set session variables
        $_SESSION['faculty_id'] = $faculty_id;
        $_SESSION['name'] = $db_name;
        $_SESSION['department'] = $department;
        $_SESSION['phone'] = $phone;
        $_SESSION['email'] = $db_email;

        // Redirect to main page
        header("Location: facultymain.php");
        exit();
    } else {
        // Step 5: Email not found → show error & redirect
        echo "<script>
                alert('Access Denied: Your Google account ($email) is not registered in the system.');
                window.location.href = 'index.html';
              </script>";
        exit();
    }
} else {
    // Redirect user to Google login
    header('Location: ' . $client->createAuthUrl());
    exit();
}
?>
