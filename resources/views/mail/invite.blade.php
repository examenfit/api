<!doctype html>
<p>
  Beste {{ $seat->first_name }} {{ $seat->last_name }},<br>
  {{ $user->first_name }} {{ $user->last_name }} heeft je uitgenodigd om examenfit.nl te gaan gebruiken.
<p>
  Klik op onderstaande link om je account te activeren.
<p>
  <!-- fixme -->
  <a href="{{ $link }}">{{ $link }}</a>
