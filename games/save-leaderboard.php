<?php
session_start();
header('Content-Type: application/json');

$filename = __DIR__ . '/../assets/json/leaderboard.json';


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($filename)) {
        $board = json_decode(file_get_contents($filename), true);
        if (!is_array($board)) $board = [];
    } else {
        $board = [];
    }
    echo json_encode(['leaderboard' => $board]);
    exit;
}
// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['name'], $data['score'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

// Profanity filter (same as JS)
$badWords = ["fuck","shit","bitch","ass","dick","cunt","piss","cock","fag","nigger","nigga","retard","whore","slut"];
$lower = strtolower($data['name']);
foreach ($badWords as $word) {
    if (strpos($lower, $word) !== false) {
        echo json_encode(['success' => false, 'error' => 'Please use a friendly nickname!']);
        exit;
    }
}

// Load existing leaderboard
if (file_exists($filename)) {
    $board = json_decode(file_get_contents($filename), true);
    if (!is_array($board)) $board = [];
} else {
    $board = [];
}

$board[] = [
    'name' => htmlspecialchars($data['name']),
    'score' => (int)$data['score']
];

// Sort and keep top 100
usort($board, function($a, $b) { return $b['score'] - $a['score']; });
if (count($board) > 100) {
    // If the new score is not in top 100, remove it
    $board = array_slice($board, 0, 100);
    $isInTop100 = false;
    foreach ($board as $entry) {
        if ($entry['name'] === htmlspecialchars($data['name']) && $entry['score'] === (int)$data['score']) {
            $isInTop100 = true;
            break;
        }
    }
    if (!$isInTop100) {
        echo json_encode(['success' => false, 'error' => 'Score not high enough for top 100!']);
        exit;
    }
}

// Save
file_put_contents($filename, json_encode($board, JSON_PRETTY_PRINT));

echo json_encode(['success' => true, 'leaderboard' => $board]);

if (isset($data['toggle'])) {
    if ($data['toggle'] === 'open') {
        $_SESSION['leaderboard_open'] = true;
    } else if ($data['toggle'] === 'close') {
        unset($_SESSION['leaderboard_open']);
    }
    echo json_encode(['success' => true]);
    exit;
}

if (isset($data['score']) && (int)$data['score'] === 0) {
    echo json_encode(['success' => false, 'error' => 'Score 0 cannot be submitted!']);
    exit;
}