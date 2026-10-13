{{-- Script for jobs.run_mode_filter and the Run Mode badge tooltips. --}}
<script>
    function initRunModeFilter(tableSelector, columnIndex) {
        var column = $(tableSelector).DataTable().column(columnIndex);
        var $filter = $('#runModeFilter');

        // stateSave may have restored a filter: show it in the dropdown
        var saved = column.search().replace(/^\^/, '');
        $filter.val($filter.find('option[value="' + saved + '"]').length ? saved : '');

        $filter.on('change', function () {
            var value = $(this).val();
            var escaped = value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            column.search(value ? '^' + escaped : '', true, false).draw();
        });

        // delegated, so rows on other DataTable pages get the tooltip too
        $('body').tooltip({selector: tableSelector + ' [data-toggle="tooltip"]'});
    }
</script>
