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

// Check if all questions are answered
$allAnswered = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answers'])) {
    // Check if all textareas have content
    $allAnswered = true;
    foreach ($_SESSION['current_questions'] as $question) {
        if (empty($_POST['answer'][$question['id']])) {
            $allAnswered = false;
            break;
        }
    }
    
    if ($allAnswered) {
        $_SESSION['answers'] = $_POST['answer'];
        // You would typically send this to an API here
        // For now we'll just set a flag to show the suggestion
        $_SESSION['show_suggestion'] = true;
    }
}

// Enable error reporting
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
    unset($_SESSION['show_suggestion']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['current_questions'])) {
    shuffle($questions);
    $_SESSION['current_questions'] = array_slice($questions, 0, 10);
    $_SESSION['questions_id'] = uniqid();
}

$selected_questions = $_SESSION['current_questions'];

// Sample suggestions based on emotion (in a real app, you'd call an API)
$emotionSuggestions = [
    'happy' => [
        'en' => "You seem to be in a great mood! Why not spread that joy by doing something kind for someone today?",
        'et' => "Tundub, et oled suurepärases tujus! Miks mitte jagada seda rõõmu tehinguga head teistele?",
        'ru' => "Кажется, у вас отличное настроение! Почему бы не поделиться этой радостью, сделав что-то доброе для кого-то сегодня?"
    ],
    'sad' => [
        'en' => "I notice you might be feeling down. Remember to be kind to yourself. Maybe a short walk in nature could help?",
        'et' => "Paistab, et võid tunda end pisut masendunult. Pea meeles, et olla enda vastu lahke. Võib-olla lühike jalutuskäik looduses aitab?",
        'ru' => "Кажется, вам немного грустно. Помните, что нужно быть добрым к себе. Может, короткая прогулка на природе поможет?"
    ],
    'angry' => [
        'en' => "It seems you might be feeling frustrated. Deep breathing exercises can help calm the mind. Try counting to 10 slowly.",
        'et' => "Tundub, et võid tunda end frustreeritult. Sügav hingamine võib aidata rahustada meelt. Proovi aeglaselt kümneni lugeda.",
        'ru' => "Кажется, вы чувствуете раздражение. Упражнения на глубокое дыхание могут помочь успокоить ум. Попробуйте медленно сосчитать до десяти."
    ],
    'neutral' => [
        'en' => "You seem balanced today. This is a great time for self-reflection or trying something new!",
        'et' => "Tundud täna tasakaalukas. See on suurepärane aeg eneserefleksiooniks või millegi uue proovimiseks!",
        'ru' => "Вы кажетесь уравновешенным сегодня. Это отличное время для саморефлексии или пробования чего-то нового!"
    ]
];

// Default to neutral if emotion not set
$userEmotion = $_SESSION['user_emotion'] ?? 'neutral';
$aiSuggestion = $emotionSuggestions[$userEmotion][$_SESSION['lang']] ?? $emotionSuggestions['neutral'][$_SESSION['lang']];
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


            <form id="questions-form" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                <div class="slider-container">
                    <?php foreach ($selected_questions as $index => $question): ?>
                        <div class="question-slide <?= $index === 0 ? 'active' : '' ?>">
                            <label class="form-label fw-bold">🌷 <?= ($index + 1) ?>.
                                <?= $question[$_SESSION['lang']] ?></label>
                            <textarea class="form-control text-white" name="answer[<?= $question['id'] ?>]" rows="5"
                                placeholder="<?= $translations['answer_placeholder'] ?>"></textarea>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4 action-buttons">
                    <button type="button" id="prevBtn" class="btn btn-secondary">←</button>
                    <button type="button" id="nextBtn" class="btn btn-secondary">→</button>
                </div>
                <input type="hidden" name="submit_answers" value="1">
            </form>
            <?php if (isset($_SESSION['show_suggestion']) && $_SESSION['show_suggestion']): ?>
                <div class="ai-suggestion-container mt-5 animate__animated animate__fadeIn">
                    <div class="ai-suggestion-card p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="ai-icon me-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M8 13A5 5 0 1 1 8 3a5 5 0 0 1 0 10zm0-1A4 4 0 1 0 8 4a4 4 0 0 0 0 8z"/>
                                    <path d="M9 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM6 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm5 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm-3 3a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                </svg>
                            </div>
                            <h3 class="mb-0"><?= $translations['ai_suggestion_title'] ?? 'AI Suggestion' ?></h3>
                        </div>
                        <div class="ai-suggestion-text">
                            <p><?= $aiSuggestion ?></p>
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-light" onclick="window.location.reload()">
                                <?= $translations['try_again_button'] ?? 'Try Again' ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

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
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script>
        const confettiBtn = document.querySelector('button[name="new_questions"]');
        confettiBtn.addEventListener('click', () => {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        });
    </script>

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
    <script>
// Enhanced slider functionality with animations
const slides = document.querySelectorAll('.question-slide');
const progressBar = document.createElement('div');
progressBar.className = 'progress-bar';
const progressContainer = document.createElement('div');
progressContainer.className = 'progress-container';
progressContainer.appendChild(progressBar);
document.querySelector('.slider-container').prepend(progressContainer);

let currentSlide = 0;

// Update progress bar
function updateProgress() {
  const progress = ((currentSlide + 1) / slides.length) * 100;
  progressBar.style.width = `${progress}%`;
}

// Enhanced slide transition
function showSlide(index, direction = 'right') {
  // Exit current slide
  const currentActive = document.querySelector('.question-slide.active');
  if (currentActive) {
    currentActive.classList.remove('active');
    currentActive.classList.add(`exit-${direction}`);
  }

  // Update current index
  currentSlide = (index + slides.length) % slides.length;
  
  // Enter new slide
  setTimeout(() => {
    slides.forEach(slide => {
      slide.classList.remove('active', 'exit-left', 'exit-right');
    });
    slides[currentSlide].classList.add('active');
    updateProgress();
    
    // Add "pulse" effect when slide changes
    slides[currentSlide].style.animation = 'none';
    void slides[currentSlide].offsetWidth; // Trigger reflow
    slides[currentSlide].style.animation = 'float 6s ease-in-out infinite';
  }, 500);
}

// Create floating petals
function createPetals() {
  const petalImages = ['🌸', '🌼', '🌺', '🌻', '🌷', '💮', '🏵️'];
  const container = document.querySelector('.questions-card');
  
  for (let i = 0; i < 15; i++) {
    const petal = document.createElement('div');
    petal.className = 'petal';
    petal.textContent = petalImages[Math.floor(Math.random() * petalImages.length)];
    petal.style.left = `${Math.random() * 100}%`;
    petal.style.top = `${Math.random() * 100}%`;
    petal.style.fontSize = `${Math.random() * 20 + 10}px`;
    petal.style.animationDuration = `${Math.random() * 10 + 10}s`;
    petal.style.animationDelay = `${Math.random() * 5}s`;
    container.appendChild(petal);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  // Initialize first slide
  showSlide(0);
  createPetals();
  
  // Navigation buttons
  document.getElementById('nextBtn').addEventListener('click', () => {
    showSlide(currentSlide + 1, 'left');
  });

  document.getElementById('prevBtn').addEventListener('click', () => {
    showSlide(currentSlide - 1, 'right');
  });

  // Keyboard navigation
  document.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight') showSlide(currentSlide + 1, 'left');
    if (e.key === 'ArrowLeft') showSlide(currentSlide - 1, 'right');
  });
  
  // Add hover effects to buttons
  const buttons = document.querySelectorAll('.btn-slide');
  buttons.forEach(btn => {
    btn.addEventListener('mouseenter', () => {
      btn.style.transform = 'scale(1.1)';
    });
    btn.addEventListener('mouseleave', () => {
      btn.style.transform = 'scale(1)';
    });
  });
});
</script>
<script>
// Enhanced slider functionality with animations
const slides = document.querySelectorAll('.question-slide');
const progressBar = document.createElement('div');
progressBar.className = 'progress-bar';
const progressContainer = document.createElement('div');
progressContainer.className = 'progress-container';
progressContainer.appendChild(progressBar);
document.querySelector('.slider-container').prepend(progressContainer);

let currentSlide = 0;
const form = document.getElementById('questions-form');
const submitBtn = document.createElement('button');
submitBtn.type = 'button';
submitBtn.id = 'submit-answers';
submitBtn.className = 'btn btn-pink btn-lg';
submitBtn.innerHTML = '<?= $translations["submit_answers_button"] ?? "Submit Answers" ?> 🌟';
submitBtn.style.display = 'none';
document.querySelector('.action-buttons').appendChild(submitBtn);

// Update progress bar
function updateProgress() {
    const progress = ((currentSlide + 1) / slides.length) * 100;
    progressBar.style.width = `${progress}%`;
    
    // Check if all textareas have content
    const allAnswered = Array.from(document.querySelectorAll('textarea')).every(ta => ta.value.trim() !== '');
    
    // Show/hide submit button
    submitBtn.style.display = allAnswered ? 'block' : 'none';
}

// Enhanced slide transition
function showSlide(index, direction = 'right') {
    // Exit current slide
    const currentActive = document.querySelector('.question-slide.active');
    if (currentActive) {
        currentActive.classList.remove('active');
        currentActive.classList.add(`exit-${direction}`);
    }

    // Update current index
    currentSlide = (index + slides.length) % slides.length;
    
    // Enter new slide
    setTimeout(() => {
        slides.forEach(slide => {
            slide.classList.remove('active', 'exit-left', 'exit-right');
        });
        slides[currentSlide].classList.add('active');
        updateProgress();
        
        // Add "pulse" effect when slide changes
        slides[currentSlide].style.animation = 'none';
        void slides[currentSlide].offsetWidth; // Trigger reflow
        slides[currentSlide].style.animation = 'float 6s ease-in-out infinite';
    }, 500);
}

// Create floating petals
function createPetals() {
    const petalImages = ['🌸', '🌼', '🌺', '🌻', '🌷', '💮', '🏵️'];
    const container = document.querySelector('.questions-card');
    
    for (let i = 0; i < 15; i++) {
        const petal = document.createElement('div');
        petal.className = 'petal';
        petal.textContent = petalImages[Math.floor(Math.random() * petalImages.length)];
        petal.style.left = `${Math.random() * 100}%`;
        petal.style.top = `${Math.random() * 100}%`;
        petal.style.fontSize = `${Math.random() * 20 + 10}px`;
        petal.style.animationDuration = `${Math.random() * 10 + 10}s`;
        petal.style.animationDelay = `${Math.random() * 5}s`;
        container.appendChild(petal);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Initialize first slide
    showSlide(0);
    createPetals();
    
    // Navigation buttons
    document.getElementById('nextBtn').addEventListener('click', () => {
        showSlide(currentSlide + 1, 'left');
    });

    document.getElementById('prevBtn').addEventListener('click', () => {
        showSlide(currentSlide - 1, 'right');
    });

    // Keyboard navigation
    document.addEventListener('keydown', e => {
        if (e.key === 'ArrowRight') showSlide(currentSlide + 1, 'left');
        if (e.key === 'ArrowLeft') showSlide(currentSlide - 1, 'right');
    });
    
    // Textarea input listener
    document.querySelectorAll('textarea').forEach(ta => {
        ta.addEventListener('input', updateProgress);
    });
    
    // Submit button handler
    submitBtn.addEventListener('click', () => {
        // Create hidden form and submit
        const hiddenForm = document.createElement('form');
        hiddenForm.method = 'POST';
        hiddenForm.style.display = 'none';
        
        const submitInput = document.createElement('input');
        submitInput.type = 'hidden';
        submitInput.name = 'submit_answers';
        submitInput.value = '1';
        hiddenForm.appendChild(submitInput);
        
        // Add all textarea values
        document.querySelectorAll('textarea').forEach(ta => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = ta.name;
            input.value = ta.value;
            hiddenForm.appendChild(input);
        });
        
        document.body.appendChild(hiddenForm);
        hiddenForm.submit();
    });
});
submitBtn.addEventListener('click', () => {
    form.submit();
});
</script>

</body>

</html>