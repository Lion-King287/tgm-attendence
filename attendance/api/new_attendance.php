<?php
require '../vendor/autoload.php';

use WebSocket\Client;

// Konfiguration
$host = 'localhost';  // Datenbankhost
$dbname = 'attendance';  // Datenbankname
$username = 'admin';  // Datenbankbenutzername
$password = 'admin';  // Datenbankpasswort

// Verbindung zur Datenbank herstellen
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Prüfen, ob alle Parameter übergeben wurden
if (!isset($_POST['api_key']) || !isset($_POST['room_name']) || !isset($_POST['card_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing parameters: api_key, room_name or card_id']);
    exit;
}

// Eingabewerte aus der POST-Anfrage holen
$apiKey = $_POST['api_key'];
$roomName = $_POST['room_name'];
$cardId = $_POST['card_id'];

// API-Key und Raumnummer validieren
$stmt = $pdo->prepare('SELECT * FROM api_keys_rooms WHERE api_key = ? AND room_name = ?');
$stmt->execute([$apiKey, $roomName]);
$apiData = $stmt->fetch();

if (!$apiData) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Invalid combination of API key and room name']);
    exit;
}

// Datenbank nach der Person mit der angegebenen card_id durchsuchen
$stmt = $pdo->prepare('SELECT * FROM students s JOIN student_cards sc ON s.username = sc.student_username WHERE sc.card_id = ?');
$stmt->execute([$cardId]);
$personData = $stmt->fetch();

// WebSocket-Nachricht senden
$wsData = '';

if (!$personData) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['error' => 'No person found with the given card ID']);

    $wsData = json_encode([
        'card_id' => $cardId,
        'firstname' => null,
        'lastname' => null,
        'class' => null,
        'catalog_number' => null,
        'login_timestamp' => null
    ]);
} else {
    // Antwort zurückgeben
    $response = [
        'first_name' => $personData['firstname'],
        'last_name' => $personData['lastname'],
        'username' => $personData['username'],
        'class' => $personData['class']
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

    $wsData = json_encode([
        'card_id' => $cardId,
        'firstname' => $personData['firstname'],
        'lastname' => $personData['lastname'],
        'class' => $personData['class'],
        'catalog_number' => $personData['catalog_number'],
        'login_timestamp' => round(microtime(true) * 1000)
    ]);
}

try {
    $token = 'czEZ3TDDWLmk8lXgJKVtcmrs6SOE8PW7ehBlpTW6EVeYaLxD7RlqKT9vdhL91pZU'; // Replace with actual token generation logic
    $client = new Client("ws://localhost:8080");
    $client->send(json_encode(['action' => 'authenticate', 'token' => $token]));
    $client->send($wsData);
    $client->close();
} catch (Exception $e) {
    error_log("WebSocket-Fehler: " . $e->getMessage());
}
?>