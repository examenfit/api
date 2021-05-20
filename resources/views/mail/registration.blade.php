<!doctype html>
<p>
  Beste {{ $registration->first_name }} {{ $registration->last_name }},<br>
  Bedankt voor je aanmelding!
<p>
  <a href="#">{{ $registration->activation_code }}</a>
