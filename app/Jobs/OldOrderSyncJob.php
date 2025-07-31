<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OldOrderSyncJob implements ShouldQueue
{
    use Queueable;

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
        // Order Syncing
        DB::connection('easyesale_db')->table('order')
            ->where('create_ts', '<', now()->subDays(180))
            ->orderBy('id')
            ->chunk(100, function ($orders) {
                foreach ($orders as $order) {
                    $this->syncOldOrder($order);
                }
            });
    }

    private function syncOldOrder(mixed $order)
    {
        $customer = DB::connection('easyesale_db')->table('customer')
            ->where('id', $order->customer_id)
            ->first();
        $createdCustomer = null;
        if ($customer) {
            $createdCustomer = Customer::updateOrCreate(
                ['id' => $customer->id],
                [
                    'id' => $customer->id,
                    'first_name' => $customer->first_name ?? '',
                    'last_name' => $customer->last_name ?? '',
                ]
            );
            Log::info('Syncing old customer with ID: ' . $createdCustomer->id);
        }

        Log::info('Syncing old order with ID: ' . $order->id);
        Order::updateOrCreate(
            ['id' => $order->id],
            [
                'id' => $order->id,
                'store_id' => $order->store_id,
                'order_status' => $order->order_status,
                'order_date' => $order->order_date,
                'buyer_first_name' => $order->buyer_first_name,
                'buyer_last_name' => $order->buyer_last_name,
                'buyer_email' => $order->buyer_email,
                'order_total' => $order->order_total,
                'purchase_order_id' => $order->purchase_order_id,
                'customer_id' => $createdCustomer->id,
            ]
        );

        $orderItems = DB::connection('easyesale_db')->table('order_item')
            ->where('order_id', $order->id)
            ->get();

        foreach ($orderItems as $item) {
            OrderItem::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'sku' => $item->sku,
                ],
                [
                    'id' => $item->id,
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'sku' => $item->sku,
                    'store_sku' => $item->store_sku,
                    'quantity' => $item->quantity,
                    'item_price' => $item->item_price,
                    'title' => $item->title,
                    'shipping_price' => $item->shipping_price,
                    'item_tax' => $item->item_tax,
                    'shipping_tax' => $item->shipping_tax,
                    'shipped_quantity' => $item->shipped_quantity,
                    'canceled_quantity' => $item->canceled_quantity,
                    'refund_quantity' => $item->refund_quantity,
                ]
            );
        }

        $orderOperations = DB::connection('easyesale_db')->table('order_operation')
            ->where('order_id', $order->id)
            ->get();

        foreach ($orderOperations as $operation) {
            OrderOperation::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                ],
                [
                    'id' => $operation->id,
                    'order_id' => $order->id,
                    'store_id' => $operation->store_id,
                    'operation_type' => $operation->operation_type,
                    'tracking_number' => $operation->tracking_number,
                    'sync_status' => $operation->sync_status,
                    'sync_message' => $operation->sync_message,
                    'status' => $operation->status,
                    'status_message' => $operation->status_message,
                    'label_creation_type' => $operation->label_creation_type,
                    'return_operation_id' => $operation->return_operation_id,
                    'insurance_value' => $operation->insurance_value,
                    'signature_confirmation' => $operation->signature_confirmation,
                ]
            );
        }
    }
}
