{{--
    Run Mode cell for the Saved Jobs / Cron Jobs lists.
    $runMode: ['mode' => JobRunMode::*, 'store_types' => [...]] from StoreScopedJobRunner::runMode().
--}}
@php
    $mode = $runMode['mode'] ?? \App\Constants\JobRunMode::SINGLE_RUN;
    $isStorePicker = $mode === \App\Constants\JobRunMode::STORE_PICKER;
    $storeTypes = collect($runMode['store_types'] ?? [])->map(fn ($type) => \Illuminate\Support\Str::headline($type))->implode(', ');
    $storeTypesLabel = $storeTypes !== '' ? $storeTypes . ' stores' : 'All store types';
@endphp
<td class="text-nowrap" data-search="{{ \App\Constants\JobRunMode::getName($mode) }}{{ $isStorePicker ? ' ' . $storeTypesLabel : '' }}" data-order="{{ $mode }}">
    <span class="badge {{ \App\Constants\JobRunMode::getBadgeClass($mode) }} p-2"
          data-toggle="tooltip" data-placement="top"
          title="{{ \App\Constants\JobRunMode::getDescription($mode) }}">
        <i class="{{ \App\Constants\JobRunMode::getIcon($mode) }} mr-1"></i>{{ \App\Constants\JobRunMode::getName($mode) }}
    </span>
    @if($isStorePicker)
        <div class="small text-muted mt-1">{{ $storeTypesLabel }}</div>
    @endif
</td>
