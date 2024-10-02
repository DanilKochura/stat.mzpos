@extends('errors::minimal')

@section('title', __('Ссылка устарела.'))
@section('message', __('Ссылка устарела.'))
@section('button')
	<a href="{{route('login')}}" class="text-gray-700" style="display: block; background-color: #2f3897;
    padding: 8px; border-radius: 5px; color: white;">На главную</a>
@endsection
