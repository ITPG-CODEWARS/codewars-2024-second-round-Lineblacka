<?php
// Задаваме параметрите за свързване към базата данни
$host = 'localhost';  // Адрес на сървъра (локален сървър)
$dbname = 'ziplink_db';  // Името на базата данни
$username = 'root';  // Потребителско име за достъп до MySQL (в случая root)
$password = '';  // Парола за достъп (празна в този пример)

// Опитваме се да създадем връзка с базата данни чрез PDO
try {
    // Създаваме нов обект PDO за свързване с базата данни
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);

    // Настройваме атрибутите на PDO за обработка на грешки
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Ако връзката не може да бъде установена, извеждаме съобщение за грешка
    die("Connection failed: " . $e->getMessage());
}
?>
