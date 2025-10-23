<?php
session_start();
require_once 'connection.php'; // include your DB connection file

$error_message = "";

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($email) && !empty($password)) {
        // Prepare query to fetch faculty details
        $stmt = $conn->prepare("SELECT faculty_id, password, name, department, phone, email 
                                FROM faculty WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($faculty_id, $stored_password, $name, $department, $phone, $db_email);
            $stmt->fetch();

            // Compare passwords (plain text match – since your DB stores plain passwords)
            if ($password === $stored_password) {
                // ✅ Store session variables
                $_SESSION['faculty_id'] = $faculty_id;
                $_SESSION['name'] = $name;
                $_SESSION['department'] = $department;
                $_SESSION['phone'] = $phone;
                $_SESSION['email'] = $db_email;

                // ✅ Redirect to faculty main page (no echo before this!)
                header("Location: facultymain.php");
                exit();
            } else {
                $error_message = "Invalid password. Please try again.";
            }
        } else {
            $error_message = "No faculty found with that email.";
        }
        $stmt->close();
    } else {
        $error_message = "Please enter both email and password.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Login</title>
    <link rel="stylesheet" href="styles1.css">
    <script>
        // Disable right click and inspect
        document.addEventListener('contextmenu', function(event) {
            event.preventDefault();
        });
        document.addEventListener('keydown', function(event) {
            if (event.keyCode == 123 || 
                (event.ctrlKey && event.shiftKey && (event.keyCode == 73 || event.keyCode == 74)) ||
                (event.ctrlKey && event.keyCode == 85)) {
                event.preventDefault();
            }
        });
    </script>
</head>
<body>
    <!-- Overlay -->
    <div class="overlay">
        <div class="text-container">
            <h1>Siddhartha Academy of Higher Education</h1>
            <h2><i>Student Information System</i></h2>
            <button class="login-btn" onclick="openLoginForm()">Login</button>
        </div>
    </div>

    <!-- Modal login form -->
    <div id="loginForm" class="modal" style="display: <?php echo $error_message ? 'block' : 'none'; ?>;">
        <div class="modal-content">
            <span class="close-btn" onclick="closeLoginForm()">&times;</span>
            <h2>Login</h2>
            <form action="logincheck.php" method="post">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Login</button>
                <button type="button" onclick="window.location.href='google_login.php'" 
                        style="background-color:#4285F4; color: #fff;">
                    Login via Gmail
                </button>
            </form>
            <?php
            // Show error message if login failed
            if ($error_message) {
                echo "<p style='color:red; margin-top:15px;'>$error_message</p>";
            }
            ?>
        </div>
    </div>

    <script src="scripts.js"></script>
    <script>
        function openLoginForm() {
            document.getElementById("loginForm").style.display = "block";
        }
        function closeLoginForm() {
            document.getElementById("loginForm").style.display = "none";
        }
    </script>
</body>
</html>
