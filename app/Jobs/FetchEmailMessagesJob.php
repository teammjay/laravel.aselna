<?php

namespace App\Jobs;

use App\Models\HDMessage;
use App\Models\Message;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Utils\EmailMessageUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
            $stores = Store::with('storeType')->where('store_id','')->get();
            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    $storeMessageAccount = StoreMessageAccount::with('incomingAccount','outgoingAccount')->where('store_id', $store->id)->get();

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

                                    $emailUtility = new EmailMessageUtility($smtpConfig, $imapConfig);
                                    $receiveEmails = $emailUtility->fetchEmails($maccount->incomingAccount->folders);

                                    if(count($receiveEmails ?? []) > 0){
                                        foreach($receiveEmails as $email){
                                            /* create ticket*/
                                            $order_id = $related_order_id = $po_no = $store_id = $reason = $customer_id = $order_operation_id = NULL;
                                            if(isset($email['parsedData']) && !empty($email['parsedData'])){
                                                if(isset($email['parsedData']['customer_order_number'])){
                                                    $related_order_id = html_entity_decode($email['parsedData']['customer_order_number']);
                                                    $orderDetail = Order::with('customer','orderOperation')->where('order_id',trim($related_order_id))->first();
                                                    if($orderDetail){
                                                        $order_id = $orderDetail->id;
                                                        $store_id = $orderDetail->store_id;
                                                        $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : '';
                                                        $order_operation_id = isset($orderDetail->orderOperation) ? $orderDetail->orderOperation->id : '';
                                                    }
                                                }

                                                if(isset($email['parsedData']['purchase_order_number'])){
                                                    $po_no = html_entity_decode($email['parsedData']['purchase_order_number']);
                                                }

                                                if(isset($email['parsedData']['reason'])){
                                                    $reason = html_entity_decode($email['parsedData']['reason']);
                                                }


                                            }
                                            $ticketData = [
                                                'ticket_type_id' => 1,
                                                'ticket_email' => $email['from'],
                                                'status' => 1,
                                                'related_order_id' => $related_order_id,
                                                'subject' => $email['subject'],
                                                'message_id' => 1,
                                                'notes' => $email['body'],
                                                'reference_code' => 1,
                                                'related_order_operation_id' => $order_operation_id,
                                                'order_id' => $order_id,
                                                'store_id' => $store_id,
                                                'ticket_date' => date('Y-m-d H:i:s'),
                                                'ticket_code' => 1,
                                                'message_read' => 0,
                                                'replies' => 0,
                                                'important' => 0,
                                                'customer_merge_status' => 0,
                                                'ticket_user_id' => $customer_id,
                                                'sku' => 0,
                                                'related_listing_item_id' => 0,
                                            ];

                                            $ticketDetail = Message::where('ticket_email',$email['from'])->first();

                                            if($ticketDetail){
                                                $hd_ticket = $ticketDetail;
                                            }else{
                                                $hd_ticket = Message::create($ticketData);
                                            }


                                            if(isset($hd_ticket->id)) {
                                                $hdMessageData = [
                                                    'ticket_id' => $hd_ticket->id,
                                                    'subject' => $email['subject'],
                                                    'message_date' => $email['date'],
                                                    'to_' => $email['to'],
                                                    'cc' => isset($email['cc']) ? $email['cc'] : NULL,
                                                    'bcc' => isset($email['bcc']) ? $email['bcc'] : NULL,
                                                    'message_id' => '',
                                                    'messaging_account_id' => $maccount->id,
                                                    'from_email' => $email['from'],
                                                    'from_name' => $email['name'],
                                                    'message' => $email['body'],
                                                    'in_reply_to' => '',
                                                    'draft' => 0
                                                ];

                                                $hd_ticket = HDMessage::create($hdMessageData);
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
}
