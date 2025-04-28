<table class="table table-bordered">
    <thead>
    <tr>
        <th>Issue</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    @foreach($issues as $issue)
        <tr>
            <td>{{ $issue }}</td>
            <td>
                <a href="/catalog/edit/{{ $product->product_id }}" target="_blank"
                   class="btn btn-primary btn-sm">Resolve</a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
