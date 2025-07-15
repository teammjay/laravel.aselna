<?php

namespace App\Jobs;

use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Models\HDMessage;
use App\Models\HdMessagePart;
use App\Models\HdTicketType;
use App\Models\Message;
use App\Models\Order;
use App\Models\Sequence;
use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Utils\EmailMessageUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FetchEmailMessagesJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            /* get shopify stores*/
            $stores = Store::with('storeType')->get();

            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    Log::info('store:'.$store->name);
                    $storeMessageAccount = StoreMessageAccount::with('incomingAccount','outgoingAccount')->where('store_id', $store->id)->where('active', 1)->get();
                    $store_type = $store->storeType->name;
                    $store_id = $store->id;
                    if(count($storeMessageAccount ?? []) > 0){
                        foreach($storeMessageAccount as $maccount){
                            $smtpConfig = $imapConfig = [];
                            if($maccount->incoming_account_id !='') {
                                if(isset($maccount->incomingAccount)) {
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

                                    Log::info('Incomming Account ID:'.$maccount->incomingAccount->id);

                                    Log::info('Imap Config :'.json_encode($imapConfig));

                                    $emailUtility = new EmailMessageUtility($smtpConfig, $imapConfig);
                                    $receiveEmails = $emailUtility->fetchEmails($store_type, $filter_date, $folder);
                                    $last_filter_date = '';
                                    if(count($receiveEmails ?? []) > 0){
                                        foreach($receiveEmails as $email){

                                            Log::info('Emails :'.json_encode($email));

                                            $last_filter_date = $email['date'];
                                            /* create ticket*/
                                            $order_id = $related_order_id = $po_no = $reason = $customer_id = $order_operation_id = $reference_code = NULL;
                                            $ticket_type_code = HdTicketTypeCode::MARKETPLACE_NOTIFICATION;
                                            if(isset($email['from'])) {
                                                $customerDetail = Customer::select('customers.*')->join('customer_emails as CE', 'CE.customer_id', '=', 'customers.id', 'left')->whereLike('customers.email', '%' . $email['from'] . '%')->orWhere('CE.email_address', 'like', '%' . $email['from'] . '%')->first();

                                                if($customerDetail){
                                                    $customer_id = $customerDetail->id;
                                                }
                                            }

                                            if(isset($email['parsedData']) && !empty($email['parsedData'])){
                                                if(isset($email['parsedData']['order_id'])){
                                                    $order_id = html_entity_decode($email['parsedData']['order_id']);
                                                    $orderDetail = Order::with('customer','orderOperation')->where('order_id',trim($order_id))->first();
                                                    if($orderDetail){
                                                        $related_order_id = $orderDetail->id;
                                                        $store_id = $orderDetail->store_id;
                                                        $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : NULL;
                                                        $order_operation_id = isset($orderDetail->orderOperation) ? $orderDetail->orderOperation->id : NULL;
                                                    }
                                                }

                                                if(isset($email['parsedData']['purchase_order_number'])){
                                                    $po_no = html_entity_decode($email['parsedData']['purchase_order_number']);
                                                }

                                                if(isset($email['parsedData']['reason'])){
                                                    $reason = html_entity_decode($email['parsedData']['reason']);
                                                }

                                                if(isset($email['parsedData']['ticket_type_id'])){
                                                    $ticket_type_code = html_entity_decode($email['parsedData']['ticket_type_id']);
                                                }

                                                if(isset($email['parsedData']['reference_id'])){
                                                    $reference_code = html_entity_decode($email['parsedData']['reference_id']);
                                                }
                                            }

                                            if($customer_id == NULL){

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
                                                ],$customerEmailData);

                                            }else{
                                                $customerDetail = Customer::findOrFail($customer_id);
                                                if($customerDetail->email != $email['from']){
                                                    $customerEmailData = [
                                                        'customer_id' => $customer_id,
                                                        'email_address' => $email['from'],
                                                    ];
                                                    CustomerEmail::updateOrCreate([
                                                        'customer_id' => $customer_id,
                                                        'email_address' => $email['from']
                                                    ],$customerEmailData);
                                                }
                                            }

                                            $ticket_code = $store->short_code.'-'.$this->getNextTicketNumberForStore($store->id);
                                            $ticketTypeDetail = HdTicketType::where('code',$ticket_type_code)->first();
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
                                                'store_id' => $store_id,
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

                                            $ticketDetail = Message::where('ticket_email',trim($email['from']))->first();

                                            if($ticketDetail){
                                                Log::info('Ticket Exist');
                                                if($order_id){
                                                    $ticketDetail->order_id = $order_id;
                                                    $ticketDetail->ticket_type_id = $ticketTypeDetail->id;
                                                    $ticketDetail->save();
                                                }
                                                $hd_ticket = $ticketDetail;
                                            }else{
                                                Log::info('New Ticket');
                                                $hd_ticket = Message::create($ticketData);
                                            }

                                            Log::info('Ticket Detail :'.json_encode($hd_ticket));

                                            if(isset($hd_ticket->id)) {
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
                                                    'in_reply_to' => $email['reply_to'],
                                                    'draft' => 0,
                                                    'reply_to_name' => $email['reply_to_name'],
                                                    'reply_to_email' => $email['reply_to'],
                                                ];

                                                Log::info('Ticket Message Detail :'.json_encode($hdMessageData));

                                                $hd_message = HDMessage::updateOrCreate([
                                                    'ticket_id' => $hd_ticket->id,
                                                    'message_id' => trim($email['message_id']),
                                                ], $hdMessageData);

                                                if(isset($hd_message->id) && !empty($email['parts'])){
                                                    $email['parts']['message_id'] = $hd_message->id;
                                                    HdMessagePart::updateOrCreate([
                                                        'message_id' => $hd_message->id,
                                                    ], $email['parts']);
                                                }
                                            }
                                        }
                                        Log::info('Last filter date :'.$last_filter_date);
                                        if($last_filter_date != ''){
                                            $account->update(['filter_from_date' => $last_filter_date]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }catch (\Exception $e) {
            Log::error('Error: ' . $e->getMessage());
        }
    }

    function getNextTicketNumberForStore($storeId) {
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
