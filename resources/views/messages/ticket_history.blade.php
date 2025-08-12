@if(count($ticket_list ?? []) > 0)
    <tbody>
    @foreach($ticket_list as $ticket)
        <tr>
            <td>{{ $ticket->ticket_date }}</td>
            <td><a href="{{ route('message.detail',$ticket->id) }}" class="text-blue">{{ $ticket->ticket_code }}</a></td>
            <td>{{ $ticket->subject }}</td>
        </tr>
    @endforeach
    </tbody>
@endif