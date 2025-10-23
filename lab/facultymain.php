<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['faculty_id'])) {
    header("Location: logincheck.php");
    exit();
}

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache");
header("Expires: 0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sem End Lab Marks</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <header class="bg-primary text-white text-center py-3">
        <h2>SIDDHARTHA ACADEMY OF HIGHER EDUCATION</h2>
        <h3>(Deemed to be University)</h3>
        <h2>Sem End Lab Marks</h2>
    </header>

    <div class="container-fluid d-flex flex-grow-1">
        <div class="row w-100 flex-grow-1">
            <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 bg-secondary text-white p-3">
            <?php include('faculty_menu.php'); ?>
        </nav>

            <!-- Content area -->
            <div class="col-md-9 col-lg-10" id="content-area">
                <center>
                    <h2>Welcome to Sem End Lab Marks Management (SLMM) - SAHE</h2>
                    <h4 class="mt-4">Faculty Details</h4>
                </center>

                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped w-75 mx-auto">
                        <tr>
                            <th>Faculty ID</th>
                            <td><?php echo htmlspecialchars($_SESSION['faculty_id']); ?></td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td><?php echo htmlspecialchars($_SESSION['name']); ?></td>
                        </tr>
                        <tr>
                            <th>Department</th>
                            <td><?php echo htmlspecialchars($_SESSION['department']); ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?php echo htmlspecialchars($_SESSION['phone']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($_SESSION['email']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-primary text-white text-center py-2 mt-auto">
        <p>© 2024 Copyrights reserved - Developed by Dept. of IT</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
