<?php
session_start();

// Connect to SQLite database
$db = new SQLite3('/srv/http/somnia/messages.db');

// Fetch replies for the specified message ID
if (isset($_GET['message_id'])) {
    $message_id = intval($_GET['message_id']);
    $replies = $db->query("SELECT * FROM Replies WHERE message_id = $message_id ORDER BY timestamp ASC");

    while ($reply = $replies->fetchArray(SQLITE3_ASSOC)) {
        echo "<div class='reply'>";
        echo "<div class='username'>{$reply['username']}</div>";
        echo "<div class='message'>{$reply['reply']}</div>";
        echo "<div class='timestamp'>{$reply['timestamp']}</div>";
        echo "</div>";
    }
}
?>
