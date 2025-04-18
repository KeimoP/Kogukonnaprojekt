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
        .btn-export {
            background-color: #6f42c1;
            color: white;
            margin-left: 10px;
        }
        .btn-export:hover {
            background-color: #5a32a3;
            color: white;
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
                    <button type="button" id="export-pdf" class="btn btn-export btn-lg">
                        <i class="bi bi-file-earmark-pdf"></i> <?= $translations['export_button'] ?>
                    </button>
                </div>
                <div class="text-center mt-4">
                <form method="POST">
                    <button type="submit" name="new_questions" class="btn btn-secondary btn-lg">
                        <?= $translations['new_questions_button'] ?>
                        <span class="flower-emoji">🌼</span>
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
            doc.setTextColor(214, 51, 132); // Pink color
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
                    fillColor: [214, 51, 132], // Pink header
                    textColor: 255
                },
                alternateRowStyles: {
                    fillColor: [255, 222, 235] // Light pink alternate rows
                },
                margin: { top: 10 }
            });
            
            // Add floral decoration (text-based)
            doc.setFontSize(30);
            doc.setTextColor(214, 51, 132);
            doc.text('🌸', 20, 20);
            doc.text('🌼', doc.internal.pageSize.width - 20, doc.internal.pageSize.height - 10);
            
            // Save the PDF
            doc.save(`<?= $translations['export_filename'] ?>_${date}.pdf`);
        });
    </script>
</body>
</html>