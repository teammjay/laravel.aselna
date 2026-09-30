<script>
    // Run Now popup for store-scoped jobs (markup: jobs.run_stores_modal)
    $(function () {
        var $modal = $('#runStoresModal');
        var $form = $('#runStoresForm');
        var $select = $('#runStoreIds');
        var $submit = $modal.find('.run-stores-submit');
        var request = null;

        function scope() {
            return $form.find('input[name="scope"]:checked').val();
        }

        function refresh() {
            var onlySelected = scope() === 'selected';
            var count = ($select.val() || []).length;

            // A disabled select is not submitted, so "default" posts no store_ids
            $select.prop('disabled', !onlySelected);
            $modal.find('.run-stores-all, .run-stores-clear').prop('disabled', !onlySelected);

            if (!onlySelected) {
                $submit.prop('disabled', false).text('Run for default stores');
            } else if (count === 0) {
                $submit.prop('disabled', true).text('Select at least one store');
            } else {
                $submit.prop('disabled', false).text('Run for ' + count + ' store' + (count === 1 ? '' : 's'));
            }
        }

        function initSelect() {
            if (!$.fn.select2) {
                return;
            }
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                dropdownParent: $modal,
                placeholder: 'Search stores',
                closeOnSelect: false,
                width: '100%'
            });
        }

        function render(data) {
            $select.empty();

            var groups = {};
            var defaults = [];
            $.each(data.stores, function (i, store) {
                if (!groups[store.type]) {
                    groups[store.type] = $('<optgroup>').attr('label', store.type).appendTo($select);
                }
                $('<option>').val(store.id).text(store.label).appendTo(groups[store.type]);
                if (store.is_default) {
                    defaults.push(store.id);
                }
            });

            var $defaults = $modal.find('.run-stores-defaults').empty();
            $.each(data.jobs, function (i, job) {
                var text = data.jobs.length > 1 ? job.name + ': ' + job.default_stores : job.default_stores;
                $('<li>').text(text).appendTo($defaults);
            });

            var types = Object.keys(groups);
            $modal.find('.run-stores-eligible').text(
                data.stores.length + ' eligible store' + (data.stores.length === 1 ? '' : 's') + (types.length ? ' (' + types.join(', ') + ')' : '')
            );

            // Start "only these" from the default stores, so trimming the default run is one click away
            $select.val(defaults);
            $('#runScopeDefault').prop('checked', true);
            initSelect();
            refresh();

            $modal.find('.run-stores-loading').addClass('d-none');
            $modal.find('.run-stores-body').removeClass('d-none');
        }

        $(document).on('click', '.js-run-stores', function (e) {
            e.preventDefault();
            var $btn = $(this);

            $form.attr('action', $btn.data('run-url'));
            $modal.find('.modal-title').text('Run ' + $btn.data('job-name'));
            $modal.find('.run-stores-body, .run-stores-error').addClass('d-none');
            $modal.find('.run-stores-loading').removeClass('d-none');
            $submit.prop('disabled', true).text('Run');
            $modal.modal('show');

            if (request) {
                request.abort();
            }
            request = $.getJSON($btn.data('stores-url'))
                .done(function (data) {
                    if (!data.scoped || !data.stores.length) {
                        $modal.find('.run-stores-loading').addClass('d-none');
                        $modal.find('.run-stores-error').removeClass('d-none').text('No eligible stores found for this job.');
                        return;
                    }
                    render(data);
                })
                .fail(function (xhr, status) {
                    if (status === 'abort') {
                        return;
                    }
                    $modal.find('.run-stores-loading').addClass('d-none');
                    $modal.find('.run-stores-error').removeClass('d-none').text('Could not load the stores for this job.');
                });
        });

        $form.on('change', 'input[name="scope"]', refresh);
        $select.on('change', refresh);

        $modal.on('click', '.run-stores-all', function () {
            $select.find('option').prop('selected', true);
            $select.trigger('change');
        });

        $modal.on('click', '.run-stores-clear', function () {
            $select.val([]).trigger('change');
        });

        $form.on('submit', function () {
            $submit.prop('disabled', true).text('Queuing...');
        });
    });
</script>
