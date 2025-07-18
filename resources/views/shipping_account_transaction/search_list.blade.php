@if(count($transactions ?? []) > 0)
    @foreach($transactions as $ship_transaction)
        <tr data-id="{{ $ship_transaction->id }}">
            <td>{{ $ship_transaction->shippingAccount->name }} <input type="checkbox" name="transaction_id[]" style="visibility: hidden;" data-id="{{ $ship_transaction->id }}" value="{{ $ship_transaction->transaction_id }}"></td>
            <td>{{ $ship_transaction->tracking_number }}</td>
            <td>{{ $ship_transaction->transaction_date }}</td>
            <td>{{ $ship_transaction->transaction_id }}</td>
            <td>{{ $ship_transaction->amount }}</td>
            <td>{{ $ship_transaction->address }}</td>
            <td><input type="checkbox" name="canceled" @if($ship_transaction->canceled == 1) checked @endif /></td>
            <td>{{ $ship_transaction->canceled_date }}</td>
            <td>{{ $ship_transaction->created_at }}</td>
        </tr>
    @endforeach
@else
    <tr class="text-center"><td colspan="9"> No transaction found </td></tr>
@endif