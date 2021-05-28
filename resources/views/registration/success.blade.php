@extends('registration.layout')
@section('main')
  <section class=success>
    <p>Beste {{ $first_name }} {{ $last_name }},
    <br>bedankt voor je aanmelding!
    <p>Je ontvangt op het door jou opgegeven emailadres een link om je account te activeren. 
  </section>
@stop
