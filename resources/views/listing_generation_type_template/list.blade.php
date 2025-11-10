@extends('adminlte::page')

@section('title', 'Listing Generation Type Template')

@section('content_header')
    <h1>Listing Generation Type Template</h1>
@stop

@section('adminlte_css')
    <style>
        .acitve-text{
            color:green;
        }
        .inacitve-text{
            color:red;
        }

    </style>
@stop

@section('content')
    <div class="row my-3">
        <div class="col-md-3">
            <a href="{{ route('listing_generation_type_template.create') }}" class="btn btn-primary">
                Add Listing Generation Type Template
            </a>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-body">
            <table id="generationTypeTemplateTable" class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Default Template</th>
                    <th>Used in Store</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($listing_generation_type_templates as $ltype)
                    @php
                        /*$storeUsed = '';

                        if(isset($ltype['used_by_store']) && strstr($ltype['used_by_store'], ',') !== false){
                            $stores = explode(',',$ltype['used_by_store']);
                        }else{
                            $stores = (array) $ltype['used_by_store'];
                        }

                        if(count($store_types ?? []) > 0){
                            $storeName = array();
                            foreach($store_types as $stype){
                                if((in_array($stype->id,$stores))){
                                    $storeName[] = $stype->name;
                                }
                            }
                            $storeUsed = implode('/',$storeName);
                        }*/

                    @endphp
                    <tr>
                        <td>{{ $ltype->id }}</td>
                        <td>{{ $ltype->template }}</td>
                        <td>{{ $ltype->listingType->name }}</td>
                        <td>{{ ($ltype->default_template == 1) ? 'Yes' : '' }}</td>
                        <td>{{ $ltype->used_by_store }}</td>
                        <td>
                            @if($ltype->status == 1)
                                <span class="acitve-text">Active</span>
                            @else
                                <span class="inacitve-text">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $ltype->created_by  }}</td>
                        <td>{{ $ltype->created_at  }}</td>
                        <td>
                            <a href="{{ route('listing_generation_type_template.edit', $ltype->id) }}" class="btn btn-primary" title="Edit">
                                <i class="fas fa-fw fa-pencil-alt"></i>
                            </a>
                            <a href="{{ route('listing_generation_type_template.delete', $ltype->id) }}" class="btn btn-danger" title="Remove">
                                <i class="fas fa-fw fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('adminlte_js')
    <script>
        $(document).ready(function () {
            $('#generationTypeTemplateTable').DataTable({
                "stateSave": true,
                stateSaveCallback: function(settings, data) {
                    sessionStorage.setItem(
                        'DataTables_' + settings.sInstance,
                        JSON.stringify(data)
                    );
                },

                stateLoadCallback: function(settings) {
                    return JSON.parse(
                        sessionStorage.getItem('DataTables_' + settings.sInstance)
                    );
                }
            });
        });
    </script>
@stop