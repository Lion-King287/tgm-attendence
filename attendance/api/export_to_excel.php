<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: ../html/login.php");
    exit();
} elseif ($_SESSION['isTeacher'] !== 1) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['microsoft_token'])) {
    http_response_code(500);
    echo json_encode(['error' => 'No access token found']);
    exit;
}

$accessToken = $_SESSION['microsoft_token'];
require 'ExcelClassbookHelper.php';

if (!ExcelClassbookHelper::isMicrosoftTokenValid($accessToken)) {
    http_response_code(500);
    echo json_encode(['error' => 'Access token is invalid']);
    exit;
}

try {
    $data = file_get_contents('php://input');
    $decodedData = json_decode($data, true);

    if ($decodedData === null) {
        throw new Exception('Invalid JSON data');
    }

    $room = $decodedData['room'];
    $date = $decodedData['date'];
    $units = explode(', ', $decodedData['units']);
    $subject = $decodedData['subject'];
    $cellSubject = $decodedData['cellSubject'];
    $teacherShortName = $decodedData['teacherShortName'];
    $students = $decodedData['students'];

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

    $sheetName = getTeachingWeek($date);

    if ($sheetName === null) {
        throw new Exception('Invalid sheet name');
    }

    $classes = [];
    foreach ($students as $student) {
        $classes[$student['class']][] = $student;
    }

    foreach ($classes as $className => $classStudents) {
        $filePath = $className;
        $excelWriter = new ExcelClassbookHelper($accessToken, $filePath, isset($_SESSION['useRealClassbook']) && $_SESSION['useRealClassbook'] === true);

        foreach ($units as $unit) {
            $column = $unitColumns[$unit][$dayOfWeek - 1];
            $teacherCell = "{$column}7";
            $subjectCell = "{$column}4";

            $currentValue = $excelWriter->readFromExcelCell($sheetName, $teacherCell);
            if (str_contains($currentValue, $teacherShortName)) {
                continue;
            }

            if ($currentValue) {
                $newValue = $currentValue . '/' . $teacherShortName;
            } else {
                $newValue = $teacherShortName;
            }

            $cells = [
                $teacherCell => $newValue,
                $subjectCell => $subject
            ];

            $excelWriter->writeToExcelRangeWithGaps($sheetName, $cells);
        }

        $cells = [];
        foreach ($classStudents as $student) {
            $remainingMinutes = !empty($student['late_minutes']) ? $student['late_minutes'] : 0;

            foreach ($units as $index => $unit) {
                $column = $unitColumns[$unit][$dayOfWeek - 1];
                $row = $student['catalog_number'] + 9;
                $cell = "{$column}{$row}";

                if (!empty($student['is_absent'])) {
                    $cells[$cell] = 'F';
                } else if ($remainingMinutes > 0) {
                    if ($remainingMinutes >= 50) {
                        $cells[$cell] = 'F';
                        $remainingMinutes -= 50;
                    } else {
                        $cells[$cell] = 'Z' . $remainingMinutes;
                        $remainingMinutes = 0;
                    }
                } else {
                    $cells[$cell] = $cellSubject;
                }
            }
        }

        $excelWriter->writeToExcelRangeWithGaps($sheetName, $cells);
    }

    echo json_encode(['success' => 'Data successfully exported to Excel']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getTeachingWeek($datum) {
    $unterrichtswochen = [
        ['start' => '2024-09-02', 'end' => '2024-09-08', 'UW' => 1],
        ['start' => '2024-09-09', 'end' => '2024-09-15', 'UW' => 2],
        ['start' => '2024-09-16', 'end' => '2024-09-22', 'UW' => 3],
        ['start' => '2024-09-23', 'end' => '2024-09-29', 'UW' => 4],
        ['start' => '2024-09-30', 'end' => '2024-10-06', 'UW' => 5],
        ['start' => '2024-10-07', 'end' => '2024-10-13', 'UW' => 6],
        ['start' => '2024-10-14', 'end' => '2024-10-20', 'UW' => 7],
        ['start' => '2024-10-21', 'end' => '2024-10-27', 'UW' => 8],
        ['start' => '2024-11-04', 'end' => '2024-11-10', 'UW' => 9],
        ['start' => '2024-11-11', 'end' => '2024-11-17', 'UW' => 10],
        ['start' => '2024-11-18', 'end' => '2024-11-24', 'UW' => 11],
        ['start' => '2024-11-25', 'end' => '2024-12-01', 'UW' => 12],
        ['start' => '2024-12-02', 'end' => '2024-12-08', 'UW' => 13],
        ['start' => '2024-12-09', 'end' => '2024-12-15', 'UW' => 14],
        ['start' => '2024-12-16', 'end' => '2024-12-22', 'UW' => 15],
        ['start' => '2025-01-06', 'end' => '2025-01-12', 'UW' => 16],
        ['start' => '2025-01-13', 'end' => '2025-01-19', 'UW' => 17],
        ['start' => '2025-01-20', 'end' => '2025-01-26', 'UW' => 18],
        ['start' => '2025-01-27', 'end' => '2025-02-02', 'UW' => 19],
        ['start' => '2025-02-10', 'end' => '2025-02-16', 'UW' => 20],
        ['start' => '2025-02-17', 'end' => '2025-02-23', 'UW' => 21],
        ['start' => '2025-02-24', 'end' => '2025-03-02', 'UW' => 22],
        ['start' => '2025-03-03', 'end' => '2025-03-09', 'UW' => 23],
        ['start' => '2025-03-10', 'end' => '2025-03-16', 'UW' => 24],
        ['start' => '2025-03-17', 'end' => '2025-03-23', 'UW' => 25],
        ['start' => '2025-03-24', 'end' => '2025-03-30', 'UW' => 26],
        ['start' => '2025-03-31', 'end' => '2025-04-06', 'UW' => 27],
        ['start' => '2025-04-07', 'end' => '2025-04-13', 'UW' => 28],
        ['start' => '2025-04-21', 'end' => '2025-04-27', 'UW' => 29],
        ['start' => '2025-04-28', 'end' => '2025-05-04', 'UW' => 30],
        ['start' => '2025-05-05', 'end' => '2025-05-11', 'UW' => 31],
        ['start' => '2025-05-12', 'end' => '2025-05-18', 'UW' => 32],
        ['start' => '2025-05-19', 'end' => '2025-05-25', 'UW' => 33],
        ['start' => '2025-05-26', 'end' => '2025-06-01', 'UW' => 34],
        ['start' => '2025-06-02', 'end' => '2025-06-08', 'UW' => 35]
    ];

    $datum = date('Y-m-d', strtotime($datum));

    foreach ($unterrichtswochen as $woche) {
        if ($datum >= $woche['start'] && $datum <= $woche['end']) {
            return $woche['UW'];
        }
    }

    return null;
}
?>