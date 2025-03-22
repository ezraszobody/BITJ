<?php
session_start();

// Connect to SQLite database
$db = new SQLite3('/srv/http/somnia/messages.db');

// Ensure Messages table exists
$db->exec('CREATE TABLE IF NOT EXISTS Messages (id INTEGER PRIMARY KEY, username TEXT, message TEXT, profile_pic TEXT, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP)');

// Handle message posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (!empty($_POST['message']) && !empty($_SESSION['username'])) {
        $username = $_SESSION['username'];
        $message = $_POST['message'];
        $profile_pic = $_SESSION['profile_pic'] ?? '';

        $stmt = $db->prepare('INSERT INTO Messages (username, message, profile_pic) VALUES (:username, :message, :profile_pic)');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':message', $message, SQLITE3_TEXT);
        $stmt->bindValue(':profile_pic', $profile_pic, SQLITE3_TEXT);
        $stmt->execute();

        // Prevent form resubmission on refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $delete_id = intval($_POST['delete']);
    $db->exec("DELETE FROM Messages WHERE id = $delete_id");

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Retrieve messages
$results = $db->query('SELECT * FROM Messages ORDER BY timestamp DESC');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>The Bandito Network</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="app.css">
</head>
<body>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="test.php">Chat</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="text.php">Account</a>
        </li>
    </ul>

    <div class="container mt-4">
        <h1>The Bandito Network</h1>
        <?php if (isset($_SESSION['username'])): ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="message" class="form-label">Message:</label>
                    <textarea name="message" class="form-control" required></textarea>
                </div>
                <button type="submit" name="submit" class="btn btn-primary">Send</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                Please <a href="text.php">set up your account</a> to start chatting.
            </div>
        <?php endif; ?>

        <h2 class="mt-4">Messages:</h2>
        <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <?php if (!empty($row['profile_pic'])): ?>
                            <img class="profile-pic rounded-circle me-3" src="<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="Profile Picture" width="50" height="50">
                        <?php endif; ?>
                        <div>
                            <h5 class="card-title"><?php echo htmlspecialchars($row['username']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($row['message']); ?></p>
                            <p class="card-text"><small class="text-muted"><?php echo $row['timestamp']; ?></small></p>
                        </div>
                    </div>
                    <?php if (isset($_SESSION['username']) && $_SESSION['username'] === $row['username']): ?>
                        <form method="POST" class="mt-2">
                            <button type="submit" name="delete" value="<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <div class="vulture-container">
    <img src="1422071-middle-removebg-preview.png" alt="Trench Vulture" class="vulture-image">
</div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
</html>