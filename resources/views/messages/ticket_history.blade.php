@if(count($ticket_list ?? []) > 0)
    <tbody>
    @foreach($ticket_list as $ticket)
        <tr>
            <td>{{ $ticket->ticket_date }}</td>
            <td>{{ $ticket->ticket_code }}</td>
            <td>{{ $ticket->subject }}</td>
        </tr>
    @endforeach
    </tbody>
@endif