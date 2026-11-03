@extends('layouts.error')
@section('codigo', '403')
@section('titulo', 'Esta función está restringida')
@section('mensaje')
{{ isset($exception) && $exception->getMessage() !== '' ? $exception->getMessage() : 'Tu cuenta no tiene permiso para realizar esta acción.' }}
@endsection
