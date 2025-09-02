@if(count($users ?? []) > 0)
    @foreach($users as $user)
        
        <tr id="user-{{ $user->id }}">

            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->created_at }}</td>
            <td class="d-flex" style="gap:10px;">
                <a href="{{ route('user.edit', $user->id) }}" class="btn btn-primary" title="Edit">
                    <i class="fas fa-fw fa-pencil-alt"></i>
                </a>
                <button class="btn btn-danger delete-btn" data-id="{{ $user->id }}" title="Remove"> <i class="fas fa-fw fa-trash"></i></button>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="10" align="center"> No users found </td>
    </tr>
@endif
