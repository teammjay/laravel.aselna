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
            $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->get();
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
                                            $order_id = $po_no = $store_id = $reason = $customer_id = '';
                                            if(isset($email['parsedData']) && !empty($email['parsedData'])){
                                                if(isset($email['parsedData']['customer_order_number'])){
                                                    $order_id = $email['parsedData']['customer_order_number'];
                                                    $orderDetail = Order::with('customer')->where('order_id',trim($order_id));
                                                    if(isset($orderDetail)){
                                                        $store_id = $orderDetail->store_id;
                                                        $customer_id = isset($orderDetail->customer) ? $orderDetail->customer->id : '';
                                                    }
                                                }

                                                if(isset($email['parsedData']['purchase_order_number'])){
                                                    $po_no = $email['parsedData']['purchase_order_number'];
                                                }

                                                if(isset($email['parsedData']['reason'])){
                                                    $reason = $email['parsedData']['reason'];
                                                }


                                            }
                                            $ticketData = [
                                                'ticket_type_id' => 1,
                                                'ticket_email' => $email['from'],
                                                'status' => 1,
                                                'subject' => $email['subject'],
                                                'message_id' => 1,
                                                'notes' => $email['body'],
                                                'reference_code' => 1,
                                                'related_order_operation_id' => 1,
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

                                            $hd_ticket = Message::updateOrCreate([
                                                'subject' => $email['subject'],
                                            ],$ticketData);

                                            if(isset($hd_ticket->id)) {
                                                $hdMessageData = [
                                                    'ticket_id' => $hd_ticket->id,
                                                    'subject' => $email['subject'],
                                                    'message_date' => $email['date'],
                                                    'to_' => $email['to'],
                                                    'cc' => $email['cc'],
                                                    'bcc' => $email['bcc'],
                                                    'message_id' => '',
                                                    'messaging_account_id' => $maccount->id,
                                                    'from_email' => $email['from'],
                                                    'from_name' => $email['name'],
                                                    'message' => $email['body'],
                                                    'in_reply_to' => '',
                                                    'draft' => 0
                                                ];

                                                $hd_ticket = HDMessage::create($ticketData);
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
