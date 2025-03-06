<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    header("Location: ../html/login.php");
    exit();
} elseif ($_SESSION['isTeacher'] !== 1) {
    header("Location: ../index.php");
    exit();
}

// Datenbankverbindung
$host = 'localhost';
$dbname = 'attendance';
$username = 'admin';
$password = 'admin';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

try {
    $stmt = $pdo->query('SELECT DISTINCT class FROM students');
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($classes);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>