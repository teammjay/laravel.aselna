{{--
    "Run Now" for the Saved Jobs / Cron Jobs lists. A store picker job opens the store popup
    (jobs.run_stores_modal); a single run job posts straight to $runUrl.
    $runMode, $runUrl, $storesUrl, $jobName, $buttonClass (size / spacing classes).
--}}
@php
    $mode = $runMode['mode'] ?? \App\Constants\JobRunMode::SINGLE_RUN;
@endphp
@if($mode === \App\Constants\JobRunMode::STORE_PICKER)
    <button type="button" class="btn {{ \App\Constants\JobRunMode::getButtonClass($mode) }} {{ $buttonClass ?? '' }} js-run-stores text-nowrap"
            data-run-url="{{ $runUrl }}"
            data-stores-url="{{ $storesUrl }}"
            data-job-name="{{ $jobName }}"
            title="Choose stores, then run">
        <i class="{{ \App\Constants\JobRunMode::getIcon($mode) }} mr-1"></i>Run Now...
    </button>
@else
    <form method="POST" action="{{ $runUrl }}">
        @csrf
        <button type="submit" class="btn {{ \App\Constants\JobRunMode::getButtonClass($mode) }} {{ $buttonClass ?? '' }} text-nowrap"
                title="Run now as configured">
            <i class="{{ \App\Constants\JobRunMode::getIcon($mode) }} mr-1"></i>Run Now
        </button>
    </form>
@endif
