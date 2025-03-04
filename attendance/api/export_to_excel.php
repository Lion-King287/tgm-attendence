<?php
    session_start();
    require 'ExcelWriter.php';

    try {
        // Empfange die JSON-Daten
        $data = file_get_contents('php://input');

        // Dekodiere die JSON-Daten
        $decodedData = json_decode($data, true);

        // Überprüfe, ob die Dekodierung erfolgreich war
        if ($decodedData === null) {
            throw new Exception('Invalid JSON data');
        }

        // Speichere die Daten in Variablen
        $room = $decodedData['room'];
        $date = $decodedData['date'];
        $units = explode(', ', $decodedData['units']);
        $subject = $decodedData['subject'];
        $teacherShortName = $decodedData['teacherShortName'];
        $students = $decodedData['students'];
        $accessToken = $_SESSION['microsoft_token'];

        // Wochentag und Spaltenzuordnung
        $dayOfWeek = date('N', strtotime($date));
        $unitColumns = [
            1 => ['F', 'R', 'AD', 'AP', 'BB'],
            2 => ['G', 'S', 'AE', 'AQ', 'BC'],
            3 => ['H', 'T', 'AF', 'AR', 'BD'],
            4 => ['I', 'U', 'AG', 'AS', 'BE'],
            5 => ['J', 'V', 'AH', 'AT', 'BF'],
            6 => ['K', 'W', 'AI', 'AU', 'BG'],
            7 => ['L', 'X', 'AJ', 'AV', 'BH'],
            8 => ['M', 'Y', 'AK', 'AW', 'BI'],
            9 => ['N', 'Z', 'AL', 'AX', 'BJ'],
            10 => ['O', 'AA', 'AM', 'AY', 'BK'],
            11 => ['P', 'AB', 'AN', 'AZ', 'BL']
        ];

        // Unterrichtswoche (hardcoded)
        $sheetName = '23';

        // Gruppiere Schüler nach Klassen
        $classes = [];
        foreach ($students as $student) {
            $classes[$student['class']][] = $student;
        }

        // Erstelle pro Klasse eine ExcelWriter-Instanz und schreibe die Daten
        foreach ($classes as $className => $classStudents) {
            $filePath = "{$className}.xlsm";

            $excelWriter = new ExcelWriter($accessToken, $filePath);

            // Schreibe den Lehrerkürzel in die Zeile 7
            foreach ($units as $unit) {
                $column = $unitColumns[$unit][$dayOfWeek - 1];
                $teacherCell = "{$column}7";

                // Lese den aktuellen Wert der Zelle
                $currentValue = $excelWriter->readFromExcelCell($sheetName, $teacherCell);

                // Füge den neuen Lehrerkürzel hinzu
                if ($currentValue) {
                    $newValue = $currentValue . '/' . $teacherShortName;
                } else {
                    $newValue = $teacherShortName;
                }

                $excelWriter->writeToExcelRangeWithGaps($sheetName, [$teacherCell => $newValue]);
            }

            // Schreibe die Schülerdaten
            $cells = [];
            foreach ($classStudents as $student) {
                foreach ($units as $unit) {
                    $column = $unitColumns[$unit][$dayOfWeek - 1];
                    $row = $student['catalog_number'] + 9;
                    $cell = "{$column}{$row}";
                    $cells[$cell] = $subject;
                }
            }

            $excelWriter->writeToExcelRangeWithGaps($sheetName, $cells);
        }

        // Bestätigungsnachricht zurückgeben
        echo json_encode(['success' => 'Data successfully exported to Excel']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    ?>