@extends('errors.minimal')

@section('title', 'Acceso denegado')
@section('code', '403')
@section('emoji', '🔒')
@section('accent', 'bc3')
@section('message', $exception->getMessage() ?: 'No tienes permiso para acceder a este recurso. Contacta al administrador si crees que es un error.')
