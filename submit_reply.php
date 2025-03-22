<?php
session_start();
$db = new SQLite3('/srv/http/somnia/messages.db');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = intval($_POST['message_id']);
    $reply = htmlspecialchars($_POST['reply']);
    $username = $_SESSION['username'] ?? 'Anonymous';

    $stmt = $db->prepare('INSERT INTO Replies (message_id, username, reply) VALUES (:message_id, :username, :reply)');
    $stmt->bindValue(':message_id', $message_id, SQLITE3_INTEGER);
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':reply', $reply, SQLITE3_TEXT);
    $stmt->execute();
}
?>
