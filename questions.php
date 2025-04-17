<?php
session_start();

// Check if user came from welcome page
if (empty($_SESSION['lang'])) {
    header('Location: index.php');
    exit;
}

// Load questions and translations
$questions = json_decode(file_get_contents('questions.json'), true)['questions'];
$translations = json_decode(file_get_contents("lang/{$_SESSION['lang']}.json"), true);

// Select 10 random questions
shuffle($questions);
$selected_questions = array_slice($questions, 0, 10);
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translations['questions_title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .flower-bg {
            background-color: #fff5f7;
            background-image: url('assets/images/lill1.jpeg'), url('assets/images/lill2.svg');
            background-position: left top, right bottom;
            background-repeat: no-repeat;
            background-size: 200px, 150px;
            min-height: 100vh;
        }
        .questions-card {
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 30px rgba(255, 182, 193, 0.3);
            border: 1px solid #ffccd5;
        }
        .question-card {
            background-color: rgba(255, 255, 255, 0.7);
            border-left: 4px solid #d63384;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="flower-bg">
    <div class="container py-5">
        <div class="questions-card p-4">
            <h1 class="text-center mb-4 text-pink"><?= $translations['questions_heading'] ?> <span class="flower-emoji">🌼</span></h1>
            
            <form id="questions-form">
                <?php foreach ($selected_questions as $index => $question): ?>
                    <div class="question-card p-3 mb-3">
                        <label class="form-label text-pink"><?= ($index+1) ?>. <?= $question[$_SESSION['lang']] ?></label>
                        <textarea class="form-control" name="answer[<?= $question['id'] ?>]" 
                                  rows="3" placeholder="<?= $translations['answer_placeholder'] ?>"></textarea>
                    </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-pink btn-lg"><?= $translations['submit_button'] ?></button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('questions-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert("<?= $translations['thank_you_message'] ?>");
            this.reset();
        });
    </script>
</body>
</html>