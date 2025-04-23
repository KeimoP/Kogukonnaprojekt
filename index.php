<?php
session_start();

try {
    require 'db_connect.php';

    $is_returning = isset($_POST['returning_user']) ? 1 : 0;
    $name = $_POST['name'] ?? null;
    $emotion = $_POST['emotion'] ?? null;

    if ($is_returning && !$name) {
        // This is the returning user button (no name, no emotion)
        header("Location: questions.php");
        exit();
    }

    $name = $name ?? 'Anonymous';
    $_SESSION['user_name'] = $name;

    $stmt = $conn->prepare("INSERT INTO visitors (name, is_returning, emotion_state, visit_time) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sis", $name, $is_returning, $emotion);

    if ($stmt->execute()) {
        $_SESSION['visitor_tracked'] = true;
        $_SESSION['visitor_id'] = $stmt->insert_id;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Tracking error: ".$e->getMessage());
}

// Language setup
$available_langs = ['et' => 'Eesti', 'en' => 'English', 'ru' => 'Русский'];
$lang = $_GET['lang'] ?? 'et';
if (!array_key_exists($lang, $available_langs)) {
    $lang = 'et';
}
$_SESSION['lang'] = $lang;

// Load translations
$translations = json_decode(file_get_contents("lang/{$lang}.json"), true);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $translations['welcome_title'] ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    body {
      background: linear-gradient(135deg, #1f1c2c, #928dab);
      font-family: 'Outfit', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
      color: #fff;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 20px;
      padding: 2rem;
      max-width: 450px;
      width: 100%;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.18);
      animation: fadeInUp 0.8s ease forwards;
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

    h1 {
      text-align: center;
      font-weight: 700;
      margin-bottom: 1.5rem;
      font-size: 2rem;
    }

    .form-floating {
      margin-bottom: 1rem;
    }

    .form-control {
      background-color: transparent;
      border: 1px solid #ffffff55;
      color: white;
    }

    .form-control:focus {
      border-color: #ffffffaa;
      box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
      background-color: transparent;
    }

    .btn-primary {
      background: linear-gradient(to right, #667eea, #764ba2);
      border: none;
      width: 100%;
      padding: 0.75rem;
      border-radius: 12px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      transform: scale(1.03);
      box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
    }

    .emoji-picker {
      display: flex;
      justify-content: space-between;
      margin: 1rem 0;
      font-size: 2rem;
      cursor: pointer;
      user-select: none;
    }

    .emoji-picker input[type="radio"] {
      display: none;
    }

    .emoji-picker label {
      transition: transform 0.2s ease;
    }

    .emoji-picker input[type="radio"]:checked + label {
      transform: scale(1.3);
      filter: drop-shadow(0 0 4px white);
    }

    .lang-select {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }

    .lang-select a {
      padding: 0.3rem 1rem;
      border-radius: 50px;
      background-color: rgba(255, 255, 255, 0.1);
      color: white;
      font-size: 0.9rem;
      text-decoration: none;
      border: 1px solid transparent;
    }

    .lang-select .active {
      border-color: white;
    }

    .privacy-text {
      font-size: 0.8rem;
      text-align: center;
      margin-top: 1rem;
      opacity: 0.8;
    }
  </style>
</head>
<body>
  <div class="glass-card">
    <h1><?= $translations['welcome_heading'] ?></h1>

    <div class="lang-select">
      <?php foreach ($available_langs as $code => $name): ?>
        <a href="?lang=<?= $code ?>" class="<?= $code === $lang ? 'active' : '' ?>">
          <?= $name ?>
        </a>
      <?php endforeach; ?>
    </div>

    <form method="POST" action="submit_emotion.php">
      <div class="form-floating">
        <input type="text" class="form-control" id="name" name="name" placeholder="<?= $translations['name_prompt'] ?>" required>
        <label for="name"><?= $translations['name_prompt'] ?></label>
      </div>
        
      <div class="mb-3">
        <label class="form-label d-block text-white fw-semibold" style="font-size: 1.1rem;">
            <?= $translations['emotion_prompt'] ?? 'How are you feeling today?' ?>
        </label>
        <div class="emoji-picker">
            <input type="radio" name="emotion" id="happy" value="good" checked />
            <label for="happy" title="<?= $translations['emotion_good'] ?? 'Good' ?>">😊</label>

            <input type="radio" name="emotion" id="okay" value="okay" />
            <label for="okay" title="<?= $translations['emotion_okay'] ?? 'Okay' ?>">😐</label>

            <input type="radio" name="emotion" id="bad" value="bad" />
            <label for="bad" title="<?= $translations['emotion_bad'] ?? 'Bad' ?>">😕</label>

            <input type="radio" name="emotion" id="very_sad" value="very_bad" />
            <label for="very_sad" title="<?= $translations['emotion_very_bad'] ?? 'Very Sad' ?>">😢</label>
        </div>
        </div>

      <button type="submit" class="btn btn-primary"><?= $translations['continue_button'] ?></button>
    </form>

    <div class="text-center mt-3">
      <form method="POST">
        <input type="hidden" name="returning_user" value="1" />
        <button type="submit" class="btn btn-link text-light"><?= $translations['returning_user'] ?></button>
      </form>
    </div>

    <div class="privacy-text">
      <?= $translations['privacy_notice'] ?>
    </div>
  </div>
</body>
</html>