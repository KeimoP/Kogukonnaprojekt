<?php
require 'db_connect.php';

// Get selected emotion
$emotion = $_POST['emotion'] ?? null;

// Check if emotion is valid
$valid_emotions = ['good', 'okay', 'bad', 'very_bad'];

if ($emotion && in_array($emotion, $valid_emotions)) {
    // Store the visit
    $stmt = $conn->prepare("INSERT INTO visitors (emotion_state, visit_time, is_returning) VALUES (?, NOW(), 0)");
    $stmt->bind_param("s", $emotion);
    $stmt->execute();
    $stmt->close();
}

header("Location: questions.php"); // or redirect back to home
exit;
?>