@extends('errors::minimal')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', __('Page Expired'))
@section('button')
	<a href="{{route('login')}}" class="text-gray-700" style="display: block; background-color: #2f3897;
    padding: 8px; border-radius: 5px; color: white;">Пожалуйста, авторизуйтесь</a>
@endsection
