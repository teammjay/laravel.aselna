<?php

namespace App\Utils;

use App\Services\CriticalErrorService;
use Ddeboer\Imap\Server;
use Ddeboer\Imap\Search\Date\Since;
use Exception;
use Illuminate\Support\Facades\Log;

class FastEmailService
{

    protected $imapConfig;
    protected $server;

    public function __construct(array $smtpConfig = [], array $imapConfig = [])
    {
        $this->imapConfig = $imapConfig;

        if (!empty($imapConfig)) {
            $this->initImapClient();
        }
    }

    private function initImapClient()
    {
        $this->server = new Server(
            $this->imapConfig['host'],
            $this->imapConfig['port'], // Port
            '/imap/ssl' // Flags
        );
    }

    public function fetchTodaysEmail($folderName = 'INBOX')
    {
        $startTime = microtime(true);

        try {
            Log::info("Starting fast email fetch for folder: {$folderName}");

            // Authenticate
            $connection = $this->server->authenticate($this->imapConfig['username'],$this->imapConfig['password']);
            Log::info("Successfully authenticated to IMAP server");

            // Get mailbox
            $mailbox = $connection->getMailbox($folderName);

            // Create search criteria for today
            $today = new \DateTime('yesterday', new \DateTimeZone('America/New_York'));
            $searchCriteria = new Since($today);

            Log::info("Searching for emails since: " . $today->format('Y-m-d'));

            // Search messages (much faster than iterating)
            $messages = $mailbox->getMessages($searchCriteria);

            $messageData = [];
            $count = 0;

            foreach ($messages as $message) {
                try {
                    $messageData[] = [
                        'message_id' => $message->getId(),
                        'subject' => $message->getSubject(),
                        'date' => $message->getDate() ? $message->getDate()->format('Y-m-d H:i:s') : null,
                        'from' => $message->getFrom() ? $message->getFrom()->getAddress() : null
                    ];

                    $count++;

                    // Log progress every 100 messages
                    if ($count % 100 === 0) {
                        Log::info("Processed {$count} messages");
                    }

                } catch (Exception $e) {
                    Log::warning("Error processing message: " . $e->getMessage());
                    continue;
                }
            }

            // Close connection
            $connection->close();

            $executionTime = round(microtime(true) - $startTime, 2);
            Log::info("Successfully fetched {$count} emails in {$executionTime} seconds");

            return $messageData;

        } catch (Exception $e) {

            CriticalErrorService::report('Fast Email Service Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error("Error fetching emails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Even faster version - gets only headers
     */
    public function fetchTodaysEmailHeadersOnly($folderName = 'INBOX')
    {
        try {
            $server = new Server($this->server, 993, '/imap/ssl');
            $connection = $server->authenticate($this->username, $this->password);
            $mailbox = $connection->getMailbox($folderName);

            $today = new \DateTime('today');
            $messages = $mailbox->getMessages(new Since($today));

            $messageData = [];

            /** @var \Ddeboer\Imap\Message $message */
            foreach ($messages as $message) {
                // Only get essential headers - much faster
                $messageData[] = [
                    'message_id' => $message->getId(),
                    'subject' => $message->getSubject(),
                    'date' => $message->getDate()->format('Y-m-d H:i:s')
                ];
            }

            $connection->close();
            return $messageData;

        } catch (Exception $e) {
            Log::error("Fast header fetch failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Batch processing version for large volumes
     */
    public function fetchTodaysEmailBatched($folderName = 'INBOX', $batchSize = 50)
    {
        try {
            $server = new Server($this->server, 993, '/imap/ssl');
            $connection = $server->authenticate($this->username, $this->password);
            $mailbox = $connection->getMailbox($folderName);

            $today = new \DateTime('today');
            $messages = $mailbox->getMessages(new Since($today));

            $allMessageData = [];
            $batch = [];
            $count = 0;

            foreach ($messages as $message) {
                $batch[] = [
                    'message_id' => $message->getId(),
                    'subject' => $message->getSubject(),
                    'date' => $message->getDate()->format('Y-m-d H:i:s')
                ];

                $count++;

                // Process in batches to manage memory
                if (count($batch) >= $batchSize) {
                    $allMessageData = array_merge($allMessageData, $batch);
                    Log::info("Processed batch of {$batchSize} messages. Total: {$count}");

                    $batch = []; // Clear batch

                    // Optional: Force garbage collection
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }

            // Process remaining messages
            if (!empty($batch)) {
                $allMessageData = array_merge($allMessageData, $batch);
            }

            $connection->close();

            Log::info("Completed batch processing. Total messages: " . count($allMessageData));
            return $allMessageData;

        } catch (Exception $e) {
            Log::error("Batch processing failed: " . $e->getMessage());
            return [];
        }
    }
}
