<?php
session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: index.php');
    exit;
}

$name = $_SESSION['name'] ?? 'Külaline';
?>

<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Küsimused</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .question-btn {
            height: 100px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .welcome-header {
            background-color: #6c757d;
            color: white;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="welcome-header text-center">
            <h1>Tere, <?php echo htmlspecialchars($name); ?>!</h1>
            <p>Vali küsimuste rubriik, millest soovid rohkem teada saada</p>
        </div>
        
        <div class="row">
            <?php
            // Defineerime küsimuste rubriigid
            $categories = [
                'Tehnoloogia', 'Sport', 'Kunst', 'Ajalugu', 
                'Matemaatika', 'Kirjandus', 'Muusika', 'Loodus',
                'Geograafia', 'Keeled', 'Tervis', 'Toit'
            ];
            
            foreach ($categories as $category):
            ?>
                <div class="col-md-4 col-sm-6">
                    <button class="btn btn-outline-primary w-100 question-btn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#questionModal"
                            data-category="<?php echo $category; ?>">
                        <?php echo $category; ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Küsimuste modal -->
    <div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="questionModalLabel">Küsimus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalQuestionContent">
                    <!-- Küsimus laaditakse siia JavaScriptiga -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sulge</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Küsimuste laadimine
        const questions = {
            'Tehnoloogia': 'Mis on su lemmik tehnoloogia seade ja miks?',
            'Sport': 'Millist spordiala pead kõige põnevamaks?',
            'Kunst': 'Kui sa peaksid valima ühe kuulsa maali, et seda igapäevaselt vaadata, milline see oleks?',
            'Ajalugu': 'Milline ajalooline sündmus sind kõige rohkem huvitab?',
            'Matemaatika': 'Kas sulle meeldis koolis matemaatika? Miks või miks mitte?',
            'Kirjandus': 'Milline on su lemmik raamat ja miks?',
            'Muusika': 'Mis muusikastiil on sulle kõige meeldivam?',
            'Loodus': 'Kus kohas looduses tunneed end kõige paremini?',
            'Geograafia': 'Kuhu maailma otsa soiksid kõige rohkem reisida?',
            'Keeled': 'Mitu keelt oskad ja millist keelt soiks veel õppida?',
            'Tervis': 'Mis on sinu lemmik tervislik toitumise nõuanne?',
            'Toit': 'Mis on su lemmikroog ja miks just see?'
        };
        
        // Kuula nuppude klõpsamist
        document.querySelectorAll('.question-btn').forEach(button => {
            button.addEventListener('click', function() {
                const category = this.getAttribute('data-category');
                const question = questions[category] || 'Palun vali teine rubriik.';
                document.getElementById('modalQuestionContent').textContent = question;
                document.getElementById('questionModalLabel').textContent = category;
            });
        });
    </script>
</body>
</html>