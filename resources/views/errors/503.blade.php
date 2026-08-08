@extends('errors::minimal')

@section('title', translate('Service Unavailable'))
@section('code', '503')
{{-- RI.2: an exception message is arbitrary runtime text, never a translation
     key. Passing it through translate() previously made every distinct 503
     message a permanent entry in resources/lang/en/messages.php. It is now
     rendered as-is, with the translated fallback when it is empty - which is
     the usual maintenance-mode case, so the page is unchanged in practice. --}}
@section('message', $exception->getMessage() ?: translate('Service Unavailable'))
