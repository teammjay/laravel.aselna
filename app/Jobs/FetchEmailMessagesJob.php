<?php

namespace App\Jobs;

use App\Constants\HdTicketTypeCode;
use App\Models\Customer;
use App\Models\HDMessage;
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
            $stores = Store::with('storeType')->where('id','0dd4de79-bba1-c47a-a7aa-822e6f015b8e')->get();

            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    Log::info('store:'.$store->name);
                    $storeMessageAccount = StoreMessageAccount::with('incomingAccount','outgoingAccount')->where('store_id', $store->id)->get();
                    $store_type = $store->storeType->name;
                    $store_id = $store->id;
                    if(count($storeMessageAccount ?? []) > 0){
                        foreach($storeMessageAccount as $maccount){
                            $smtpConfig = $imapConfig = [];
                            /*if($maccount->outgoing_account_id !='') {
                                $smtpConfig = [
                                    'host' => $maccount->incomingAccount->server,
                                    'port' => $maccount->incomingAccount->port,
                                    'encryption' => 'tls',
                                    'username' => $maccount->incomingAccount->user_id,
                                    'password' => $maccount->incomingAccount->password,
                                    'from_address' => $maccount->incomingAccount->email,
                                    'from_name' => $store->name,
                                ];
                            }*/

                            if($maccount->incoming_account_id !='') {
                                if(isset($maccount->incomingAccount)) {
                                    $imapConfig = [
                                        'host' => $maccount->incomingAccount->server,
                                        'port' => $maccount->incomingAccount->port,
                                        'encryption' => 'ssl',
                                        'username' => $maccount->incomingAccount->user_id,
                                        'password' => $maccount->incomingAccount->password,
                                    ];

                                    Log::info('Imap Config :'.json_encode($imapConfig));

                                    $emailUtility = new EmailMessageUtility($smtpConfig, $imapConfig);
                                    $receiveEmails = $emailUtility->fetchEmails($maccount->incomingAccount->folders, $store_type);

                                    if(count($receiveEmails ?? []) > 0){
                                        foreach($receiveEmails as $email){

                                            Log::info('Emails :'.json_encode($email));
                                            /* create ticket*/
                                            $order_id = $related_order_id = $po_no = $reason = $customer_id = $order_operation_id = $reference_code = NULL;
                                            $ticket_type_code = HdTicketTypeCode::ORDER_INFORMATION;
                                            $customerDetail = Customer::whereLike('email','%'.$email['from'].'%')->first();

                                            if($customerDetail){
                                                $customer_id = $customerDetail->id;
                                            }

                                            if(isset($email['parsedData']) && !empty($email['parsedData'])){
                                                if(isset($email['parsedData']['customer_order_number'])){
                                                    $order_id = html_entity_decode($email['parsedData']['customer_order_number']);
                                                    $orderDetail = Order::with('customer','orderOperation')->where('order_id',trim($related_order_id))->first();
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

                                            $ticket_code = $store->short_code.'-'.$this->getNextTicketNumberForStore($store->id);
                                            $ticketTypeDetail = HdTicketType::where('code',$ticket_type_code)->first();
                                            $ticketData = [
                                                'ticket_type_id' => $ticketTypeDetail->id,
                                                'ticket_email' => $email['from'],
                                                'status' => 0,
                                                'related_order_id' => $related_order_id,
                                                'subject' => $email['subject'],
                                                'message_id' => $email['message_id'],
                                                'notes' => $email['body'],
                                                'reference_code' => $reference_code,
                                                'related_order_operation_id' => $order_operation_id,
                                                'order_id' => $order_id,
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

                                            $ticketDetail = Message::where('ticket_email',$email['from'])->first();

                                            if($ticketDetail){
                                                Log::info('Ticket Exist');
                                                $hd_ticket = $ticketDetail;
                                            }else{
                                                Log::info('New Ticket');
                                                $hd_ticket = Message::create($ticketData);
                                            }

                                            Log::info('Ticket Detail :'.json_encode($hd_ticket));

                                            if(isset($hd_ticket->id)) {
                                                $hdMessageData = [
                                                    'ticket_id' => $hd_ticket->id,
                                                    'subject' => $email['subject'],
                                                    'message_date' => $email['date'],
                                                    'to_' => $email['to'],
                                                    'cc' => isset($email['cc']) ? $email['cc'] : NULL,
                                                    'bcc' => isset($email['bcc']) ? $email['bcc'] : NULL,
                                                    'message_id' => $email['message_id'],
                                                    'messaging_account_id' => $maccount->id,
                                                    'from_email' => $email['from'],
                                                    'from_name' => $email['name'],
                                                    'message' => $email['body'],
                                                    'in_reply_to' => NULL,
                                                    'draft' => 0,
                                                    'reply_to_name' => $email['name'],
                                                    'reply_to_email' => $email['from'],
                                                ];

                                                Log::info('Ticket Detail :'.json_encode($hdMessageData));

                                                $hd_ticket = HDMessage::updateOrCreate([
                                                    'ticket_id' => $hd_ticket->id,
                                                    'message_id' => $email['message_id'],
                                                ], $hdMessageData);
                                            }
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
