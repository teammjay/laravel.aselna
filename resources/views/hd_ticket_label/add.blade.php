@extends('adminlte::page')

{{--@section('title', '')--}}

@section('content_header')
    <h1>{{ isset($hd_ticket_label) ? 'Edit HD Ticket Label' : 'Add HD Ticket Label' }}</h1>
@stop

@section('adminlte_css')
    <style>
        .stores li {
            display: inline-block;
            width: 49%;
            margin-bottom: 10px;
        }

    </style>
@stop

@section('content')
    <form
        action="{{ isset($hd_ticket_label) ? route('hd_ticket_label.update',['id' => $hd_ticket_label['id']]) : route('hd_ticket_label.store') }}"
        method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">

                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                   name="name"
                                   placeholder="Enter template name" value="{{ old('name', $hd_ticket_label['name'] ?? '') }}">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="parent_id">Parent</label>
                            <select class="form-control" name="parent_id" id="parent_id">
                                <option value=""></option>
                                @if(count($ticket_labels ?? []) > 0)
                                    @foreach($ticket_labels as $lable)
                                        <option value="{{ $lable->id }}" {{ (isset($hd_ticket_label['parent_id']) && $lable->id == $hd_ticket_label['parent_id']) ? 'selected' : ''}}>{{ $stype->name }}</option>
                                    @endforeach
                                @endif

                            </select>
                        </div>





                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ route('hd_ticket_label.index') }}" class="btn btn-secondary">Cancel</a>
                @if(!isset($hd_ticket_label))
                    <button type="reset" class="btn btn-primary">Reset</button>
                @endif
            </div>
        </div>
    </form>
@stop
