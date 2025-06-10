@if(isset($orderOperation->tracking_number) && ($orderOperation->tracking_number != '' || $orderOperation->tracking_number != null))
    <div class="row">
        <div class="col-md-2"><i class="fa fa-fw fa-shipping-fast"></i> </div>
        <div class="col-md-6">Order Shipment On {{ $orderOperation->ship_date }} </div>
        <div class="col-md-4"><button class="btn btn-default" type="button" name="reship" id="reship"> <i class="fas fa-fw fa-pencil-alt"></i> Re-ship</button> </div>
    </div>
    <div class="row pt-3">
        <div class="col-md-12">
            {{ $orderDetail->customer->first_name }} {{ $orderDetail->customer->last_name }}<br>
            {{ $orderDetail->customerAddress->address_line_1 }},<br>
            @if($orderDetail->customerAddress->address_line_2 != '')
                {{ $orderDetail->customerAddress->address_line_2 }},<br>
            @endif
            {{ $orderDetail->customerAddress->city}}, {{$orderDetail->customerAddress->state}} {{$orderDetail->customerAddress->zip}}
        </div>
    </div>
    <div class="row pt-3">
        <div class="col-md-12">
                @if(isset($orderOperation->tracking_number))
                    <a href="" class="text-blue">{{ isset($orderOperation->carrier) ? $orderOperation->carrier->name : '' }} {{ $orderOperation->tracking_number }}</a>
            @endif
        </div>
    </div>

    <div class="row pt-3">
        <div class="col-md-12">
            Status = {{ \App\Constants\OrderOperationStatus::getOrderStatusName($orderOperation->status) }}
        </div>
    </div>
@endif