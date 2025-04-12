@extends('adminlte::page')

@section('content_header')
    <h1>{{ isset($subcategory) ? 'Edit Category' : 'Add Category' }}</h1>
@stop

@section('content')
    <form action="{{ isset($subcategory) ? route('categories_sub.edit', ['id' => $subcategory['id']]) : route('categories_sub.store') }}" method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input @error('status') is-invalid @enderror"
                               @if((isset($subcategory) && $subcategory->status === 'active') || old('status') == 'on') checked
                               @endif name="status"
                               id="status">
                        <label class="form-check-label" for="status">Status</label>
                        @error('status')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Subcategory Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter Subcategory Name" value="{{ old('name', $subcategory['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Code</label>
                            <input type="text" class="form-control @error('ccode') is-invalid @enderror" id="code"
                                   name="code"
                                   placeholder="Enter Code" value="{{ old('code', $subcategory['code'] ?? '') }}">
                            @if ($errors->has('code'))
                                <span class="text-danger">{{ $errors->first('code') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Order</label>
                            <input type="text" class="form-control @error('order') is-invalid @enderror" id="order"
                                   name="order"
                                   placeholder="Enter Order" value="{{ old('order', $subcategory['order'] ?? '') }}">
                            @if ($errors->has('order'))
                                <span class="text-danger">{{ $errors->first('order') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select class="form-control @error('category_id') is-invalid @enderror" id="category_id"
                                    name="category_id">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                            @if(isset($subcategory) && $subcategory['category_id'] == $category->id) selected @endif>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('category_id'))
                                <span class="text-danger">{{ $errors->first('category_id') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                @if(!isset($subcategory))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
