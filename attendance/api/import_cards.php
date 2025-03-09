<?php
$host = 'localhost';
$dbname = 'attendance';
$username = 'admin';
$password = 'admin';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $csvFile = '3AHIT.csv'; // Pfad zur CSV-Datei
    $fileHandle = fopen($csvFile, 'r');

    if ($fileHandle !== false) {
        // Überspringe die Kopfzeile
        fgetcsv($fileHandle);

        while (($data = fgetcsv($fileHandle, 1000, ",")) !== false) {
            $email = $data[0];
            $nfc = $data[5];

            // Extrahiere den Benutzernamen aus der E-Mail
            $username = explode('@', $email)[0];

            // Füge die NFC-ID in die student_cards-Tabelle ein
            $stmt = $pdo->prepare("INSERT INTO student_cards (card_id, student_username) VALUES (:card_id, :student_username) ON DUPLICATE KEY UPDATE card_id = :card_id");
            $stmt->execute([
                ':card_id' => $nfc,
                ':student_username' => $username
            ]);
        }

        fclose($fileHandle);
        echo "Daten erfolgreich importiert!";
    } else {
        echo "Fehler beim Öffnen der CSV-Datei.";
    }
} catch (PDOException $e) {
    echo "Verbindung fehlgeschlagen: " . $e->getMessage();
}