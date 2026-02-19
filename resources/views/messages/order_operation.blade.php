@if(count($orderOperation ?? []) > 0)
    @foreach($orderOperation as $order_operation)
        @if($order_operation->operation_type == \App\Constants\OrderOperationType::OrderReplacement)

            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1"><i class="fa fa-fw fa-exchange-alt" style="font-size: 18px;"></i> </div>
                        <div class="col-md-8">Order Replacement On {{ date('D, d M Y',strtotime($order_operation->created_at)) }} </div>
                        <div class="col-md-3">
                            <div class="btn-group float-right" role="group">
                                <button id="btnGroupDrop2" type="button" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></button>
                                <div class="dropdown-menu" aria-labelledby="btnGroupDrop2">
                                    <a class="dropdown-item" href="javascript:void(0)" id="regenerate_rma_message" data-opid="{{ $order_operation->id }}">Regenerate</a>
                                    @if((isset($order_operation->relatedOperation) && !empty($order_operation->relatedOperation)) && $order_operation->relatedOperation->tracking_number == '')
                                        <a class="dropdown-item" href="javascript:void(0)" id="regenerate_label" data-opid="{{ $order_operation->relatedOperation->id }}" data-orderid="{{ (isset($order_operation->relatedOperation->order) && !empty($order_operation->relatedOperation->order)) ? $order_operation->relatedOperation->order->order_id : '' }}">Regenerate Label</a>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                    @if(isset($order_operation->relatedOperation))
                        <div class="card mt-2">
                            <div class="card-body p-2">
                                <div class="row">
                                    <div class="col-md-12">
                                        @if(isset($order_operation->relatedOperation->order))
                                        Order ID <a class="text-blue replace-orderId" href="{{ route('order.edit',$order_operation->relatedOperation->order->id) }}"> {{ $order_operation->relatedOperation->order->order_id }}</a>   ON  {{ date('D, d M Y',strtotime($order_operation->relatedOperation->created_at)) }}
                                        @endif
                                    </div>
                                </div>
                                <div class="row pt-2">
                                    <div class="col-md-12">
                                        Shipped on {{ date('D, d M Y',strtotime($order_operation->relatedOperation->created_at)) }}
                                    </div>
                                </div>
                                <div class="row pt-2">
                                    <div class="col-md-12">
                                        @if(isset($order_operation->relatedOperation->tracking_number))
                                            <a target="_blank" href="https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1={{ $order_operation->relatedOperation->tracking_number }}" class="text-blue">{{ isset($order_operation->relatedOperation->carrier) ? $order_operation->relatedOperation->carrier->name : '' }} {{ $order_operation->relatedOperation->tracking_number }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($order_operation->operation_type == \App\Constants\OrderOperationType::OrderRefund || $order_operation->operation_type == \App\Constants\OrderOperationType::OrderReturnRefund)
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1"><i class="fa fa-fw fa-undo" style="font-size: 18px;"></i> </div>
                        <div class="col-md-10">Order Refunded On {{ date('D, d M Y',strtotime($order_operation->created_at)) }} </div>

                    </div>
                </div>
            </div>
        @endif

        @if($order_operation->operation_type == \App\Constants\OrderOperationType::OrderCancellation)
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1"><i class="fa fa-fw fa-times" style="font-size: 18px;"></i> </div>
                        <div class="col-md-8">Order Cancelled On {{ date('D, d M Y',strtotime($order_operation->created_at)) }} </div>
                        <div class="col-md-3">
                            <div class="btn-group float-right" role="group">
                                <button id="btnGroupDrop2" type="button" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></button>
                                <div class="dropdown-menu" aria-labelledby="btnGroupDrop2">
                                    <a class="dropdown-item" href="javascript:void(0)" id="regenerate_message" data-opid="{{ $order_operation->id }}">Regenerate</a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @endif

        @if(isset($order_operation->operation_type) && ($order_operation->operation_type == 2 && $order_operation->label_creation_type == 1))
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1"><i class="fa fa-fw fa-spinner" style="font-size: 18px;"></i> </div>
                        <div class="col-md-8">Order Return On {{ date('D, d M Y',strtotime($order_operation->created_at)) }} </div>
                        <div class="col-md-3">
                            <div class="btn-group float-right" role="group">
                                <button id="btnGroupDrop2" type="button" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></button>
                                <div class="dropdown-menu" aria-labelledby="btnGroupDrop2">
                                    <a class="dropdown-item" href="javascript:void(0)" id="regenerate_rma_message" data-opid="{{ $order_operation->id }}">Regenerate</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-md-12">
                            RMA : {{ $order_operation->rma_number }}
                            <input type="hidden" id="rma_number" value="{{ $order_operation->rma_number }}">

                        </div>
                    </div>
                    @if(isset($order_operation->shipFrom))
                        <div class="row pt-2">
                            <div class="col-md-12">
                                Return Location : {{ $order_operation->shipFrom->name }}
                            </div>
                        </div>
                    @endif

                    @if(isset($order_operation->tracking_number))
                        <div class="row pt-2">
                            <div class="col-md-12">
                                @if(isset($order_operation->tracking_number))
                                    <a target="_blank" href="https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1={{ $order_operation->tracking_number }}" class="text-blue">{{ isset($order_operation->carrier) ? $order_operation->carrier->name : '' }} {{ $order_operation->tracking_number }}</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif


        @if(isset($order_operation->rma_number) && ($order_operation->rma_number != '' || $order_operation->rma_number != null) && $order_operation->label_creation_type == 0)
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1"><i class="fa fa-fw fa-spinner" style="font-size: 18px;"></i> </div>
                        <div class="col-md-8">Order Return On {{ date('D, d M Y',strtotime($order_operation->created_at)) }} </div>
                        <div class="col-md-3">
                            <div class="btn-group float-right" role="group">
                                <button id="btnGroupDrop2" type="button" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></button>
                                <div class="dropdown-menu" aria-labelledby="btnGroupDrop2">
                                    <a class="dropdown-item" href="javascript:void(0)" id="regenerate_rma_message" data-opid="{{ $order_operation->id }}">Regenerate</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-md-12">
                            RMA : {{ $order_operation->rma_number }}
                            <input type="hidden" id="rma_number" value="{{ $order_operation->rma_number }}">

                        </div>
                    </div>
                    @if(isset($order_operation->shipFrom))
                        <div class="row pt-2">
                            <div class="col-md-12">
                                Return Location : {{ $order_operation->shipFrom->name }}
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        @endif


        @if(isset($order_operation->tracking_number) && ($order_operation->tracking_number != '' || $order_operation->tracking_number != null) && $order_operation->operation_type == \App\Constants\OrderOperationType::OrderShipment)
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1"><i class="fa fa-fw fa-shipping-fast" style="font-size: 18px;"></i> </div>
                        <div class="col-md-7">Order Shipment On {{ date('D, d M Y',strtotime($order_operation->ship_date)) }} </div>
                        <div class="col-md-4"><button class="btn btn-default w-100" type="button" name="reship" id="reship"> <i class="fas fa-fw fa-pencil-alt"></i> Re-ship</button> </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-md-12">
                            {{ $orderDetail->customer->first_name }} {{ $orderDetail->customer->last_name }}<br>
                            {{ $orderDetail->customerAddress->address_line_1 }},<br>
                            @if($orderDetail->customerAddress->address_line_2 != '')
                                {{ $orderDetail->customerAddress->address_line_2 }},<br>
                            @endif
                            {{ $orderDetail->customerAddress->city}}, {{ $orderDetail->customerAddress->state_code ?? $orderDetail->customerAddress->state}} {{$orderDetail->customerAddress->zip}}
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="col-md-12">
                            @if(isset($order_operation->tracking_number))
                                <a target="_blank" href="https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1={{ $order_operation->tracking_number }}" class="text-blue">{{ isset($order_operation->carrier) ? $order_operation->carrier->name : '' }} {{ $order_operation->tracking_number }}</a>
                            @endif
                        </div>
                    </div>

                    <div class="row pt-2">
                        <div class="col-md-12">
                            Status = {{ \App\Constants\OrderOperationStatus::getOrderStatusName($order_operation->status) }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endif
