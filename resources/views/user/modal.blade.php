
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
                </ul>
                <form id="frmUserAccount">
                    <div class="tab-content" id="account-tabContent">
                        <div class="tab-pane fade show active" id="acctab1" role="tabpanel" aria-labelledby="message-general-tab">
                            <input type="hidden" name="user_account_id" id="user_account_id">
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
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveUserMessageAccount()" id="saveUserMessageAccount">Save</button>
            </div>
        </div>
    </div>
</div>
