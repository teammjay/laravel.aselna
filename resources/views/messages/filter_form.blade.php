@php use App\Constants\FilterCodes; @endphp
<form id="filter-form">
    <input type="hidden" name="store_id" id="store_id">
    <div class="row">
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    First Name
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="first_name_operator">
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <input type="text" name="first_name" id="first_name" class="form-control">
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    Last Name
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="last_name_operator">
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <input type="text" name="last_name" id="last_name" class="form-control">
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    Subject
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="subject_operator">
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <input type="text" name="subject" id="subject" class="form-control">
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    Order Id
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="order_id_operator">
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <input type="text" name="order_id" id="order_id" class="form-control">
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    Ticket Code
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="ticket_code_operator">
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <input type="text" name="ticket_code" id="ticket_code" class="form-control">
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    Body
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="body_operator">
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                    </select>
                </div>
                <div class="col-6">
                    <input type="text" name="body" id="body" class="form-control">
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="row">
                <div class="col-6 text-right">
                    Product Code
                </div>
                <div class="col-6">
                    <input type="text" name="product_code" id="product_code" class="form-control">
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    Ticket Type
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="ticket_type_operator">
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <select class="form-control" name="ticket_type">
                        <option value=""></option>
                        @if(count($ticketTypes ?? []) > 0)
                            @foreach($ticketTypes as $tt)
                                <option value="{{ $tt->id }}">{{ $tt->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-6 text-right">
                    RMA
                </div>
                <div class="col-6">
                    <input type="text" name="rma" id="rma" class="form-control">
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    Sender Email
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="sender_email_operator">
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <input type="text" name="sender_email" id="sender_email" class="form-control">
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    Reference Id
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="reference_id_operator">
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <select class="form-control" id="reference_id" name="reference_id">
                        <option></option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    User Id
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="user_id_operator">
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <input class="form-control" type="text" name="user_id" id="user_id">
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="row">
                <div class="col-3 text-right">
                    Username
                </div>
                <div class="col-3">
                    <select class="custom-select" id="" name="username_operator">
                        <option value="{{FilterCodes::CONTAINS}}">contains</option>
                        <option value="{{FilterCodes::STARTS_WITH}}">starts with</option>
                        <option value="{{FilterCodes::ENDS_WITH}}">ends with</option>
                        <option value="{{FilterCodes::IN}}">in</option>
                        <option value="{{FilterCodes::EQUAL}}">=</option>
                        <option value="{{FilterCodes::NOT_IN}}">not in</option>
                        <option value="{{FilterCodes::DOESNT_CONTAIN}}">doesn't contain</option>
                        <option value="{{FilterCodes::IS_SET}}">is set</option>
                    </select>
                </div>
                <div class="col-6">
                    <input type="text" name="username" id="username" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-2">
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" id="apply_filter" class="form-control btn btn-primary">Filter</button>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="reset" class="form-control btn  btn-reset btn-secondary" id="resetForm">Reset</button>
            </div>
        </div>
    </div>

</form>