<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Überprüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['username'])) {
    header("Location: html/login.php");
    exit();
} elseif ($_SESSION['isTeacher'] !== 1) {
    header("Location: ../index.php");
    exit();
}

require '../vendor/autoload.php';

use GuzzleHttp\Client;

class ExcelWriter
{

    private string $accessToken;
    private string $filePath;
    private string $fileId;

    public function __construct(string $accessToken, string $filePath)
    {
        $this->accessToken = $accessToken;
        $this->filePath = $filePath;
        $this->fileId = $this->getFileId();
    }

    public static function isMicrosoftTokenValid(string $accessToken): bool
    {
        try {
            $client = new Client();

            // API URL for the test request
            $url = "https://graph.microsoft.com/v1.0/me";

            // Headers for the request (Authorization)
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ];

            // GET request to Microsoft Graph API
            $response = $client->request('GET', $url, [
                'headers' => $headers
            ]);

            // Check the response status
            return $response->getStatusCode() == 200;
        } catch (Exception $e) {
            return false;
        }
    }

    private function getFileId(): string
    {
        try {
            $client = new Client();

            // API URL für die Suche nach der Datei
            $url = "https://graph.microsoft.com/v1.0/me/drive/root:/Desktop/Schule/Klassenbücher/Tests:/search(q='" . basename($this->filePath) . "')";

            // Header für den Request (Authorization)
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];

            // GET-Request an Microsoft Graph API
            $response = $client->request('GET', $url, [
                'headers' => $headers
            ]);

            // Überprüfe den Response Status
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody(), true);
                foreach ($data['value'] as $item) {
                    if ($item['name'] === basename($this->filePath)) {
                        return $item['id'];
                    }
                }
            } else {
                throw new Exception("Fehler beim Abrufen der Datei-ID: " . $response->getStatusCode());
            }
        } catch (Exception $e) {
            echo 'Fehler: ' . $e->getMessage() . PHP_EOL;
        }

        return '';
    }

    public function writeToExcelRangeWithGaps(string $sheetName, array $cells): void
    {
        // Berechne den Bereich
        $minRow = PHP_INT_MAX;
        $maxRow = 0;
        $minCol = 'Z';
        $maxCol = 'A';

        foreach ($cells as $cell => $value) {
            preg_match('/([A-Z]+)(\d+)/', $cell, $matches);
            $col = $matches[1];
            $row = (int)$matches[2];

            if ($row < $minRow) $minRow = $row;
            if ($row > $maxRow) $maxRow = $row;
            if ($col < $minCol) $minCol = $col;
            if ($col > $maxCol) $maxCol = $col;
        }

        $startCell = $minCol . $minRow;
        $endCell = $maxCol . $maxRow;

        // Erstelle das Werte-Array mit nulls
        $values = [];
        for ($row = $minRow; $row <= $maxRow; $row++) {
            $rowValues = [];
            for ($col = $minCol; $col <= $maxCol; $col++) {
                $cell = $col . $row;
                $rowValues[] = $cells[$cell] ?? null;
            }
            $values[] = $rowValues;
        }

        // Schreibe die Werte in den Bereich
        try {
            $client = new Client();

            // API URL für den PATCH-Request
            $url = "https://graph.microsoft.com/v1.0/me/drive/items/" . $this->fileId . "/workbook/worksheets/" . $sheetName . "/range(address='" . $startCell . ":" . $endCell . "')";

            // Daten, die in den Bereich geschrieben werden
            $data = [
                'values' => $values
            ];

            // Header für den Request (Authorization und Content-Type)
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];

            // PATCH-Request an Microsoft Graph API
            $response = $client->request('PATCH', $url, [
                'headers' => $headers,
                'json' => $data
            ]);

            // Überprüfe den Response-Status
            if ($response->getStatusCode() == 200) {
                //echo "Wert erfolgreich in den Bereich $startCell:$endCell geschrieben!" . PHP_EOL;
            } else {
                echo "Fehler beim Schreiben in den Bereich $startCell:$endCell: " . $response->getStatusCode() . PHP_EOL;
            }
        } catch (Exception $e) {
            echo 'Fehler: ' . $e->getMessage() . PHP_EOL;
        }
    }

    public function readFromExcelRange(string $sheetName, string $startCell, string $endCell): array
    {
        try {
            $client = new Client();

            // API URL für den GET-Request
            $url = "https://graph.microsoft.com/v1.0/me/drive/items/" . $this->fileId . "/workbook/worksheets/" . $sheetName . "/range(address='" . $startCell . ":" . $endCell . "')";

            // Header für den Request (Authorization)
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];

            // GET-Request an Microsoft Graph API
            $response = $client->request('GET', $url, [
                'headers' => $headers
            ]);

            // Überprüfe den Response-Status
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody(), true);
                return $data['values'];
            } else {
                throw new Exception("Fehler beim Lesen des Bereichs $startCell:$endCell: " . $response->getStatusCode());
            }
        } catch (Exception $e) {
            echo 'Fehler: ' . $e->getMessage() . PHP_EOL;
            return [];
        }
    }

    public function readFromExcelCell(string $sheetName, string $cell): string
    {
        try {
            $client = new Client();

            // API URL für den GET-Request
            $url = "https://graph.microsoft.com/v1.0/me/drive/items/" . $this->fileId . "/workbook/worksheets/" . $sheetName . "/range(address='" . $cell . "')";

            // Header für den Request (Authorization)
            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];

            // GET-Request an Microsoft Graph API
            $response = $client->request('GET', $url, [
                'headers' => $headers
            ]);

            // Überprüfe den Response-Status
            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody(), true);
                return $data['values'][0][0];
            } else {
                throw new Exception("Fehler beim Lesen der Zelle $cell: " . $response->getStatusCode());
            }
        } catch (Exception $e) {
            echo 'Fehler: ' . $e->getMessage() . PHP_EOL;
            return '';
        }
    }
}