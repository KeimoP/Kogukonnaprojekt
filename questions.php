<?php
session_start();

// Handle language change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_lang'])) {
    $_SESSION['lang'] = $_POST['lang'];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Redirect if language not set
if (empty($_SESSION['lang'])) {
    header('Location: ./');
    exit;
}

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load questions and translations
$questionsData = json_decode(file_get_contents('questions.json'), true);
if (!$questionsData || !isset($questionsData['questions'])) {
    die('Error: Failed to load or parse questions.json');
}
$questions = $questionsData['questions'];

$langFile = "lang/{$_SESSION['lang']}.json";
if (!file_exists($langFile)) {
    die('Error: Language file not found.');
}
$translations = json_decode(file_get_contents($langFile), true);
if (!$translations) {
    die('Error: Failed to decode translation file.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_questions'])) {
    shuffle($questions);
    $_SESSION['current_questions'] = array_slice($questions, 0, 10);
    $_SESSION['questions_id'] = uniqid();
    header("Location: " . $_SERVER['PHP_SELF']); // Refresh page after POST to avoid resubmission
    exit;
}

if (!isset($_SESSION['current_questions'])) {
    shuffle($questions);
    $_SESSION['current_questions'] = array_slice($questions, 0, 10);
    $_SESSION['questions_id'] = uniqid();
}


$selected_questions = $_SESSION['current_questions'];
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translations['questions_title'] ?></title>
    <link rel="icon" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/questions.css" !important>
</head>

<body class="flower-bg">
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand text-pink" href="./">🌸 <?= $translations['back_home'] ?? 'Back to Start' ?></a>
            <form method="POST" class="d-flex ms-auto align-items-center">
                <input type="hidden" name="change_lang" value="1">
                <select name="lang" class="form-select me-2" onchange="this.form.submit()">
                    <option value="en" <?= $_SESSION['lang'] === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="et" <?= $_SESSION['lang'] === 'et' ? 'selected' : '' ?>>Eesti</option>
                    <option value="ru" <?= $_SESSION['lang'] === 'ru' ? 'selected' : '' ?>>русский</option>
                </select>
            </form>
        </div>
    </nav>

    <div class="container py-5">
        <div class="questions-card p-4">
            <h1 class="text-center mb-4 text-pink">
                <span id="typing-heading"><?= $translations['questions_heading'] ?></span> <span
                    class="flower-emoji"></span>
            </h1>


            <form id="questions-form text-white">
                <?php foreach ($selected_questions as $index => $question): ?>
                    <div class="question-card text-white">
                        <label class="form-label fw-bold">🌷 <?= ($index + 1) ?>.
                            <?= $question[$_SESSION['lang']] ?></label>
                        <textarea class="form-control text-white" name="answer[<?= $question['id'] ?>]" rows="3"
                            placeholder="<?= $translations['answer_placeholder'] ?>"></textarea>
                    </div>
                <?php endforeach; ?>
            </form>

            <div class="text-center mt-4 action-buttons">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="new_questions" class="btn btn-pink btn-lg">
                        <?= $translations['new_questions_button'] ?> 🌼
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const text = document.getElementById('typing-heading').innerText;
        let i = 0;
        document.getElementById('typing-heading').innerText = '';
        function type() {
            if (i < text.length) {
                document.getElementById('typing-heading').innerText += text.charAt(i);
                i++;
                setTimeout(type, 50);
            }
        }
        type();
    </script>
</body>

</html>