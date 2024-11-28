@extends('adminlte::page')

@section('title', 'Add Interface')

@section('content_header')
    <h1>{{ isset($interface) ? 'Edit Interface' : 'Add Interface' }}</h1>
@stop

@section('content')
    <form action="{{ isset($interface) ? route('interface.update',['id' => $interface['id']]) : route('interface.store') }}"
          method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter interface name" value="{{ old('name', $interface['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">Code</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code"
                                   name="code"
                                   placeholder="Enter code" value="{{ old('code', $interface['code'] ?? '') }}">
                            @error('code')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="product_type_id">Product Type</label>
                            <select class="form-control" name="product_type_id" id="product_type_id">
                                @if(count($productTypes ?? []) > 0)
                                    @foreach($productTypes as $pType)
                                        <option value="{{ $pType->id }}" @if(isset($interface['code']) && $pType->id == $interface['product_type_id']) selected @endif>{{ $pType->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes"
                                      name="notes"
                                      placeholder="Enter Notes">{{ old('notes', $interface['notes'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($interface))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
