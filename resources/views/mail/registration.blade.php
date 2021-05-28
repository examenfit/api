<!doctype html>
<p>
  Beste {{ $registration->first_name }} {{ $registration->last_name }},<br>
  Bedankt voor je aanmelding!
<p>
  Klik op onderstaande link om je proefaccount te activeren.
<p>
  <!-- fixme -->
  <a href="{{ $link }}">{{ $link }}</a>
