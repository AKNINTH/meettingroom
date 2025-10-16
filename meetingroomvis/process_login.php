<?php
// Include the database connection file
require_once 'db_connect.php';

// Start a session to manage user login state
session_start();

// Check if the form was submitted using the POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if both username and password fields are set and not empty
    if (isset($_POST['username']) && isset($_POST['password'])) {
        
        // Sanitize and trim the input
        $username = trim($_POST['username']);
        $password = $_POST['password']; 
        
        try {
            // Prepare a SQL statement using a named placeholder.
            // This prevents SQL injection.
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
            
            // Execute the prepared statement, binding the username value to the placeholder.
            $stmt->execute(['username' => $username]);

            // Fetch the user data. The PDO connection is already set to fetch as an associative array.
            $user = $stmt->fetch();
            
            // Check if a user was found
            if ($user) {
                // Verify the hashed password from the database with the one the user submitted
                if (password_verify($password, $user['password'])) {
                    // Password is correct, so start a session
                    session_regenerate_id(true); // Regenerate session ID for security
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirect to the manufacturing form page
                    header("Location: main.php");
                    exit();
                } else {
                    // Invalid password
                    $_SESSION['login_error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง.";
                    header("Location: login2.php");
                    exit();
                }
            } else {
                // User not found
                $_SESSION['login_error'] = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง.";
                header("Location: login2.php");
                exit();
            }
        } catch(PDOException $e) {
            // Handle any database-related errors
            die("Error: " . $e->getMessage());
        }
    } else {
        // Not all fields were submitted
        $_SESSION['login_error'] = "โปรดกรอกข้อมูลให้ครบถ้วน.";
        header("Location: login2.php");
        exit();
    }
} else {
    // The request method was not POST, so redirect
    header("Location: login2.php");
    exit();
}
?>
