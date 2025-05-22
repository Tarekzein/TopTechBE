@extends('store::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('store.name') !!}</p>
@endsection
