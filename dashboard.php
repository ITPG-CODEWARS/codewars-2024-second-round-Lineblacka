<?php
// Стартиране на сесията
session_start();
// Включване на конфигурацията за връзка с базата данни
include('config.php'); 

// Пренасочване към логин страницата, ако потребителят не е автентикиран
if (!isset($_SESSION['username'])) {
    header('Location: auth.php');
    exit;
}

// Вземане на потребителското име и ID от сесията
$username = htmlspecialchars($_SESSION['username']);
$user_id = $_SESSION['user_id'];
$message = '';

// Функция за генериране на съкратен URL код
function generateShortUrl($length = 6) {
    return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, $length);
}

// Обработка на формата за съкращаване на URL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_url'])) {
    $original_url = trim($_POST['url']);
    if (!empty($original_url)) {
        $shortened_url = generateShortUrl();

        // Вмъкване на оригиналния и съкратен URL в базата данни
        $stmt = $pdo->prepare("INSERT INTO urls (user_id, original_url, shortened_url) VALUES (:user_id, :original_url, :shortened_url)");
        try {
            $stmt->execute(['user_id' => $user_id, 'original_url' => $original_url, 'shortened_url' => $shortened_url]);
            $message = "URL адресът е съкратен успешно: <a href='shorten.php?u=$shortened_url'>$shortened_url</a>";
        } catch (PDOException $e) {
            $message = "Неуспешно съкращаване на URL: " . $e->getMessage();
        }
    } else {
        $message = "Моля, въведете валиден URL адрес.";
    }
}

// Обработка на формата за изтриване на URL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_url'])) {
    $url_id = $_POST['url_id'];
    // Изтриване на URL от базата данни
    $stmt = $pdo->prepare("DELETE FROM urls WHERE id = :id AND user_id = :user_id");
    $stmt->execute(['id' => $url_id, 'user_id' => $user_id]);
    $message = "URL адресът е изтрит успешно.";
}

// Обработка на формата за актуализиране на съкратен URL
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_url'])) {
    $url_id = $_POST['url_id'];
    $new_shortened_url = trim($_POST['new_shortened_url']);
    
    // Проверка дали новият съкратен URL код е уникален
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM urls WHERE shortened_url = :shortened_url AND id != :url_id");
    $check_stmt->execute(['shortened_url' => $new_shortened_url, 'url_id' => $url_id]);
    if ($check_stmt->fetchColumn() > 0) {
        $message = "Този съкратен URL код вече е използван. Моля, изберете друг код.";
    } else {
        // Актуализиране на съкратен URL в базата данни
        $stmt = $pdo->prepare("UPDATE urls SET shortened_url = :shortened_url WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['shortened_url' => $new_shortened_url, 'id' => $url_id, 'user_id' => $user_id]);
        $message = "Съкратеният URL е актуализиран успешно.";
    }
}

// Вземане на всички съкратени URL адреси за текущия потребител
$stmt = $pdo->prepare("SELECT id, original_url, shortened_url FROM urls WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$urls = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ZipLink</title>
    <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
    <link href="css/styles.css" rel="stylesheet" />
</head>
<body>
    <!-- Навигационно меню -->
    <nav class="navbar navbar-light bg-light static-top">
        <div class="container">
            <a class="navbar-brand" href="#!">
                <img src="assets/img/logo.png" alt="ZipLink logo" style="height: 40px;"> <!-- Регулиране на височината -->
            </a>
        </div>
    </nav>
    
    <!-- Хедър секция -->
    <header class="masthead">
        <div class="container position-relative">
            <div class="row justify-content-center">
                <div class="col-xl-6">
                    <div class="text-center text-white">
                        <!-- Заглавие на страницата -->
                        <h1 class="mb-5">Добре дошъл, <?php echo $username; ?>!</h1>
                        <!-- Форма за съкращаване на URL -->
                        <form action="dashboard.php" method="POST" class="form-subscribe" id="contactForm">
                            <div class="row">
                                <div class="col">
                                    <input class="form-control form-control-lg" type="url" id="url" name="url" placeholder="URL Адрес" required />
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-primary btn-lg" name="submit_url" type="submit">Съкрати</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <br>
    <div style="text-align: center;">
        <?php if (!empty($urls)): ?>
            <h2>Вашите съкратени адреси</h2>
            <table border="1" style="margin: 0 auto;">
                <tr>
                    <th>Оригинал</th>
                    <th>Съкратен</th>
                    <th>Действие</th>
                </tr>
                <?php foreach ($urls as $url): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($url['original_url']); ?></td>
                        <td><a href="shorten.php?u=<?php echo htmlspecialchars($url['shortened_url']); ?>" target="_blank"><?php echo htmlspecialchars($url['shortened_url']); ?></a></td>
                        <td>
                            <!-- Форма за изтриване на URL -->
                            <form action="dashboard.php" method="POST" style="display:inline;">
                                <input type="hidden" name="url_id" value="<?php echo $url['id']; ?>">
                                <button type="submit" name="delete_url" class="delete-button" onclick="return confirm('Сигурни ли сте, че искате да изтриете адреса?');">Изтрий</button>
                            </form>
                            <!-- Форма за актуализиране на съкратен URL -->
                            <form action="dashboard.php" method="POST" style="display:inline;">
                                <input type="hidden" name="url_id" value="<?php echo $url['id']; ?>">
                                <input type="text" name="new_shortened_url" placeholder="Нов съкратен вариант" required>
                                <button type="submit" class="update-button" name="update_url">Промени</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <h2>Няма открити съкратени адреси</h2>
        <?php endif; ?>
    </div>

    <style>
        .delete-button {
            background-color: #9c0909;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 5px 10px;
            font-size: 16px;
            cursor: pointer;
        }

        .delete-button:focus {
            outline: none;
        }

        .delete-button:hover {
            background-color: #b31111;
        }

        .update-button {
            background-color: #41bf97;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 5px 10px;
            font-size: 16px;
            cursor: pointer;
        }

        .update-button:focus {
            outline: none;
        }

        .update-button:hover {
            background-color: #41bf97;
        }
    </style>
</body>
</html>
