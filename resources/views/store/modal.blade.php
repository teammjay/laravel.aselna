<!-- Add Shipping modal -->
<div class="modal fade" id="addShippingModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addShippingModalLabel">Store Shipping Service Editor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="frmStoreShippingService">
                    <div class="mb-3">
                        <label for="shipping-name" class="col-form-label">Name:</label>
                        <input type="text" name="name" class="form-control" id="shipping-name">
                    </div>
                    <div class="mb-3">
                        <label for="carrier_service_id" class="col-form-label">Carrier Service:</label>
                        <select class="form-control" name="carrier_service_id" id="carrier_service_id">
                            @if(count($shipping_services ?? []) > 0)
                                @foreach($shipping_services as $service)
                                    <option value="{{ $service->id }}">{{ $service->carrierName.' '.$service->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStoreShippingService()" id="saveStoreShippingService">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Pages modal -->
<div class="modal fade" id="addPagesModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPagesModalLabel">Store Pages Editor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="frmStorePages">
                    <input type="hidden" id="page_id" name="page_id" value="">
                    <div class="mb-3">
                        <label for="page_type_id" class="col-form-label">Page Type:</label>
                        <select class="form-control" name="page_type" id="page_type">
                            {{--@if(count($pageTypes ?? []) > 0)
                                @foreach($pageTypes as $ptype)
                                    <option value="{{ $ptype->id }}">{{ $ptype->type }}</option>
                                @endforeach
                            @endif--}}
                            <option value="2">Shipping</option>
                            <option value="3">Returns</option>
                            <option value="4">Payments</option>
                            <option value="8">Feedback</option>
                            <option value="10">Return Policy</option>
                        </select>
                        <span class="error-message text-danger" id="page_type-error"></span>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="col-form-label">Title:</label>
                        <input type="text" name="title" class="form-control" id="page_title">
                        <span class="error-message text-danger" id="title-error"></span>
                    </div>
                    <div class="mb-3">
                        <label for="text" class="col-form-label">Text:</label>
                        <textarea class="form-control" name="text" id="page_text" rows="10"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStorePages()" id="saveStorePages">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Category modal -->
<div class="modal fade" id="addCategoryModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Store Cateory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="frmStoreCategory">
                    <div>
                        <label for="cat_code" class="col-form-label">Code:</label>
                        <input type="text" name="code" class="form-control" id="cat_code">
                    </div>
                    <div>
                        <label for="cat_name" class="col-form-label">Name:</label>
                        <input type="text" name="name" class="form-control" id="cat_name">
                        <span class="error-message text-danger" id="name-error"></span>
                    </div>
                    <div class="mb-3">
                        <label for="cat_url" class="col-form-label">Url:</label>
                        <input type="text" name="url" class="form-control" id="cat_url">
                    </div>
                    <div class="mb-3">
                        <div class="card">
                            <div class="card-header">Product Types</div>
                            <div class="card-body">
                                <label for="product_type_id" class="col-form-label">Code:</label>
                                <select class="form-control" name="product_type_id" id="product_type_id">

                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStoreCategory()" id="saveStoreCategory">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Message modal -->
<div class="modal fade" id="addMessageModal">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMessageModalLabel">Message Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <ul class="nav nav-tabs" id="account-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="message-general-tab" data-toggle="pill" href="#acctab1" role="tab" aria-controls="tab1" aria-selected="true">
                            General
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="message-outgoing-tab" data-toggle="pill" href="#acctab2" role="tab" aria-controls="tab2" aria-selected="false">
                            Outgoing Account
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="message-incoming-tab" data-toggle="pill" href="#acctab3" role="tab" aria-controls="tab3" aria-selected="false">
                            Incoming Account
                        </a>
                    </li>

                </ul>
                <form id="frmStoreMessageAccount">
                    <div class="tab-content" id="account-tabContent">

                            <div class="tab-pane fade show active" id="acctab1" role="tabpanel" aria-labelledby="message-general-tab">
                                <input type="hidden" name="store_account_id" id="store_account_id">
                                <div>
                                    <label for="acc_name" class="col-form-label">Name:</label>
                                    <input type="text" name="name" class="form-control" id="acc_name">
                                    <span class="error-message text-danger" id="acc_name-error"></span>
                                </div>
                                <div>
                                    <label for="acc_email" class="col-form-label">Email:</label>
                                    <input type="text" name="email" class="form-control" id="acc_email">
                                    <span class="error-message text-danger" id="acc_email-error"></span>
                                </div>
                                <div>
                                    <label for="api_account" class="col-form-label">API Account:</label>
                                    <input type="checkbox" name="api_account" id="api_account">
                                </div>
                                <div class="mb-3">
                                    <label for="message_active" class="col-form-label">Active:</label>
                                    <input type="checkbox" name="active" id="message_active">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="acctab2" role="tabpanel" aria-labelledby="message-outgoing-tab">
                                <input type="hidden" name="acc_out_id" id="acc_out_id" value="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="acc_out_protocol" class="col-form-label">Account Protocol:</label>
                                        <select class="form-control" name="acc_out_protocol" id="acc_out_protocol">
                                            <option value=""></option>
                                            <option value="1">SMTP</option>
                                            <option value="2">IMAP</option>
                                            <option value="3">POP3</option>
                                            <option value="4">Store API</option>
                                        </select>
                                        <span class="error-message text-danger" id="acc_out_protocol-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_out_email" class="col-form-label">Account Email:</label>
                                        <input type="text" name="acc_out_email" class="form-control" id="acc_out_email">
                                        <span class="error-message text-danger" id="acc_out_email-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_out_user_id" class="col-form-label">Account Userid:</label>
                                        <input type="text" name="acc_out_user_id" class="form-control" id="acc_out_user_id">
                                        <span class="error-message text-danger" id="acc_out_user_id-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_out_password" class="col-form-label">Account Password:</label>
                                        <input type="text" name="acc_out_password" class="form-control" id="acc_out_password">
                                        <span class="error-message text-danger" id="acc_out_password-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_out_server" class="col-form-label">Account Server:</label>
                                        <input type="text" name="acc_out_server" class="form-control" id="acc_out_server">
                                        <span class="error-message text-danger" id="acc_out_server-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_out_port" class="col-form-label">Account Port:</label>
                                        <input type="text" name="acc_out_port" class="form-control" id="acc_out_port">
                                        <span class="error-message text-danger" id="acc_out_port-error"></span>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="acc_out_ssl_port" class="col-form-label">SSL Port:</label>
                                        <input type="text" name="acc_out_ssl_port" class="form-control" id="acc_out_ssl_port">
                                        <span class="error-message text-danger" id="acc_out_ssl_port-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_out_tls_port" class="col-form-label">TLS Port:</label>
                                        <input type="text" name="acc_out_tls_port" class="form-control" id="acc_out_tls_port">
                                        <span class="error-message text-danger" id="acc_out_tls_port-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_out_requires_ssl" class="col-form-label">Required SSL:</label>
                                        <input type="checkbox" name="acc_out_requires_ssl" id="acc_out_requires_ssl">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_out_requires_tls" class="col-form-label">Required TLS:</label>
                                        <input type="checkbox" name="acc_out_requires_tls" id="acc_out_requires_tls">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_out_requires_authentication" class="col-form-label">Required Authentication:</label>
                                        <input type="checkbox" name="acc_out_requires_authentication" id="acc_out_requires_authentication">
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="acctab3" role="tabpanel" aria-labelledby="message-incoming-tab">
                                <input type="hidden" name="acc_in_id" id="acc_in_id" value="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="acc_in_protocol" class="col-form-label">Account Protocol:</label>
                                        <select class="form-control" name="acc_in_protocol" id="acc_in_protocol">
                                            <option value=""></option>
                                            <option value="1">SMTP</option>
                                            <option value="2">IMAP</option>
                                            <option value="3">POP3</option>
                                            <option value="4">Store API</option>
                                        </select>
                                        <span class="error-message text-danger" id="acc_in_protocol-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_email" class="col-form-label">Account Email:</label>
                                        <input type="text" name="acc_in_email" class="form-control" id="acc_in_email">
                                        <span class="error-message text-danger" id="acc_in_email-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_user_id" class="col-form-label">Account Userid:</label>
                                        <input type="text" name="acc_in_user_id" class="form-control" id="acc_in_user_id">
                                        <span class="error-message text-danger" id="acc_in_user_id-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_password" class="col-form-label">Account Password:</label>
                                        <input type="text" name="acc_in_password" class="form-control" id="acc_in_password">
                                        <span class="error-message text-danger" id="acc_in_password-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_server" class="col-form-label">Account Server:</label>
                                        <input type="text" name="acc_in_server" class="form-control" id="acc_in_server">
                                        <span class="error-message text-danger" id="acc_in_server-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_port" class="col-form-label">Account Port:</label>
                                        <input type="text" name="acc_in_port" class="form-control" id="acc_in_port">
                                        <span class="error-message text-danger" id="acc_in_port-error"></span>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="acc_in_ssl_port" class="col-form-label">SSL Port:</label>
                                        <input type="text" name="acc_in_ssl_port" class="form-control" id="acc_in_ssl_port">
                                        <span class="error-message text-danger" id="acc_in_ssl_port-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_tls_port" class="col-form-label">TLS Port:</label>
                                        <input type="text" name="acc_in_tls_port" class="form-control" id="acc_in_tls_port">
                                        <span class="error-message text-danger" id="acc_in_tls_port-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_folders" class="col-form-label">Incoming Folder:</label>
                                        <input type="text" name="acc_in_folders" class="form-control" id="acc_in_folders">
                                        <span class="error-message text-danger" id="acc_in_folders-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_fetch_limit" class="col-form-label">Fetch Limit:</label>
                                        <input type="text" name="acc_in_fetch_limit" class="form-control" id="acc_in_fetch_limit">
                                        <span class="error-message text-danger" id="acc_in_fetch_limit-error"></span>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_requires_ssl" class="col-form-label">Required SSL:</label>
                                        <input type="checkbox" name="acc_in_requires_ssl" id="acc_in_requires_ssl">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_requires_tls" class="col-form-label">Required TLS:</label>
                                        <input type="checkbox" name="acc_in_requires_tls" id="acc_in_requires_tls">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="acc_in_requires_authentication" class="col-form-label">Required Authentication:</label>
                                        <input type="checkbox" name="acc_in_requires_authentication" id="acc_in_requires_authentication">
                                    </div>
                                </div>
                            </div>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStoreMessageAccount()" id="saveStoreMessageAccount">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Logistic Center modal -->
<div class="modal fade" id="addLogisticCenterModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLogisticCenterModalLabel">Store Logistic Center</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="frmStoreLogisticCenter">
                    <div>
                        <label for="logistic_store_id" class="col-form-label">Store:</label>
                        <input type="text" name="store_id" class="form-control" id="logistic_store_id" value="{{ $store->id ?? '' }}" readonly>
                    </div>
                    <div>
                        <label for="logistic_logistic_center_id" class="col-form-label">Logistic Center:</label>
                        <select class="form-control" name="logistic_center_id" id="logistic_logistic_center_id">
                            <option value=""></option>
                            @if(count($logisticCenters ?? []) > 0)
                                @foreach($logisticCenters as $lCenter)
                                    <option value="{{ $lCenter->id }}">{{ $lCenter->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div>
                        <label for="overwrite_from_address" class="col-form-label">Overwrite From Address:</label>
                        <input type="checkbox" name="overwrite_from_address" id="overwrite_from_address">
                    </div>
                    <div>
                        <label for="overwrite_return_address" class="col-form-label">Overwrite Return Address:</label>
                        <input type="checkbox" name="overwrite_return_address" id="overwrite_return_address">
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStoreLogisticCenter()" id="saveStoreLogisticCenter">Save</button>
            </div>
        </div>
    </div>
</div>

