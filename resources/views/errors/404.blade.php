@extends('errors::minimal')

@section('title', __('Страница не найдена.'))
@section('code', '404')
@section('message', $exception->getMessage() ?: 'Страница не найдена.')
