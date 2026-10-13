{{--
    Run Mode legend + filter above the Saved Jobs / Cron Jobs tables.
    Wire it up with initRunModeFilter(tableSelector, columnIndex) from jobs.run_mode_js after the DataTable is created.
--}}
<div class="d-flex flex-wrap align-items-center mb-3">
    <div class="form-inline mr-4 mb-2">
        <label for="runModeFilter" class="mr-2">Run mode</label>
        <select id="runModeFilter" class="form-control form-control-sm">
            <option value="">All</option>
            @foreach(\App\Constants\JobRunMode::names as $id => $name)
                <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>
    @foreach(\App\Constants\JobRunMode::names as $id => $name)
        <div class="small text-muted mr-4 mb-2">
            <span class="badge {{ \App\Constants\JobRunMode::getBadgeClass($id) }} p-1">
                <i class="{{ \App\Constants\JobRunMode::getIcon($id) }} mr-1"></i>{{ $name }}
            </span>
            {{ \App\Constants\JobRunMode::getDescription($id) }}
        </div>
    @endforeach
</div>
