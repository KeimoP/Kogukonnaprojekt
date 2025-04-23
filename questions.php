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
    header('Location: index.php');
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
    header("Location: ".$_SERVER['PHP_SELF']); // Refresh page after POST to avoid resubmission
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include jsPDF library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <style>
        :root {
        --primary-color: #667eea;
        --secondary-color: #764ba2;
        --accent-color: #4cc9f0;
        --text-color: #ffffff;
        }

        body {
        background: linear-gradient(135deg, #1f1c2c, #928dab);
        font-family: 'Outfit', sans-serif;
        color: var(--text-color);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0;
        }

        .navbar {
        width: 100%;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: fixed;
        top: 0;
        left: 0;
        z-index: 999;
        color: white;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
        font-size: 1.5rem;
        font-weight: 700;
        color: white;
        text-decoration: none;
        }

        .navbar-links {
        display: flex;
        gap: 1rem;
        }

        .navbar-links a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        transition: all 0.3s ease;
        }

        .navbar-links a:hover {
        background-color: rgba(255, 255, 255, 0.1);
        transform: scale(1.05);
        }

        @media (max-width: 768px) {
        .navbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .navbar-links {
            flex-direction: column;
            width: 100%;
            margin-top: 1rem;
        }

        .navbar-links a {
            width: 100%;
            text-align: left;
        }
        }

        .questions-card {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.18);
        animation: fadeInUp 0.8s ease forwards;
        margin: 2rem;
        }

        .questions-header h1 {
        color: white;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-align: center;
        }

        .question-card {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid var(--accent-color);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-control {
        background-color: transparent;
        border: 1px solid rgba(255, 255, 255, 0.4);
        color: white;
        border-radius: 8px;
        margin-top: 0.5rem;
        }

        .form-control:focus {
        border-color: #ffffffaa;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
        background-color: transparent;
        }

        .btn-pink {
        background: linear-gradient(to right, #ff6a8b, #d61e6a);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s;
        }

        .btn-pink:hover {
        transform: scale(1.03);
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .btn-export {
        background: linear-gradient(to right, #6f42c1, #5a32a3);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s;
        }

        .btn-export:hover {
        transform: scale(1.03);
        box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .action-buttons {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 2rem;
        flex-wrap: wrap;
        }

        @keyframes fadeInUp {
        0% {
            opacity: 0;
            transform: translateY(40px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
        }

        @media (max-width: 576px) {
        .action-buttons {
            flex-direction: column;
            align-items: center;
        }

        .btn-export {
            margin-left: 0;
            margin-top: 1rem;
        }
        }
    </style>
</head>
<body class="flower-bg">
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand text-pink" href="index.php">🌸 <?= $translations['back_home'] ?? 'Back to Start' ?></a>
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
            <h1 class="text-center mb-4 text-pink"><?= $translations['questions_heading'] ?> <span class="flower-emoji">🌼</span></h1>
            
            <form id="questions-form">
                <?php foreach ($selected_questions as $index => $question): ?>
                    <div class="question-card">
                    <label class="form-label fw-bold">🌷 <?= ($index+1) ?>. <?= $question[$_SESSION['lang']] ?></label>
                        <textarea class="form-control" name="answer[<?= $question['id'] ?>]" 
                                rows="3" placeholder="<?= $translations['answer_placeholder'] ?>"></textarea>
                    </div>
                <?php endforeach; ?>
            </form>

            <div class="text-center mt-4 action-buttons">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="new_questions" class="btn btn-pink btn-lg">
                        <?= $translations['new_questions_button'] ?> 🌼
                    </button>
                </form>
                
                <button type="button" id="export-pdf" class="btn btn-export btn-lg">
                    <i class="bi bi-file-earmark-pdf"></i> <?= $translations['export_button'] ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize jsPDF
        const { jsPDF } = window.jspdf;
        
        document.getElementById('questions-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert("<?= $translations['thank_you_message'] ?>");
            this.reset();
        });

        // PDF Export functionality
        document.getElementById('export-pdf').addEventListener('click', function() {
            const form = document.getElementById('questions-form');
            const answers = [];
            let hasAnswers = false;
            
            // Collect all answers
            form.querySelectorAll('textarea').forEach((textarea, index) => {
                const question = form.querySelectorAll('label')[index].textContent.replace(/^\d+\.\s/, '');
                const answer = textarea.value.trim();
                
                if (answer) hasAnswers = true;
                
                answers.push({
                    question: question,
                    answer: answer || '<?= $translations['no_answer'] ?>'
                });
            });
            
            if (!hasAnswers) {
                alert("<?= $translations['no_answers_alert'] ?>");
                return;
            }
            
            // Create PDF
            const doc = new jsPDF();
            const userName = "<?= $_SESSION['user_name'] ?? '' ?>";
            const date = new Date().toLocaleDateString();
            
            // Add title
            doc.setFontSize(18);
            doc.setTextColor(67, 97, 238); // Primary color
            doc.text("<?= $translations['export_title'] ?>", 105, 15, { align: 'center' });
            
            // Add metadata
            doc.setFontSize(12);
            doc.setTextColor(0, 0, 0); // Black color
            if (userName) {
                doc.text(`<?= $translations['name_label'] ?>: ${userName}`, 14, 25);
            }
            doc.text(`<?= $translations['date_label'] ?>: ${date}`, 14, 32);
            
            // Prepare data for the table
            const tableData = answers.map(item => [item.question, item.answer]);
            
            // Add table with questions and answers
            doc.autoTable({
                startY: 40,
                head: [['<?= $translations['question_label'] ?>', '<?= $translations['answer_label'] ?>']],
                body: tableData,
                theme: 'grid',
                headStyles: {
                    fillColor: [67, 97, 238], // Primary color header
                    textColor: 255
                },
                alternateRowStyles: {
                    fillColor: [232, 240, 254] // Light blue alternate rows
                },
                margin: { top: 10 }
            });
            
            // Save the PDF
            doc.save(`<?= $translations['export_filename'] ?>_${date}.pdf`);
        });
    </script>
</body>
</html>