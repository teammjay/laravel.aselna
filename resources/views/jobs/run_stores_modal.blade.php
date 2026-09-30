{{--
    "Run Now" popup for store-scoped jobs, shared by the Saved Jobs and Cron Jobs lists.
    A button with class "js-run-stores" and data-run-url / data-stores-url / data-job-name opens it.
    Include jobs.run_stores_js in the page's adminlte_js section, and @section('plugins.Select2', true).
--}}
<style>
    /* AdminLTE colours Select2 chips' text white while Select2 4.0.3 gives them a light grey background */
    #runStoresModal .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff;
        border-color: #0069d9;
        color: #fff;
        padding: 1px 8px;
        margin-top: 5px;
    }
    #runStoresModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255, 255, 255, 0.8);
        margin-right: 6px;
    }
    #runStoresModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #fff;
    }
    /* "Default stores" chosen: the picker is disabled, keep the chips readable but visibly inactive */
    #runStoresModal .select2-container--disabled .select2-selection--multiple {
        background-color: #e9ecef;
    }
    #runStoresModal .select2-container--disabled .select2-selection--multiple .select2-selection__choice {
        background-color: #6c757d;
        border-color: #5a6268;
    }
</style>
<div class="modal fade" id="runStoresModal" tabindex="-1" role="dialog" aria-labelledby="runStoresModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" id="runStoresForm" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="runStoresModalTitle">Run job</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="run-stores-loading text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin"></i> Loading stores...
                </div>
                <div class="run-stores-error alert alert-danger d-none"></div>

                <div class="run-stores-body d-none">
                    <div class="custom-control custom-radio">
                        <input type="radio" id="runScopeDefault" name="scope" value="default" class="custom-control-input" checked>
                        <label class="custom-control-label" for="runScopeDefault">
                            Default stores <span class="text-muted font-weight-normal">(same as the scheduled run)</span>
                        </label>
                    </div>
                    <ul class="run-stores-defaults small text-muted pl-5 mb-3"></ul>

                    <div class="custom-control custom-radio">
                        <input type="radio" id="runScopeSelected" name="scope" value="selected" class="custom-control-input">
                        <label class="custom-control-label" for="runScopeSelected">Only these stores</label>
                    </div>
                    <div class="pl-4 mt-2">
                        <select name="store_ids[]" id="runStoreIds" class="form-control" multiple style="width: 100%"></select>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div>
                                <button type="button" class="btn btn-link btn-sm p-0 mr-3 run-stores-all">Select all</button>
                                <button type="button" class="btn btn-link btn-sm p-0 run-stores-clear">Clear</button>
                            </div>
                            <small class="text-muted run-stores-eligible"></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary run-stores-submit" disabled>Run</button>
            </div>
        </form>
    </div>
</div>
