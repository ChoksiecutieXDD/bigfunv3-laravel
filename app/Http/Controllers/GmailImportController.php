<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use GuzzleHttp\Client as GuzzleClient;

class GmailImportController extends Controller
{
    public function fetchEmails(Request $request)
    {
        $tokenPath = storage_path('app/google/token.json');
        if (!file_exists($tokenPath)) {
            return response()->json(['error' => 'Not connected to Gmail. Please authenticate in Enquiries page.']);
        }

        try {
            $client = new GoogleClient();
            $client->setHttpClient(new GuzzleClient(['verify' => false]));
            $client->setAuthConfig(storage_path('app/google/client_secret.json'));
            $client->setAccessToken(json_decode(file_get_contents($tokenPath), true));

            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    file_put_contents($tokenPath, json_encode($client->getAccessToken()));
                } else {
                    return response()->json(['error' => 'Token expired. Re-authentication required.']);
                }
            }

            $service = new Gmail($client);

            $qParam = $request->input('q', '');
            $optParams = [
                'maxResults' => 10,
                'labelIds' => ['INBOX']
            ];

            if (!empty($qParam)) {
                $optParams['q'] = $qParam;
            }

            $results = $service->users_messages->listUsersMessages('me', $optParams);
            $messages = [];

            if ($results->getMessages()) {
                foreach ($results->getMessages() as $message) {
                    $msg = $service->users_messages->get('me', $message->getId(), ['format' => 'full']);
                    $payload = $msg->getPayload();
                    $headers = $payload->getHeaders();

                    $subject = "(No Subject)";
                    $from = "Unknown";
                    $date = "";

                    foreach ($headers as $header) {
                        if ($header->getName() == 'Subject') $subject = $header->getValue();
                        if ($header->getName() == 'From') $from = $header->getValue();
                        if ($header->getName() == 'Date') $date = date('d M Y', strtotime($header->getValue()));
                    }

                    $name = $from;
                    $email = "";
                    if (preg_match('/(.*)<(.*)>/', $from, $matches)) {
                        $name = trim($matches[1], " \"");
                        $email = $matches[2];
                    }

                    $content = $this->extractMessageContent($payload);
                    $fullBody = !empty($content['html']) ? $content['html'] : nl2br($content['plain']);
                    $rawText = !empty($content['plain']) ? $content['plain'] : strip_tags($content['html']);
                    $snippet = substr(trim(preg_replace('/\s+/', ' ', $rawText)), 0, 100) . '...';

                    $messages[] = [
                        'id' => $message->getId(),
                        'name' => $name,
                        'email' => $email,
                        'subject' => $subject,
                        'snippet' => $snippet,
                        'body' => $fullBody,
                        'date' => $date
                    ];
                }
            }

            return response()->json($messages);

        } catch (\Exception $e) {
            return response()->json(['error' => 'API Error: ' . $e->getMessage()]);
        }
    }

    private function decodeBody($data)
    {
        $data = str_replace(['-', '_'], ['+', '/'], $data);
        return base64_decode($data);
    }

    private function extractMessageContent($payload)
    {
        $html = '';
        $plain = '';

        if (isset($payload['body']['data'])) {
            $decoded = $this->decodeBody($payload['body']['data']);
            if ($payload['mimeType'] === 'text/html') {
                $html = $decoded;
            } elseif ($payload['mimeType'] === 'text/plain') {
                $plain = $decoded;
            }
        }

        if (isset($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if ($part['mimeType'] === 'text/html' && isset($part['body']['data'])) {
                    $html = $this->decodeBody($part['body']['data']);
                } elseif ($part['mimeType'] === 'text/plain' && isset($part['body']['data'])) {
                    $plain = $this->decodeBody($part['body']['data']);
                } elseif (strpos($part['mimeType'], 'multipart/') === 0) {
                    $nested = $this->extractMessageContent($part);
                    if (!empty($nested['html'])) $html = $nested['html'];
                    if (!empty($nested['plain'])) $plain = $nested['plain'];
                }
            }
        }

        return ['html' => $html, 'plain' => $plain];
    }
}
