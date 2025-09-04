<!-- RMA Modal -->
<div class="modal fade" id="makeRMAModal">
    <div class="modal-dialog modal-dialog-centered" style="max-width:800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="makeRMAModalLabel">Shipment Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                <button type="button" class="btn btn-primary" id="save_rma"><i class="fa fa-check"></i> Ok </button>
            </div>
        </div>
    </div>
</div>

<!-- Order Refund Modal -->
<div class="modal fade" id="orderRefundModal">
    <div class="modal-dialog modal-dialog-centered" style="max-width:800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderRefundModalLabel">Order Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                <button type="button" class="btn btn-primary" id="refund_order"><i class="fa fa-check"></i> Ok </button>
            </div>
        </div>
    </div>
</div>


<!-- ship order modal -->
<div class="modal fade" id="shipOrderModal">
    <div class="modal-dialog modal-dialog-centered" style="max-width:800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shipOrderModalLabel">Shipment Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                <button type="button" class="btn btn-primary" id="submit_ship_order"><i class="fa fa-check"></i> Ok </button>
            </div>
        </div>
    </div>
</div>

<!-- cancel order modal -->
<div class="modal fade" id="cancelOrderModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                <button type="button" class="btn btn-primary" id="cancelOrder"><i class="fa fa-check"></i> Ok </button>
            </div>
        </div>
    </div>
</div>

<!-- order return modal -->
<div class="modal fade" id="returnLabelModal">
    <div class="modal-dialog modal-dialog-centered" style="max-width:800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="returnLabelModalLabel">Return Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                <button type="button" class="btn btn-primary" id="submit_return_label"><i class="fa fa-check"></i> Ok </button>
            </div>
        </div>
    </div>
</div>

<!-- order replacement modal -->
<div class="modal fade" id="replaceOrderModal">
    <div class="modal-dialog modal-dialog-centered" style="max-width:800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replaceOrderModalLabel">Shipment Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancle</button>
                <button type="button" class="btn btn-primary" id="submit_replace_order"><i class="fa fa-check"></i> Ok </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editPostalAddressModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPostalAddressModalLabel">Postal Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeaddressbtn" data-dismiss="modal">Cancle</button>
                <button type="button" class="btn btn-primary" id="saveAddress"><i class="fa fa-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="findOrdersModal">
    <div class="modal-dialog modal-dialog-centered" style="max-width:950px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="findOrdersModalLabel">Find Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card card-default">
                    <div class="card-header">
                        <h3 class="card-title"><a href="javascript:void(0);" class="text-blue">Add search condition</a></h3>
                        <i class="fa fa-angle-down float-right"></i>
                    </div>
                    <div class="card-body">
                        <form id="filter-form">
                            <div class="row pt-3">
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-4 text-right">
                                            Store
                                        </div>
                                        <div class="col-8">
                                            <select class="form-control" name="store_id" id="store_id">
                                                <option value="">All Stores</option>
                                                @if(count($stores ?? []) > 0)
                                                    @foreach($stores as $store)
                                                        <option
                                                                value="{{ $store->id }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-4 text-right">
                                            Order ID
                                        </div>
                                        <div class="col-8">
                                            <input type="text" name="order_id" id="order_id" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-4 text-right">
                                            Order Date
                                        </div>
                                        <div class="col-8">
                                            <input type="text" name="order_date" id="order_date" class="form-control">
                                        </div>
                                    </div>
                                </div>


                            </div>

                            <div class="row pt-3">
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-4 text-right">
                                            First Name
                                        </div>
                                        <div class="col-8">
                                            <input type="text" name="buyer_first_name" id="buyer_first_name" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-4 text-right">
                                            Last Name
                                        </div>
                                        <div class="col-8">
                                            <input type="text" name="buyer_last_name" id="buyer_last_name" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-4 text-right">
                                            SKU
                                        </div>
                                        <div class="col-8">
                                            <input type="text" name="sku" id="sku" class="form-control">
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="row pt-3">
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-4 text-right">
                                            Order Status
                                        </div>
                                        <div class="col-8">
                                            <select class="form-control" name="order_status" id="order_status">
                                                <option value="">--</option>
                                                <option value="1">Pending</option>
                                                <option value="2">Ready</option>
                                                <option value="3">Acknowledged</option>
                                                <option value="4">Shipped</option>
                                                <option value="5">Canceled</option>
                                                <option value="6">Synchronizing</option>
                                                <option value="7">PartiallyShipped</option>
                                                <option value="8">SynchronizatonFailed</option>
                                                <option value="9">Refund</option>
                                                <option value="10">Replaced</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-4 text-right">
                                            Purchase Order ID
                                        </div>
                                        <div class="col-8">
                                            <input type="text" name="purchase_order_id" id="purchase_order_id" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-4 text-right">
                                            Created Date
                                        </div>
                                        <div class="col-8">
                                            <input type="text" name="created_at" id="created_at" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row pt-3">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <button type="submit" class="form-control btn btn-primary">Apply Filter</button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <button type="reset" class="form-control btn btn-secondary">Reset</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="ordersTable" class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Store</th>
                            <th>Order Date</th>
                            <th>Order Image</th>
                            <th>Order SKU</th>
                            <th>Order Status</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Order Total</th>
                            <th>Buyer Last Name</th>
                            <th>Purchase Order Id</th>
                        </tr>
                        </thead>
                        <tbody id="order-list">


                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeaddressbtn" data-dismiss="modal">Cancle</button>
                <button type="button" class="btn btn-primary" id="selectOrder"><i class="fa fa-check"></i> Select</button>
            </div>
        </div>
    </div>
</div>