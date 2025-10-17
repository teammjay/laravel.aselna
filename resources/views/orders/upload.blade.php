@extends('adminlte::page')

@section('title', 'Import Amazon Order')

@section('content_header')
    <h1>Import Amazon Order</h1>
@stop

@section('adminlte_css_pre')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('content')

        @csrf
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="row">

                            <div class="col-md-12">
                                <!-- resources/views/upload.blade.php -->
                                <form action="{{ route('order.upload') }}" id="uploadForm" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label for="store_id">Store</label>
                                        <select class="form-control @error('store_id') is-invalid @enderror" name="store_id" id="store_id" required>
                                            <option value="">--</option>
                                            @if(count($stores ?? []) > 0)
                                                @foreach($stores as $store)
                                                    @if(isset($store->storeType) && $store->storeType->code == 'amazon')
                                                    <option value="{{ $store->id }}">{{ $store->name }} {{ (!empty((array)$store->storeType)) ? ' - '.$store->storeType->name : '' }}</option>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </select>
                                        @if ($errors->has('store_id'))
                                            <span class="text-danger">{{ $errors->first('store_id') }}</span>
                                        @endif
                                    </div>
                                    <div class="form-group">
                                        <input type="file" name="csv_file" required>
                                    </div>
                                    <button type="submit" id="import" class="btn btn-primary">Import</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

@stop
@section('adminlte_js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#store_id').select2();

            const form = document.getElementById('uploadForm');
            const button = document.getElementById('import');

            form.addEventListener('submit', function(e) {
                button.disabled = true; // Disable button
                button.innerText = "Importing..."; // Optional: indicate progress
            });

        });
    </script>
@stop
