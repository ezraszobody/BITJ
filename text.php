<?php
session_start();

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to SQLite database
$db = new SQLite3('/srv/http/somnia/messages.db');

// Ensure Users table exists with the correct schema
$db->exec('CREATE TABLE IF NOT EXISTS Users (
    id INTEGER PRIMARY KEY,
    username TEXT UNIQUE,
    password TEXT,
    profile_pic TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
)');

// Handle user signup or login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($username)) {
        $error = 'Username cannot be empty.';
    } elseif (empty($password)) {
        $error = 'Password cannot be empty.';
    } else {
        // Check if the username already exists
        $stmt = $db->prepare('SELECT * FROM Users WHERE username = :username');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $existingUser = $result->fetchArray(SQLITE3_ASSOC);

        $profile_pic = '';

        // Handle profile picture upload
        if (!empty($_FILES['profile_pic']['name'])) {
            $upload_dir = '/srv/http/somnia/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = basename($_FILES['profile_pic']['name']);
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path)) {
                $profile_pic = '/uploads/' . $file_name;
            } else {
                $error = 'Failed to upload profile picture.';
            }
        }

        if (!isset($error)) {
            if ($existingUser) {
                // Existing user: Verify password
                if (password_verify($password, $existingUser['password'])) {
                    // Password is correct, update profile picture if a new one was uploaded
                    if (!empty($profile_pic)) {
                        $stmt = $db->prepare('UPDATE Users SET profile_pic = :profile_pic WHERE username = :username');
                        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                        $stmt->bindValue(':profile_pic', $profile_pic, SQLITE3_TEXT);

                        if (!$stmt->execute()) {
                            $error = 'Error updating user profile.';
                        }
                    }
                } else {
                    $error = 'Incorrect password.';
                }
            } else {
                // New user: Insert into the database
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare('INSERT INTO Users (username, password, profile_pic) VALUES (:username, :password, :profile_pic)');
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
                $stmt->bindValue(':profile_pic', $profile_pic, SQLITE3_TEXT);

                if (!$stmt->execute()) {
                    $error = 'Error inserting user.';
                }
            }

            if (!isset($error)) {
                // Set session variables
                $_SESSION['username'] = $username;
                $_SESSION['profile_pic'] = $profile_pic ?: $existingUser['profile_pic'];

                // Redirect to the chat page
                header("Location: test.php");
                exit;
            }
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: text.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Setup - Bandito Network</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link" href="test.php">Chat</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="text.php">Account</a>
        </li>
    </ul>

    <div class="container mt-4">
        <h1>Account Setup</h1>

        <!-- Signup/Login Form -->
        <h2>Sign Up / Log In</h2>
        <form method="POST" enctype="multipart/form-data">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="profile_pic" class="form-label">Profile Picture:</label>
                <input type="file" name="profile_pic" class="form-control">
            </div>
            <button type="submit" name="signup" class="btn btn-primary">Sign Up / Log In</button>
        </form>

        <!-- Logout Button -->
        <?php if (isset($_SESSION['username'])): ?>
            <hr>
            <form method="GET">
                <button type="submit" name="logout" class="btn btn-danger">Logout</button>
            </form>
        <?php endif; ?>
    </div>
<br>


    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
</html>