<?php

namespace App\Jobs;

use App\Models\HdMessageAttachment;
use App\Models\Sequence;
use App\Models\SysFile;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30000; // Set a timeout for the job

    protected $storeMessageAccount;
    protected $store;
    protected $store_type;

    public function __construct($storeMessageAccount, $store, $store_type)
    {
        $this->storeMessageAccount = $storeMessageAccount;
        $this->store = $store;
        $this->store_type = $store_type;
    }

    public function handle()
    {
        try {
            Log::info('Store Name: ' . $this->store->name);
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
                    $receiveEmails = $emailUtility->fetchEmails($store_type, $filter_date, $folder);
                    $last_filter_date = '';

                    if (count($receiveEmails ?? []) > 0) {
                        foreach ($receiveEmails as $email) {

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

                                if (isset($email['parsedData']['purchase_order_number'])) {
                                    $po_no = html_entity_decode($email['parsedData']['purchase_order_number']);
                                }

                                if (isset($email['parsedData']['reason'])) {
                                    $reason = html_entity_decode($email['parsedData']['reason']);
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
                                'status' => HdTicketStatus::NEEDS_ACTION,
                                'related_order_id' => $related_order_id,
                                'subject' => trim($email['subject']),
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

                            Log::info('Email From : ' . $email['from'] . ' Message ID: ' . $email['message_id'].' Reference ID: '.$email['references']);
                            /*$ticketDetail = Message::where('ticket_email', trim($email['from']))->orWhere('ticket_email', trim($email['reply_to']))->first();*/
                            $query = Message::where('message_id', trim($email['message_id']));
                            if(isset($email['references']) && $email['references'] != ''){
                                $query->orWhere('message_id', trim($email['references']));
                                $query->orWhere('message_id', trim($email['in_reply_to']));
                            }
                            $ticketDetail = $query->first();

                            if ($ticketDetail) {
                                if ($order_id) {
                                    $ticketDetail->order_id = $order_id;
                                    $ticketDetail->ticket_type_id = $ticketTypeDetail->id;
                                    $ticketDetail->save();
                                }
                                $hd_ticket = $ticketDetail;
                            } else {
                                $hd_ticket = Message::create($ticketData);
                            }

                            if (isset($hd_ticket->id)) {
                                $hdMessageData = [
                                    'ticket_id' => $hd_ticket->id,
                                    'subject' => trim($email['subject']),
                                    'message_date' => $email['date'],
                                    'to_' => $email['to'],
                                    'cc' => isset($email['cc']) ? $email['cc'] : NULL,
                                    'bcc' => isset($email['bcc']) ? $email['bcc'] : NULL,
                                    'message_id' => trim($email['message_id']),
                                    'messaging_account_id' => $maccount->incomingAccount->id,
                                    'from_email' => trim($email['from']),
                                    'from_name' => trim($email['name']),
                                    'message' => $email['body'],
                                    'in_reply_to' => $email['in_reply_to'],
                                    'draft' => 0,
                                    'reply_to_name' => $email['reply_to_name'],
                                    'reply_to_email' => $email['reply_to'],
                                ];

                                $hd_message = HDMessage::updateOrCreate([
                                    'ticket_id' => $hd_ticket->id,
                                    'message_id' => trim($email['message_id']),
                                ], $hdMessageData);

                                if (isset($hd_message->id) && !empty($email['parts'])) {
                                    $email['parts']['message_id'] = $hd_message->id;
                                    HdMessagePart::updateOrCreate([
                                        'message_id' => $hd_message->id,
                                    ], $email['parts']);
                                }

                                if (isset($hd_message->id) && !empty($email['attachments'])) {

                                    if(!empty($email['attachments'])){
                                        $uniqueAttachments = [];
                                        foreach($email['attachments'] as $attachment){
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
                                                if(!in_array($key,$uniqueAttachments)) {
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

                                            } catch (\Exception $e) {
                                                echo "Error saving attachment: " . $e->getMessage();
                                                Log::error('Error saving attachment: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($last_filter_date != '') {
                            $account->update(['filter_from_date' => $last_filter_date]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
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

    public static function getNextTicketNumberForStore($storeId) {
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
