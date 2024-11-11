@extends('adminlte::page')

@section('title', 'Add/Edit Customer')

@section('content_header')
    <h1>{{ isset($device) ? 'Edit Customer' : 'Add Customer' }}</h1>
@stop
