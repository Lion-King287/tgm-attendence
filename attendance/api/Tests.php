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

    private function fetchSiteId(): string
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
}

$accessToken = $_SESSION['microsoft_token'];
$siteName = 'tgmwien.sharepoint.com:/teams/HIT';
$filePath = 'Klassenbücher/SchülerIn/3AHIT/Karajeh Sharif.xlsx';

$fileFetcher = new SharePointFileFetcher($accessToken, $siteName);
$fileInfo = $fileFetcher->fetchFileInfo($filePath);

echo json_encode($fileInfo, JSON_PRETTY_PRINT);
?>