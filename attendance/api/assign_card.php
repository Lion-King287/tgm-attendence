<?php
session_start();

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    header("Location: html/login.php");
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

// Eingabedaten aus der Anfrage lesen
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['card_id']) || !isset($data['student_username'])) {
    echo json_encode(['success' => false, 'error' => 'Missing card_id or student_username']);
    exit;
}

$card_id = $data['card_id'];
$student_username = $data['student_username'];

try {
    // Überprüfen, ob die Karte bereits einem anderen Schüler zugewiesen ist
    $stmt = $pdo->prepare('SELECT * FROM student_cards WHERE card_id = ?');
    $stmt->execute([$card_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Card already assigned to another student']);
        exit;
    }

    // Karte dem Schüler zuweisen
    $stmt = $pdo->prepare('INSERT INTO student_cards (card_id, student_username) VALUES (?, ?)');
    $stmt->execute([$card_id, $student_username]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>