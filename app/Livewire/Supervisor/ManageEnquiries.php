<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use GuzzleHttp\Client as GuzzleClient;

#[Layout('components.layouts.supervisor')]
class ManageEnquiries extends Component
{
    use WithFileUploads;

    public $emails = [];
    public $syncStatus = 'Waiting to sync...';
    public $syncError = null;

    // Reply Modal State
    public $replyModalOpen = false;
    public $replyId = '';
    public $replyName = '';
    public $replyEmail = '';
    public $replySnippet = '';
    public $replySubject = '';
    public $replyBody = '';
    public $attachments = [];

    // We removed loadEnquiries() from mount() so the page loads instantly.
    public function mount()
    {
        // Page loads instantly now.
    }

    private function getGoogleClient()
    {
        $tokenPath = storage_path('app/google/token.json');
        $secretPath = storage_path('app/google/client_secret.json');

        if (!file_exists($secretPath)) {
            throw new \Exception('client_secret.json is missing in storage/app/google/.');
        }

        if (!file_exists($tokenPath)) {
            throw new \Exception('Not connected to Gmail. No token found.');
        }

        $client = new GoogleClient();
        $client->setHttpClient(new GuzzleClient(['verify' => false]));
        $client->setAuthConfig($secretPath);

        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            } else {
                throw new \Exception('Token expired. Re-authentication required.');
            }
        }

        return $client;
    }

    public function loadEnquiries()
    {
        $this->syncError = null;
        $this->syncStatus = 'Syncing...';

        try {
            $client = $this->getGoogleClient();
            $service = new Gmail($client);

            $optParams = [
                'maxResults' => 10,
                'labelIds' => ['INBOX']
            ];

            $results = $service->users_messages->listUsersMessages('me', $optParams);
            $fetchedEmails = [];

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
                        $email = trim($matches[2], " <>");
                    }

                    $snippet = substr(trim(preg_replace('/\s+/', ' ', $msg->getSnippet())), 0, 100) . '...';

                    $fetchedEmails[] = [
                        'id' => $message->getId(),
                        'name' => $name,
                        'email' => $email,
                        'subject' => $subject,
                        'snippet' => $snippet,
                        'date' => $date
                    ];
                }
            }

            $this->emails = $fetchedEmails;
            $this->syncStatus = 'Active';
        } catch (\Exception $e) {
            $this->syncError = $e->getMessage();
            $this->syncStatus = 'Disconnected';
        }
    }

    public function openReplyModal($emailId, $name, $email, $subject, $snippet)
    {
        $this->replyId = $emailId;
        $this->replyName = $name;
        $this->replyEmail = $email;
        $this->replySnippet = $snippet;

        $subj = $subject ?: "No Subject";
        if (!str_starts_with(strtolower($subj), "re:")) {
            $subj = "Re: " . $subj;
        }
        $this->replySubject = $subj;
        $this->replyBody = '';
        $this->reset('attachments');

        $this->replyModalOpen = true;
    }

    public function sendReply()
    {
        $this->validate([
            'replyEmail' => 'required|email',
            'replySubject' => 'required|string',
            'replyBody' => 'required|string',
        ]);

        try {
            $client = $this->getGoogleClient();
            $service = new Gmail($client);

            $strSubject = '=?utf-8?B?' . base64_encode($this->replySubject) . '?=';
            $rawMessageString = "To: {$this->replyEmail}\r\n";
            $rawMessageString .= "Subject: {$strSubject}\r\n";
            $rawMessageString .= "MIME-Version: 1.0\r\n";
            $rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n";
            $rawMessageString .= "Content-Transfer-Encoding: base64\r\n\r\n";

            $htmlContent = "
            <div style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
                <h2 style='color: #9E6B73; border-bottom: 2px solid #eee; padding-bottom: 10px;'>BigFun Reply</h2>
                <div style='font-size: 14px; line-height: 1.6; color: #555;'>
                    " . nl2br(htmlspecialchars($this->replyBody)) . "
                </div>
                <br>
                <hr style='border: 0; border-top: 1px solid #eee;'>
                <p style='font-size: 12px; color: #999;'>
                    Thank you for choosing BigFun!<br>
                    <a href='#' style='color: #9E6B73; text-decoration: none;'>Visit our website</a>
                </p>
            </div>";

            $rawMessageString .= base64_encode($htmlContent);
            $mime = rtrim(strtr(base64_encode($rawMessageString), '+/', '-_'), '=');

            $msg = new \Google\Service\Gmail\Message();
            $msg->setRaw($mime);

            $service->users_messages->send('me', $msg);

            $this->replyModalOpen = false;
            $this->dispatch('notify', title: 'Success', message: 'Reply sent successfully!');
            $this->loadEnquiries(); // Refresh the inbox

        } catch (\Exception $e) {
            $this->dispatch('notify-error', title: 'Error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.supervisor.manage-enquiries');
    }
}
