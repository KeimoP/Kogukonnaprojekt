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

// Generate or retrieve current questions
if (!isset($_SESSION['current_questions']) || isset($_POST['new_questions'])) {
    // Generate new random questions
    shuffle($questions);
    $_SESSION['current_questions'] = array_slice($questions, 0, 10);
    $_SESSION['questions_id'] = uniqid(); // Unique ID for answer storage
}

// Get current question set
$selected_questions = $_SESSION['current_questions'];
?>
<?php
// Temporary debug - remove after testing
if (isset($_SESSION['user_name'])) {
    echo '<div class="alert alert-info">Welcome back, '.htmlspecialchars($_SESSION['user_name']).'! Your visit was recorded.</div>';
}
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
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --text-color: #2b2d42;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .questions-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1rem;
        }
        
        .questions-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            border: none;
        }
        
        .questions-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .questions-header h1 {
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .questions-header .icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }
        
        .question-card {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--accent-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
            margin-top: 0.5rem;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-export {
            background-color: #6f42c1;
            color: white;
            margin-left: 1rem;
        }
        
        .btn-export:hover {
            background-color: #5a32a3;
            color: white;
            transform: translateY(-2px);
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
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
<body>
    <div class="questions-container">
        <div class="questions-card">
            <div class="questions-header">
                <div class="icon">📝</div>
                <h1><?= $translations['questions_heading'] ?></h1>
            </div>
            
            <form id="questions-form">
                <?php foreach ($selected_questions as $index => $question): ?>
                    <div class="question-card">
                        <label class="form-label fw-bold"><?= ($index+1) ?>. <?= $question[$_SESSION['lang']] ?></label>
                        <textarea class="form-control" name="answer[<?= $question['id'] ?>]" 
                                  rows="3" placeholder="<?= $translations['answer_placeholder'] ?>"></textarea>
                    </div>
                <?php endforeach; ?>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary btn-lg"><?= $translations['submit_button'] ?></button>
                    <button type="button" id="export-pdf" class="btn btn-export btn-lg">
                        <i class="bi bi-file-earmark-pdf"></i> <?= $translations['export_button'] ?>
                    </button>
                </div>
                
                <div class="text-center mt-4">
                    <form method="POST">
                        <button type="submit" name="new_questions" class="btn btn-secondary btn-lg">
                            <?= $translations['new_questions_button'] ?>
                        </button>
                    </form>
                </div>
            </form>
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