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

                    $emailUtility = new EmailMessageUtility($smtpConfig, $imapConfig);
                    $receiveEmails = $emailUtility->fetchEmails($store_type, $filter_date, $folder,$maccount->incomingAccount->fetch_limit, $this->store_name);
                    $last_filter_date = $account->filter_from_date;

                    if (count($receiveEmails ?? []) > 0) {
                        foreach ($receiveEmails as $email) {

                            /*if(!is_array($email)){ continue;}*/
                            if(isset($email['error'])){
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
                                $customerDetail = Customer::select('customers.*')
                                    ->join('customer_emails as CE', 'CE.customer_id', '=', 'customers.id', 'left')
                                    ->whereLike('customers.email', '%' . $email['from'] . '%')
                                    ->orWhere('CE.email_address', 'like', '%' . $email['from'] . '%')
                                    ->first();

                                if ($customerDetail) {
                                    $customer_id = $customerDetail->id;
                                }
                            }
                            $username = NULL;
                            if (isset($email['parsedData']) && !empty($email['parsedData'])) {
                                if (isset($email['parsedData']['order_id'])) {
                                    $order_id = html_entity_decode($email['parsedData']['order_id']);
                                    $orderDetail = Order::with('customer', 'orderOperation')->where('order_id', trim($order_id))->first();
                                    if ($orderDetail) {
                                        $related_order_id = $orderDetail->id;
                                        $store_id = $orderDetail->store_id;
                                        $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : NULL;
                                        $order_operation_id = isset($orderDetail->orderOperation) ? $orderDetail->orderOperation->id : NULL;
                                    }
                                }

                                if(isset($email['from']) && $email['from'] != ''){
                                    $orderDetail = Order::with('customer', 'orderOperation')->where('buyer_email', trim($email['from']))->first();
                                    if ($orderDetail) {
                                        $order_id = $orderDetail->order_id;
                                        $related_order_id = $orderDetail->id;
                                        $store_id = $orderDetail->store_id;
                                        $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : NULL;
                                        $order_operation_id = isset($orderDetail->orderOperation) ? $orderDetail->orderOperation->id : NULL;
                                    }
                                }

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
                            }

                            if ($customer_id == NULL) {
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

                                $customerEmailData = [
                                    'customer_id' => $customer_id,
                                    'email_address' => $email['from'],
                                ];
                                CustomerEmail::updateOrCreate([
                                    'customer_id' => $customer_id,
                                    'email_address' => $email['from']
                                ], $customerEmailData);
                            } else {
                                $customerDetail = Customer::findOrFail($customer_id);
                                if ($customerDetail->email != $email['from']) {
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
                            $ticketTypeDetail = HdTicketType::where('code', $ticket_type_code)->first();
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
                                    $ticketDetail = Message::find($hdMessage->ticket_id);
                                }
                            }

                            if ($ticketDetail) {
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
                                $hd_ticket = Message::create($ticketData);
                            }

                            if (isset($hd_ticket->id)) {

                                $msg = mb_convert_encoding($email['body'], 'UTF-8', 'UTF-8');
                                $msg = preg_replace('/[^\x00-\x7F]+/', ' ', $msg);

                                $threadId = null;

                                if (!empty($email['references'])) {
                                    $threadId = $email['references'][0];
                                } elseif (isset($email['in_reply_to']) && $email['in_reply_to'] != '') {
                                    $threadId = $email['in_reply_to'];
                                } else {
                                    $threadId = $email['message_id'];
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

                                $hd_message = HDMessage::updateOrCreate([
                                    'ticket_id' => $hd_ticket->id,
                                    'message_id' => trim($email['message_id']),
                                ], $hdMessageData);

                                if (isset($hd_message->id) && !empty($email['parts'])) {
                                    $email['parts']['message_id'] = $hd_message->id;

                                    $partmsg = mb_convert_encoding($email['parts']['content'], 'UTF-8', 'UTF-8');
                                    $partmsg = preg_replace('/[^\x00-\x7F]+/', ' ', $partmsg);
                                    $email['parts']['content'] = $partmsg;

                                    HdMessagePart::updateOrCreate([
                                        'message_id' => $hd_message->id,
                                    ], $email['parts']);
                                }

                                if(isset($email['references']) && !empty($email['references']) && is_array($email['references'])){
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
                                }

                                if (isset($hd_message->id) && !empty($email['attachments'])) {

                                    if (!empty($email['attachments'])) {
                                        $uniqueAttachments = [];
                                        foreach ($email['attachments'] as $attachment) {
                                            $fileName = $attachment->getName();
                                            $fileMimeType = $attachment->getMimeType();
                                            $fileSize = $attachment->getSize();
                                            $attachmentID = $attachment->getId();

                                            $key = $fileName . '-' . $fileSize;

                                            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                                            $savePath = storage_path('app/public/email_attachment/');
                                            if (!file_exists($savePath)) {
                                                mkdir($savePath, 0755, true);
                                            }

                                            //$uniqueFileName = uniqid('email_', true) . '.' . $extension;

                                            try {
                                                if (!in_array($key, $uniqueAttachments)) {
                                                    $uniqueAttachments[] = $key;

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

                                                    }
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
                                    }
                                }
                            }
                        }

                        if ($last_filter_date != '') {
                            Log::info('Updating last filter date to: ' . $last_filter_date);
                            $last_filter_datef = \Carbon\Carbon::parse($last_filter_date)->setTimezone('America/New_York');
                            $account->update(['filter_from_date' => $last_filter_datef]);
                        }

                        $this->updateTrackingRecord([
                            'status' => 'completed',
                            'finished_at' => now()
                        ]);
                    } else {
                        $filterDateObj = \Carbon\Carbon::parse($filter_date, 'America/New_York');
                        $maxDate = $filterDateObj->copy()->addDays(1);
                        Log::info("Diff " . json_encode(\Carbon\Carbon::now('America/New_York')->diffInDays($filterDateObj, false)));
                        if (\Carbon\Carbon::now('America/New_York')->diffInDays($filterDateObj, false) < -1) {
                            $account->update(['filter_from_date' => $maxDate->toDateString()]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {

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
}
