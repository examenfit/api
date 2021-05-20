@extends('registration.layout')
@section('main')
  <section class=failure>
    <p>
  <details>
    <summary>Er is een fout opgetreden...</summary>
    <p>{{ $message }}</p>
  </details>
    <p>Probeer het later nog eens.
  </section>
  <script>
    function onTimeout() {
      history.go(-1)
    }
    setTimeout(onTimeout, 10000)
  </script>
@stop
