<?php
// Включване на конфигурацията за връзка с базата данни
include('config.php');

// Стартиране на сесията в началото на файла
session_start();

// Променливи за съобщения при грешка и успех
$error = '';
$success = '';

// Обработка на регистрацията
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    // Вземане на потребителското име и парола от формата
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Проверка дали има празни полета
    if (empty($username) || empty($password)) {
        $error = 'Потртебителско име и парола са задължителни.';
    } else {
        // Проверка дали потребителското име вече съществува в базата данни
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->rowCount() > 0) {
            $error = 'Потребителя вече съществува.';
        } else {
            // Хеширане на паролата преди да я съхраним в базата данни
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Вмъкване на новия потребител в базата данни
            $sql = "INSERT INTO users (username, password) VALUES (:username, :password)";
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute(['username' => $username, 'password' => $hashed_password]);
                $success = 'Регистрацията е успешна. Може да се впишете.';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Обработка на логването
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    // Вземане на потребителското име и парола от формата
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Проверка дали има празни полета
    if (empty($username) || empty($password)) {
        $error = 'Потртебителско име и парола са задължителни.';
    } else {
        // Вземане на потребителя от базата данни
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);

        // Ако потребителят съществува
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Проверка дали паролата е валидна
            if (password_verify($password, $user['password'])) {
                // Записване на потребителското име и ID в сесията и пренасочване към таблото
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $user['id']; // Добавяне на user_id в сесията
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Невалидна парола.';
            }
        } else {
            $error = 'Потребителското име не беше намерено.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Auth | ZipLink</title>
    <link rel="stylesheet" type="text/css" href="css/auth.css" />
    <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
  </head>
  <body>
    <div class="container">
      <div class="forms-container">
        <div class="signin-signup">
          <!-- Форма за вписване -->
          <form action="auth.php" method="POST" class="sign-in-form">
            <h2 class="title">Впиши се</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" name="username" placeholder="Потребителско име" required />
            </div>
            <div class="input-field">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" placeholder="Парола" required />
            </div>
            <input type="submit" value="Вписване" class="btn solid" name="login" />
          </form>

          <!-- Форма за регистрация -->
          <form action="auth.php" method="POST" class="sign-up-form">
            <h2 class="title">Регистрация</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="input-field">
              <i class="fas fa-user"></i>
              <input type="text" name="username" placeholder="Потребителско име" required />
            </div>
            <div class="input-field">
              <i class="fas fa-lock"></i>
              <input type="password" name="password" placeholder="Парола" required />
            </div>
            <input type="submit" value="Регистрация" class="btn solid" name="register" />
          </form>
        </div>
      </div>

      <div class="panels-container">
        <div class="panel left-panel">
          <div class="content">
            <h3>Нови сте тук?</h3>
            <p>Регистрацията е безплатна и се прави за под 2 минути!</p>
            <button class="btn transparent" id="sign-up-btn">Регистрация</button>
          </div>
          <img src="assets/img/log.svg" class="image" alt="" />
        </div>

        <div class="panel right-panel">
          <div class="content">
            <h3>Един от нас?</h3>
            <p>Вече имаш регистрация и искаш да се впишеш?</p>
            <button class="btn transparent" id="sign-in-btn">Вписване</button>
          </div>
          <img src="assets/" class="image" alt="" />
        </div>
      </div>
    </div>
    <script src="js/app.js"></script>
  </body>
</html>
