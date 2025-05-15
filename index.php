<?php
session_start();

require_once('db_connect.php'); // Include your database connection file
$show_help = false;
// Generate a unique identifier based on IP and User-Agent
$device_identifier = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);

// Connect to the database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// Check the connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Check if this device has been recorded already
$query = "SELECT * FROM device_tracker WHERE device_identifier = '$device_identifier'";
$result = $conn->query($query);

// If not found, insert a new device record
if ($result->num_rows === 0) {
  $insert_query = "INSERT INTO device_tracker (device_identifier) VALUES ('$device_identifier')";
}

// Optionally, you can count unique devices
$query_count = "SELECT COUNT(*) AS device_count FROM device_tracker";
$result_count = $conn->query($query_count);
$row = $result_count->fetch_assoc();

// Close the database connection
$conn->close();

// Language setup (no change)
$available_langs = ['et' => 'Eesti', 'en' => 'English', 'ru' => 'Русский'];

// Check for lang in URL first
$lang = $_GET['lang'] ?? null;

// If not set, check session
if (!$lang && isset($_SESSION['lang'])) {
  $lang = $_SESSION['lang'];
}

// If still not set, try browser settings
if (!$lang && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
  // Extract primary language code (e.g., "en-US" → "en")
  $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
  if (array_key_exists($browser_lang, $available_langs)) {
    $lang = $browser_lang;
  }
}

// Default to Estonian
if (!$lang || !array_key_exists($lang, $available_langs)) {
  $lang = 'et';
}

// Store in session
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
  <link rel="icon" href="assets/images/logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/index.css" !important />
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

    <form method="POST" action="questions.php">
      <div class="form-floating text-dark">
        <input type="text" class="form-control" id="name" name="name" placeholder="<?= $translations['name_prompt'] ?>"
          required>
        <label for="name"><?= $translations['name_prompt'] ?></label>
      </div>
      <div class="mb-3">
        <label class="form-label d-block text-white fw-semibold" style="font-size: 1.1rem;">
          <?= $translations['emotion_prompt'] ?? 'How are you feeling today?' ?>
        </label>
        <div class="emoji-picker">
          <input type="radio" name="emotion" id="happy" value="happy" checked />
          <label for="happy" title="<?= $translations['emotion_happy'] ?? 'Happy' ?>">😊</label>

          <input type="radio" name="emotion" id="okei" value="okei" />
          <label for="okei" title="<?= $translations['emotion_okei'] ?? 'Okei' ?>">😐</label>

          <input type="radio" name="emotion" id="sad" value="sad" />
          <label for="sad" title="<?= $translations['emotion_sad'] ?? 'Sad' ?>">😕</label>

          <input type="radio" name="emotion" id="very_sad" value="very_sad" />
          <label for="very_sad" title="<?= $translations['emotion_very_sad'] ?? 'Very Sad' ?>">😢</label>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><?= $translations['continue_button'] ?></button>
    </form>

    <div class="text-center mt-3">
      <form method="POST" action="questions.php">
        <input type="hidden" name="returning_user" value="1" />
        <button type="submit" class="btn btn-link text-light"><?= $translations['returning_user'] ?></button>
      </form>
    </div>

    <div class="privacy-text rounded-2 p-4">
      <?= $translations['privacy_notice'] ?>
    </div>
    <div class="text-center mt-4">
      <button class="btn btn-outline-light" onclick="toggleHelp()">
        <?= $translations['help_button'] ?>
      </button>

    <div id="helpInfo"
      style="display: <?= $show_help ? 'block' : 'none' ?>; margin-top: 1rem; font-size: 0.9rem; line-height: 1.6;">
      <strong><?= $translations['help_title'] ?></strong><br>
      <ul style="list-style: none; padding: 0;">
        <li>📞 <?= $translations['help_estonia'] ?></li>
        <li>🌐 <a href="https://findahelpline.com" target="_blank"
            style="color: #fff; text-decoration: underline;"><?= $translations['help_global'] ?></a></li>
        <li>🌐 <a href="https://www.peaasi.ee" style="color: #fff; text-decoration: underline;"
            target="_blank"><?= $translations['help_eesti'] ?></a></li>
        <li>🌐 <a href="https://www.eluliin.ee" style="color: #fff; text-decoration: underline;"
            target="_blank"><?= $translations['help_kriis'] ?></a></li>
        <li>🧠 <?= $translations['help_local'] ?></li>
      </ul>
    </div>
    <button class="btn btn-outline-light ms-2" onclick="toggleAbout()">
        <?= $translations['about_button'] ?>
      </button>
    <div id="aboutInfo" style="display: <?= $show_help ? 'block' : 'none' ?>; margin-top: 1rem; font-size: 0.9rem; line-height: 1.6;" class="text-white text-start">
      <strong><?= $translations['about_title'] ?></strong><br>
      <p><?= nl2br($translations['about_text']) ?></p>
    </div>

  </div>
  </div>
  <script>
    function toggleHelp() {
      const helpInfo = document.getElementById('helpInfo');
      const wasHidden = helpInfo.style.display === 'none';
      helpInfo.style.display = wasHidden ? 'block' : 'none';
      if (wasHidden) {
        helpInfo.scrollIntoView({ behavior: 'smooth' });
      }
    }

    <?php if ($show_help): ?>
      window.addEventListener('load', () => {
        const helpInfo = document.getElementById('helpInfo');
        if (helpInfo) helpInfo.scrollIntoView({ behavior: 'smooth' });
      });
    <?php endif; ?>
  </script>
  <script>
  function toggleAbout() {
    const aboutInfo = document.getElementById('aboutInfo');
    const helpInfo = document.getElementById('helpInfo');
    const wasHidden = aboutInfo.style.display === 'none';
    
    // Hide help info if shown
    helpInfo.style.display = 'none';
    aboutInfo.style.display = wasHidden ? 'block' : 'none';

    if (wasHidden) {
      aboutInfo.scrollIntoView({ behavior: 'smooth' });
    }
  }
</script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>