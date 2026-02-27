<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\HdMessageAttachment;
use App\Models\HdMessageReference;
use App\Models\Sequence;
use App\Models\SysFile;
use App\Services\CriticalErrorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Models\Order;
use App\Models\Message;
use App\Models\HDMessage;
use App\Models\HdMessagePart;
use App\Models\HdTicketType;
use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Utils\EmailMessageUtility;


class ProcessStoreMessageAccountJob implements ShouldQueue
{
    //use InteractsWithQueue, SerializesModels;
    use Queueable, Dispatchable, TrackableJob {
        TrackableJob::withChain insteadof Dispatchable;
        Dispatchable::withChain as dispatchableWithChain;
    }

    public $timeout = 60 * 35; // Set a timeout for the job

    protected $storeMessageAccount;
    protected $store;
    public $store_type;
    public $store_name;

    public function __construct($storeMessageAccount, $store, $store_type)
    {
        $this->storeMessageAccount = $storeMessageAccount;
        $this->store = $store;
        $this->store_type = $store_type;
        $this->store_name = $store->name;
    }

    public function handle()
    {
        try {
            Log::info('===== ProcessStoreMessageAccountJob Started =====', [
                'store_id' => $this->store->id,
                'store_name' => $this->store->name,
                'store_type' => $this->store_type
            ]);

            $this->updateTrackingRecord([
                'status' => 'processing',
                'started_at' => now(),
                'tags' => $this->store_name
            ]);

            Log::info('Store Name: ' . $this->store->name);
            Log::info('Store Type Name : ' . $this->store->storeType->name);
            $maccount = $this->storeMessageAccount;
            $store = $this->store;
            $store_type = $this->store_type;
            $smtpConfig = $imapConfig = [];

            if ($maccount->incoming_account_id != '') {
                Log::info('Incoming account ID found', ['incoming_account_id' => $maccount->incoming_account_id]);

                if (isset($maccount->incomingAccount)) {
                    $account = $maccount->incomingAccount;
                    $imapConfig = [
                        'host' => $maccount->incomingAccount->server,
                        'port' => $maccount->incomingAccount->port,
                        'encryption' => 'ssl',
                        'username' => $maccount->incomingAccount->user_id,
                        'password' => $maccount->incomingAccount->password,
                    ];

                    $filter_date = (isset($maccount->incomingAccount->filter_from_date)) ? $maccount->incomingAccount->filter_from_date : date('Y-m-d', strtotime('-5 days'));
                    $folder = $maccount->incomingAccount->folders;

                    Log::info('Processing Incoming Account ID:' . $maccount->incomingAccount->id);
                    Log::info('Imap Config :' . json_encode($imapConfig));
                    Log::info('Fetch parameters', [
                        'filter_date' => $filter_date,
                        'folder' => $folder,
                        'fetch_limit' => $maccount->incomingAccount->fetch_limit
                    ]);

                    $emailUtility = new EmailMessageUtility($smtpConfig, $imapConfig);
                    Log::info('Fetching emails from server...');
                    $receiveEmails = $emailUtility->fetchEmails($store_type, $filter_date, $folder,$maccount->incomingAccount->fetch_limit, $this->store_name);
                    Log::info('Email fetch completed', ['email_count' => count($receiveEmails ?? [])]);
                    $last_filter_date = $account->filter_from_date;

                    if (count($receiveEmails ?? []) > 0) {
                        Log::info('Starting to process emails', ['total_emails' => count($receiveEmails)]);

                        foreach ($receiveEmails as $emailIndex => $email) {
                            Log::info("Processing email #{$emailIndex}", [
                                'message_id' => $email['message_id'] ?? 'N/A',
                                'from' => $email['from'] ?? 'N/A',
                                'subject' => $email['subject'] ?? 'N/A'
                            ]);

                            /*if(!is_array($email)){ continue;}*/
                            if(isset($email['error'])){
                                Log::warning("Email has error flag, skipping", [
                                    'email_index' => $emailIndex,
                                    'error' => $email['error']
                                ]);
                                continue;
                            }

                            Log::info(' Date : ' . $email['date']);
                            // get as max date for next filter
                            if (isset($email['date']) && $email['date'] > $last_filter_date) {
                                $last_filter_date = $email['date'];
                            }
                            //Log::info('Date :: '. $email['date']);
                            $order_id = $related_order_id = $po_no = $reason = $customer_id = $order_operation_id = $reference_code = NULL;
                            $ticket_type_code = HdTicketTypeCode::MARKETPLACE_NOTIFICATION;

                            if (isset($email['from'])) {
                                Log::info('Looking up customer by email', ['from_email' => $email['from']]);

                                $customerDetail = Customer::select('customers.*')
                                    ->join('customer_emails as CE', 'CE.customer_id', '=', 'customers.id', 'left')
                                    ->whereLike('customers.email', '%' . $email['from'] . '%')
                                    ->orWhere('CE.email_address', 'like', '%' . $email['from'] . '%')
                                    ->first();

                                if ($customerDetail) {
                                    $customer_id = $customerDetail->id;
                                    Log::info('Customer found', ['customer_id' => $customer_id]);
                                } else {
                                    Log::info('Customer not found for email', ['from_email' => $email['from']]);
                                }
                            }
                            $username = NULL;

                            Log::info('Finding related order for email');
                            $orderDetail = $this->findRelatedOrder($email, $store_type);
                            if ($orderDetail) {
                                $order_id = $orderDetail->order_id;
                                $related_order_id = $orderDetail->id;
                                $store_id = $orderDetail->store_id;
                                $customer_id = $orderDetail->customer_id ?? (isset($orderDetail->customer) ? $orderDetail->customer->id : NULL);
                                $order_operation_id = isset($orderDetail->orderOperation) ? $orderDetail->orderOperation->id : NULL;

                                Log::info('Related order found', [
                                    'order_id' => $order_id,
                                    'related_order_id' => $related_order_id,
                                    'customer_id' => $customer_id
                                ]);
                            } else {
                                Log::info('No related order found for this email');
                            }

                            if (isset($email['parsedData']) && !empty($email['parsedData'])) {
                                Log::info('Processing parsed email data', ['parsed_data' => $email['parsedData']]);

                                if (isset($email['parsedData']['purchase_order_number'])) {
                                    $po_no = html_entity_decode($email['parsedData']['purchase_order_number']);
                                }

                                if (isset($email['parsedData']['reason'])) {
                                    $reason = html_entity_decode($email['parsedData']['reason']);
                                }

                                if (isset($email['parsedData']['username'])) {
                                    $username = html_entity_decode($email['parsedData']['username']);
                                }

                                if (isset($email['parsedData']['ticket_type_id'])) {
                                    $ticket_type_code = html_entity_decode($email['parsedData']['ticket_type_id']);
                                }

                                if (isset($email['parsedData']['reference_id'])) {
                                    $reference_code = html_entity_decode($email['parsedData']['reference_id']);
                                }

                                Log::info('Parsed data extracted', [
                                    'po_no' => $po_no,
                                    'username' => $username,
                                    'ticket_type_code' => $ticket_type_code,
                                    'reference_code' => $reference_code
                                ]);
                            }


                            if ($customer_id == NULL) {
                                Log::info('Creating new customer from email data');

                                $firstname = $lastname = '';
                                if (isset($email['name'])) {
                                    $nameArr = explode(' ', $email['name']);
                                    $firstname = $nameArr[0];
                                    $lastname = isset($nameArr[1]) ? $nameArr[1] : '';
                                }

                                $customerData = [
                                    'first_name' => $firstname,
                                    'last_name' => $lastname,
                                    'full_name' => isset($email['name']) ? $email['name'] : '',
                                    'email' => $email['from'],
                                    'user_id' => $email['from']
                                ];

                                $customer_detail = Customer::updateOrCreate([
                                    'email' => $email['from'],
                                ], $customerData);

                                $customer_id = $customer_detail->id;

                                Log::info('New customer created', [
                                    'customer_id' => $customer_id,
                                    'email' => $email['from']
                                ]);

                                $customerEmailData = [
                                    'customer_id' => $customer_id,
                                    'email_address' => $email['from'],
                                ];
                                CustomerEmail::updateOrCreate([
                                    'customer_id' => $customer_id,
                                    'email_address' => $email['from']
                                ], $customerEmailData);
                            } else {
                                Log::info('Using existing customer', ['customer_id' => $customer_id]);

                                $customerDetail = Customer::findOrFail($customer_id);
                                if ($customerDetail->email != $email['from']) {
                                    Log::info('Adding secondary email to customer', [
                                        'customer_id' => $customer_id,
                                        'secondary_email' => $email['from']
                                    ]);

                                    $customerEmailData = [
                                        'customer_id' => $customer_id,
                                        'email_address' => $email['from'],
                                    ];
                                    CustomerEmail::updateOrCreate([
                                        'customer_id' => $customer_id,
                                        'email_address' => $email['from']
                                    ], $customerEmailData);
                                }
                            }

                            $ticket_code = $store->short_code . '-' . $this->getNextTicketNumberForStore($store->id);

                            Log::info('Preparing ticket data', ['ticket_code' => $ticket_code]);

                            $ticketTypeDetail = HdTicketType::where('code', $ticket_type_code)->first();

                            if (!$ticketTypeDetail) {
                                Log::warning('Ticket type not found', ['ticket_type_code' => $ticket_type_code]);
                            } else {
                                Log::info('Ticket type found', [
                                    'ticket_type_id' => $ticketTypeDetail->id,
                                    'ticket_type_code' => $ticket_type_code
                                ]);
                            }

                            $ticketData = [
                                'ticket_type_id' => $ticketTypeDetail->id,
                                'ticket_email' => trim($email['from']),
                                'username' => trim($username),
                                'status' => HdTicketStatus::NEEDS_ACTION,
                                'related_order_id' => $related_order_id,
                                'subject' => preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', trim(mb_convert_encoding(htmlspecialchars_decode($email['subject']), 'UTF-8', 'UTF-8'))),
                                'message_id' => trim($email['message_id']),
                                'notes' => NULL,
                                'customer_id' => $customer_id,
                                'reference_code' => $reference_code,
                                'related_order_operation_id' => $order_operation_id,
                                'order_id' => trim($order_id),
                                'store_id' => $store->id,
                                'ticket_date' => $email['date'],
                                'ticket_code' => $ticket_code,
                                'message_read' => 0,
                                'replies' => 0,
                                'important' => 0,
                                'customer_merge_status' => 0,
                                'ticket_user_id' => $customer_id,
                                'sku' => NULL,
                                'related_listing_item_id' => NULL,
                            ];

                            Log::info('Email From : ' . $email['from'] . ' Message ID: ' . $email['message_id']);
                            Log::info(' Reference ID: ' . json_encode($email['references']));

                            /*$ticketDetail = Message::where('ticket_email', trim($email['from']))->orWhere('ticket_email', trim($email['reply_to']))->first();*/
                            Log::info('Searching for existing ticket by message_id and references');

                            $query = Message::where('message_id', trim($email['message_id']));
                            if (isset($email['references']) && $email['references'] != '') {
                                if(is_array($email['references'])){
                                    $query->orWhereIn('message_id', $email['references']);
                                }
                            }
                            if (isset($email['in_reply_to']) && $email['in_reply_to'] != '') {
                                $query->orWhere('message_id', trim($email['in_reply_to']));
                            }
                            /*if (isset($email['reply_to']) && $email['reply_to'] != '') {
                                $query->orWhere('ticket_email', trim($email['reply_to']));
                            }*/
                            Log::info(' Message Query: ' . $query->toSql());
                            $ticketDetail = $query->first();

                            if (!$ticketDetail) {
                                Log::info('No ticket found in Messages table, checking HdMessage table');

                                $hdQuery = HdMessage::select('ticket_id')
                                    ->where('message_id', trim($email['message_id']));

                                if (!empty($email['references'])) {
                                    if (is_array($email['references'])) {
                                        $hdQuery->orWhereIn('message_id', $email['references']);
                                    }
                                }

                                if (!empty($email['in_reply_to'])) {
                                    $hdQuery->orWhere('message_id', trim($email['in_reply_to']));
                                }

                                $hdQuery->orderBy('created_at', 'desc');

                                $hdMessage = $hdQuery->first();

                                if ($hdMessage) {
                                    Log::info('Found related ticket via HdMessage', ['ticket_id' => $hdMessage->ticket_id]);
                                    $ticketDetail = Message::find($hdMessage->ticket_id);
                                } else {
                                    Log::info('No existing ticket found in HdMessage table either');
                                }
                            } else {
                                Log::info('Existing ticket found', ['ticket_id' => $ticketDetail->id]);
                            }

                            if ($ticketDetail) {
                                Log::info('Updating existing ticket', ['ticket_id' => $ticketDetail->id]);

                                if ($order_id) {
                                    $ticketDetail->order_id = $order_id;
                                    $ticketDetail->ticket_type_id = $ticketTypeDetail->id;
                                }
                                $ticketDetail->ticket_date = $email['date'];
                                $ticketDetail->message_read = 0;
                                $ticketDetail->status = HdTicketStatus::NEEDS_ACTION;
                                $ticketDetail->save();

                                $hd_ticket = $ticketDetail;
                            } else {
                                Log::info('Creating new ticket', ['ticket_code' => $ticket_code]);
                                $hd_ticket = Message::create($ticketData);
                                Log::info('New ticket created', ['ticket_id' => $hd_ticket->id]);
                            }

                            if (isset($hd_ticket->id)) {
                                Log::info('Processing HD message for ticket', ['ticket_id' => $hd_ticket->id]);

                                $msg = mb_convert_encoding($email['body'], 'UTF-8', 'UTF-8');
                                $msg = preg_replace('/[^\x00-\x7F]+/', ' ', $msg);

                                $threadId = null;

                                if (!empty($email['references'])) {
                                    $threadId = $email['references'][0];
                                    Log::info('Thread ID set from references', ['thread_id' => $threadId]);
                                } elseif (isset($email['in_reply_to']) && $email['in_reply_to'] != '') {
                                    $threadId = $email['in_reply_to'];
                                    Log::info('Thread ID set from in_reply_to', ['thread_id' => $threadId]);
                                } else {
                                    $threadId = $email['message_id'];
                                    Log::info('Thread ID set from message_id (new thread)', ['thread_id' => $threadId]);
                                }

                                $hdMessageData = [
                                    'ticket_id' => $hd_ticket->id,
                                    'subject' => preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', trim(mb_convert_encoding(htmlspecialchars_decode($email['subject']), 'UTF-8', 'UTF-8'))),
                                    'message_date' => $email['date'],
                                    'to_' => $email['to'],
                                    'cc' => isset($email['cc']) ? $email['cc'] : NULL,
                                    'bcc' => isset($email['bcc']) ? $email['bcc'] : NULL,
                                    'message_id' => trim($email['message_id']),
                                    'messaging_account_id' => $maccount->incomingAccount->id,
                                    'from_email' => trim($email['from']),
                                    'from_name' => trim($email['name']),
                                    'message' => iconv('UTF-8', 'UTF-8//IGNORE', $msg),
                                    'in_reply_to' => $email['in_reply_to'],
                                    'draft' => 0,
                                    'reply_to_name' => $email['reply_to_name'],
                                    'reply_to_email' => $email['reply_to'],
                                    'thread_id' => $threadId ?? NULL,
                                    'references' => $email['references'] ?? NULL,
                                ];

                                Log::info('Creating/updating HD message', [
                                    'ticket_id' => $hd_ticket->id,
                                    'message_id' => trim($email['message_id']),
                                    'thread_id' => $threadId
                                ]);

                                $hd_message = HDMessage::updateOrCreate([
                                    'ticket_id' => $hd_ticket->id,
                                    'message_id' => trim($email['message_id']),
                                ], $hdMessageData);

                                Log::info('HD message saved', ['hd_message_id' => $hd_message->id]);

                                if (isset($hd_message->id) && !empty($email['parts'])) {
                                    Log::info('Processing message parts', ['hd_message_id' => $hd_message->id]);

                                    $email['parts']['message_id'] = $hd_message->id;

                                    $partmsg = mb_convert_encoding($email['parts']['content'], 'UTF-8', 'UTF-8');
                                    $partmsg = preg_replace('/[^\x00-\x7F]+/', ' ', $partmsg);
                                    $email['parts']['content'] = $partmsg;

                                    HdMessagePart::updateOrCreate([
                                        'message_id' => $hd_message->id,
                                    ], $email['parts']);

                                    Log::info('Message parts saved');
                                }

                                if(isset($email['references']) && !empty($email['references']) && is_array($email['references'])){
                                    Log::info('Processing message references', [
                                        'reference_count' => count($email['references']),
                                        'references' => $email['references']
                                    ]);

                                    foreach($email['references'] as $ref){
                                        $refData = [
                                            'ticket_id' => $hd_ticket->id,
                                            'message_id' => $hd_message->id,
                                            'reference' => $ref,
                                        ];
                                        HdMessageReference::updateOrCreate([
                                            'ticket_id' => $hd_ticket->id,
                                            'message_id' => $hd_message->id,
                                            'reference' => $ref,
                                        ], $refData);
                                    }

                                    Log::info('Message references saved');
                                }

                                if (isset($hd_message->id) && !empty($email['attachments'])) {
                                    Log::info('Processing email attachments', [
                                        'hd_message_id' => $hd_message->id,
                                        'attachment_count' => count($email['attachments'])
                                    ]);

                                    if (!empty($email['attachments'])) {
                                        $uniqueAttachments = [];
                                        foreach ($email['attachments'] as $attachmentIndex => $attachment) {
                                            $fileName = $attachment->getName();
                                            $fileMimeType = $attachment->getMimeType();
                                            $fileSize = $attachment->getSize();
                                            $attachmentID = $attachment->getId();

                                            Log::info("Processing attachment #{$attachmentIndex}", [
                                                'file_name' => $fileName,
                                                'mime_type' => $fileMimeType,
                                                'size' => $fileSize,
                                                'attachment_id' => $attachmentID
                                            ]);

                                            $key = $fileName . '-' . $fileSize;

                                            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                                            $savePath = storage_path('app/public/email_attachment/');
                                            if (!file_exists($savePath)) {
                                                mkdir($savePath, 0755, true);
                                                Log::info('Created attachment directory', ['path' => $savePath]);
                                            }

                                            //$uniqueFileName = uniqid('email_', true) . '.' . $extension;

                                            try {
                                                if (!in_array($key, $uniqueAttachments)) {
                                                    $uniqueAttachments[] = $key;

                                                    Log::info('Saving attachment to disk', ['file_name' => $fileName]);
                                                    $attachment->save($savePath, $fileName);

                                                    $filedata = [
                                                        'name' => $fileName,
                                                        'ext' => $extension ?: 'jpg',
                                                        'file_size' => $fileSize ?: 'Unknown',
                                                        'location' => 'email_attachment/',
                                                        'created_by' => 'admin',
                                                    ];

                                                    $fileDetail = SysFile::updateOrCreate([
                                                        'name' => $fileName,
                                                    ], $filedata);

                                                    /*$fileDetail = SysFile::create($filedata);*/
                                                    $file_id = $fileDetail->id;

                                                    Log::info('File record created', ['file_id' => $file_id]);

                                                    if ($hd_ticket->id && $file_id) {
                                                        $attachementData = [
                                                            'client_id' => NULL,
                                                            'message_id' => $hd_message->id,
                                                            'attachment_id' => $attachmentID,
                                                            'file_id' => $file_id,
                                                            'created_by' => 'admin',
                                                        ];

                                                        /*HdMessageAttachment::create($attachementData);*/

                                                        HdMessageAttachment::updateOrCreate([
                                                            'message_id' => $hd_message->id,
                                                            'attachment_id' => $attachmentID,
                                                            'file_id' => $file_id,
                                                        ], $attachementData);

                                                        Log::info('Attachment record saved to database');

                                                    }
                                                } else {
                                                    Log::info('Skipping duplicate attachment', ['key' => $key]);
                                                }

                                            } catch (\Throwable $e) {

                                                $extraInfo = [
                                                    'message_id' => $hd_message->id
                                                ];

                                                \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

                                                echo "Error saving attachment: " . $e->getMessage();
                                                Log::error('Error saving attachment: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
                                            }
                                        }

                                        Log::info('All attachments processed', ['total_saved' => count($uniqueAttachments)]);
                                    }
                                }
                            }
                        }

                        if ($last_filter_date != '') {
                            Log::info('Updating last filter date to: ' . $last_filter_date);
                            $last_filter_datef = \Carbon\Carbon::parse($last_filter_date)->setTimezone('America/New_York');
                            $account->update(['filter_from_date' => $last_filter_datef]);
                            Log::info('Filter date updated successfully', ['new_filter_date' => $last_filter_datef->toDateTimeString()]);
                        }

                    } else {
                        Log::info('No emails received from server');

                        $filterDateObj = \Carbon\Carbon::parse($filter_date, 'America/New_York');
                        $maxDate = $filterDateObj->copy()->addDays(1);
                        $daysDiff = \Carbon\Carbon::now('America/New_York')->diffInDays($filterDateObj, false);

                        Log::info("Diff " . json_encode($daysDiff));
                        Log::info('Checking if filter date should be incremented', [
                            'current_filter_date' => $filter_date,
                            'days_diff' => $daysDiff
                        ]);

                        if ($daysDiff < -1) {
                            $account->update(['filter_from_date' => $maxDate->toDateString()]);
                            Log::info('Filter date incremented by 1 day', ['new_filter_date' => $maxDate->toDateString()]);
                        }
                    }
                } else {
                    Log::warning('Incoming account not found', ['incoming_account_id' => $maccount->incoming_account_id]);
                }
            } else {
                Log::info('No incoming account ID set for this store message account');
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);

            Log::info('===== ProcessStoreMessageAccountJob Completed Successfully =====', [
                'store_id' => $this->store->id,
                'store_name' => $this->store->name
            ]);
        } catch (\Throwable $e) {

            Log::error('===== ProcessStoreMessageAccountJob Failed =====', [
                'store_id' => $this->store->id,
                'store_name' => $this->store->name,
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $extraInfo = [
                'store_id' => $this->store->id
            ];

            \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Store message account job failed:'.$this->store_name.'-'.$this->store_type, [
                'marketplace' => ucfirst($this->store_type),
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['store_type' => $this->store_type, 'store_name' => $this->store_name,'store_message_account' => $this->storeMessageAccount]);

            Log::error('Error processing store message account: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }
    }

    /*private function getNextTicketNumberForStore($storeId)
    {
        $lastTicket = Message::where('store_id', $storeId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTicket) {
            $lastNumber = intval(substr($lastTicket->ticket_code, strpos($lastTicket->ticket_code, '-') + 1));
            return str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        }

        return str_pad(1, 6, '0', STR_PAD_LEFT);
    }*/

    public static function getNextTicketNumberForStore($storeId)
    {
        $key = 'ticket_' . str_replace('-', '', $storeId);

        return DB::transaction(function () use ($key) {
            $sequence = Sequence::lockForUpdate()->find($key);

            if (!$sequence) {
                $sequence = Sequence::create([
                    'key' => $key,
                    'number' => 1
                ]);
            } else {
                $sequence->increment('number');
            }

            return $sequence->number;
        });
    }

    /**
     * Find a related order using a multi-layered matching strategy.
     *
     * @param array $email
     * @param string $store_type
     * @return Order|null
     */
    private function findRelatedOrder(array $email, string $store_type): ?Order
    {
        Log::info('Starting findRelatedOrder process', [
            'store_type' => $store_type,
            'email_from' => $email['from'] ?? 'N/A',
            'email_subject' => $email['subject'] ?? 'N/A'
        ]);

        // 1. Match by parsed Order ID (High-Confidence)
        if (!empty($email['parsedData']['order_id'])) {
            $order_id = html_entity_decode($email['parsedData']['order_id']);
            Log::info('Attempting to find order by parsed order_id', ['order_id' => $order_id]);

            $order = Order::with('customer', 'orderOperation')->where('order_id', trim($order_id))->first();
            if ($order) {
                Log::info('Order found by parsed order_id', [
                    'order_id' => $order->order_id,
                    'related_order_id' => $order->id
                ]);
                return $order;
            }
            Log::info('No order found by parsed order_id');
        }

        $text_to_search = $email['subject'] . ' ' . $email['body'];
        if (preg_match('/#(\d+)/', $text_to_search, $matches)) {
            Log::info('Found order/PO number pattern in email', ['number' => $matches[1]]);

            $order = Order::with('customer', 'orderOperation')->where('purchase_order_id', $matches[1])->first();
            if ($order) {
                Log::info('Order found by purchase_order_id', [
                    'purchase_order_id' => $matches[1],
                    'order_id' => $order->order_id
                ]);
                return $order;
            }

            $order = Order::with('customer', 'orderOperation')->where('order_id', $matches[1])->first();
            if ($order) {
                Log::info('Order found by order_id from pattern', [
                    'order_id' => $matches[1]
                ]);
                return $order;
            }
            Log::info('No order found by pattern matching');
        }

        // 3. Match by sender's email address (Medium-Confidence)
        if (!empty($email['from'])) {
            Log::info('Attempting to find order by buyer email', ['email' => $email['from']]);

            $order = Order::with('customer', 'orderOperation')
                ->where('buyer_email', trim($email['from']))
                ->orderBy('order_date', 'desc')
                ->first();
            if ($order) {
                Log::info('Order found by buyer_email', [
                    'order_id' => $order->order_id,
                    'order_date' => $order->order_date
                ]);
                return $order;
            }
            Log::info('No order found by buyer email');
        }

        // 4. Match by sender's name (Low-Confidence)
        if (!empty($email['name'])) {
            $nameParts = explode(' ', trim($email['name']));
            $firstName = $nameParts[0];
            $lastName = count($nameParts) > 1 ? end($nameParts) : null;

            Log::info('Attempting to find order by buyer name', [
                'first_name' => $firstName,
                'last_name' => $lastName
            ]);

            $query = Order::with('customer', 'orderOperation')
                ->where('buyer_first_name', 'like', '%' . $firstName . '%')
                ->where('order_date', '>=', now()->subDays(90));

            if ($lastName) {
                $query->where('buyer_last_name', 'like', '%' . $lastName . '%');
            }

            $order = $query->orderBy('order_date', 'desc')->first();
            if ($order) {
                Log::info('Order found by buyer name', [
                    'order_id' => $order->order_id,
                    'buyer_name' => $order->buyer_first_name . ' ' . $order->buyer_last_name
                ]);
                return $order;
            }
            Log::info('No order found by buyer name');
        }

        Log::info('No related order found using any matching strategy');
        return null;
    }
}
