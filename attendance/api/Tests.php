<?php
session_start();

require '../vendor/autoload.php';

use GuzzleHttp\Client;

class SharePointFileFetcher
{

    private string $accessToken;
    private string $siteName;
    private string $siteId;

    public function __construct(string $accessToken, string $siteName)
    {
        $this->accessToken = $accessToken;
        $this->siteName = $siteName;
        $this->siteId = $this->fetchSiteId();
    }

    public function fetchSiteId(): string
    {
        try {
            $client = $this->initializeHttpClient();

            $url = "https://graph.microsoft.com/v1.0/sites/" . $this->siteName;

            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];

            $response = $client->request('GET', $url, [
                'headers' => $headers
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody(), true);
                return $data['id'];
            } else {
                throw new Exception("Error fetching site ID: " . $response->getStatusCode());
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            return '';
        }
    }

    private function initializeHttpClient(): Client
    {
        return new Client();
    }

    public function fetchFileInfo(string $filePath): array
    {
        try {
            $client = $this->initializeHttpClient();

            $url = "https://graph.microsoft.com/v1.0/sites/" . $this->siteId . "/drive/root:/" . $filePath;

            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];

            $response = $client->request('GET', $url, [
                'headers' => $headers
            ]);

            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                throw new Exception("Error fetching file info: " . $response->getStatusCode());
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            return [];
        }
    }

    public function getFileIdByPath(string $filePath): string
    {
        try {
            $client = $this->initializeHttpClient();

            $url = "https://graph.microsoft.com/v1.0/sites/" . $this->siteId . "/drive/root:/" . $filePath;

            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];

            $response = $client->request('GET', $url, [
                'headers' => $headers
            ]);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody(), true);
                return $data['id'];
            } else {
                throw new Exception("Error fetching file ID: " . $response->getStatusCode());
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            return '';
        }
    }

    public function getFileInfoById(string $fileId): array
    {
        try {
            $client = $this->initializeHttpClient();

            $url = "https://graph.microsoft.com/v1.0/sites/" . $this->siteId . "/drive/items/" . $fileId;

            $headers = [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ];

            $response = $client->request('GET', $url, [
                'headers' => $headers
            ]);

            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            } else {
                throw new Exception("Error fetching file info: " . $response->getStatusCode());
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . PHP_EOL;
            return [];
        }
    }
}


$accessToken = $_SESSION['microsoft_token'];
// tgmwien.sharepoint.com,bde961ef-ac5b-470c-82e7-b3e5571262d9,7e44ac09-bc23-4ad2-b3b1-525f194f6163
$siteName = 'tgmwien.sharepoint.com:/teams/HIT';
$filePath = 'Klassenbücher/SchülerIn/3AHIT/Karajeh Sharif.xlsx';

$fileFetcher = new SharePointFileFetcher($accessToken, $siteName);
//$fileInfo = $fileFetcher->fetchFileInfo($filePath);
//echo $fileFetcher->fetchSiteId() . '<br>';
//echo json_encode($fileInfo, JSON_PRETTY_PRINT);
$fileId = $fileFetcher->getFileIdByPath($filePath);
$fileInfo = $fileFetcher->getFileInfoById($fileId);
echo $fileId . '<br>';
echo json_encode($fileInfo, JSON_PRETTY_PRINT);

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

    return null; // Falls das Datum in keiner UW liegt
}

//echo getTeachingWeek('2025-03-10') . PHP_EOL;