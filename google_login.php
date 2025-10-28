<?php
/*******************
 * google_login.php
 * Handles Google OAuth Login for both localhost and production
 *******************/

session_start();
require_once 'vendor/autoload.php';
require_once 'connection.php'; // Include your DB connection

/* -----------------------------------------------------
   CONFIGURATION
----------------------------------------------------- */
$clientID = '145056233418-lbpkussp1fn4ihff4ah9rukmo52etc2m.apps.googleusercontent.com';
$clientSecret = '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';

/* Detect whether we're on localhost or the live server */
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    // Local environment (XAMPP)
    $redirectUri = 'http://localhost/lab/google_login.php';
} else {
    // Production environment (Live public domain)
    $redirectUri = 'https://sahelms.vrsiddhartha.ac.in/pavan/google_login.php';
}

/* -----------------------------------------------------
   INITIALIZE GOOGLE CLIENT
----------------------------------------------------- */
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

/* -----------------------------------------------------
   STEP 1: HANDLE OAUTH CALLBACK
----------------------------------------------------- */
if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        die('OAuth Error: ' . htmlspecialchars($token['error_description']));
    }

    $client->setAccessToken($token['access_token']);

    // Fetch Google user profile
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    $email = $google_account_info->email;
    $name  = $google_account_info->name;

    /* -----------------------------------------------------
       STEP 2: VERIFY EMAIL WITH FACULTY DATABASE
    ----------------------------------------------------- */
    $stmt = $conn->prepare("
        SELECT faculty_id, name, department, phone, email
        FROM faculty
        WHERE email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Faculty exists → set session
        $stmt->bind_result($faculty_id, $db_name, $department, $phone, $db_email);
        $stmt->fetch();

        $_SESSION['faculty_id'] = $faculty_id;
        $_SESSION['name']       = $db_name;
        $_SESSION['department'] = $department;
        $_SESSION['phone']      = $phone;
        $_SESSION['email']      = $db_email;

        // Redirect to faculty main page
        header("Location: facultymain.php");
        exit();
    } else {
        // Email not found → show error
        echo "<script>
                alert('Access Denied: Your Google account ($email) is not registered in the system.');
                window.location.href = 'index.html';
              </script>";
        exit();
    }

} else {
    /* -----------------------------------------------------
       STEP 3: IF NO CODE → REDIRECT TO GOOGLE LOGIN
    ----------------------------------------------------- */
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit();
}
?>