
<table class="table table-bordered">
    <thead>
    <tr>
        <th>Issue</th>
        <th>Field</th>
        <th>Value</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    @foreach($issues as $issue)
        <tr>
            <td style="white-space: normal;">{{ $issue['title'] }}</td>
            <td>
                @if(isset($issue['spec']['field_id']))
                    <select name="specification[]" class="form-control specification">
                        <option value="{{ $issue['spec']['field'] }}">{{ $issue['spec']['field'] }}</option>
                    </select>
                @endif
            </td>
            <td>
                @if(isset($issue['spec']['field_id']))
                    @if(isset($issue['spec']['field_type']) && $issue['spec']['field_type'] == 'select')

                        <select class="form-control specification-select specification_value" name="specification_value[]">
                            <option value="">----</option>
                            @if(count($issue['spec']['values'] ?? []) > 0)
                                @foreach($issue['spec']['values'] as $vopt)
                                    <option value="{{ trim($vopt->id) }}">{{ trim($vopt->field_value) }}</option>
                                @endforeach
                            @endif
                        </select>
                    @else
                        <textarea class="form-control specification_value" name="specification_value[]"></textarea>
                    @endif
                @endif
            </td>

            <td>
                @if(!isset($issue['spec']['field_id']))
                    <a href="/catalog/edit/{{ $product->product_id }}" target="_blank"
                       class="btn btn-primary btn-sm">Resolve</a>
                @endif
                @if( isset($issue['spec']['field_type']) && isset($issue['spec']['spec_id']) && $issue['spec']['field_type'] == 'select')
                        <a href="/specification_values/edit/{{ $issue['spec']['spec_id'] }}" target="_blank"
                           class="btn btn-primary btn-sm">Add Value</a>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
