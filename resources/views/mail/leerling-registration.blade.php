<!doctype html>
<p>
  Beste {{ $registration->first_name }} {{ $registration->last_name }},
</p>

<p>Je hebt je aangemeld voor een leerlinglicentie. Hiermee kun je:</p>
<ul>
  <li>Examenstof oefenen</li>
  <li>...</li>
</ul>

<p>
  Klik op deze link om je proeflicentie te activeren.<br>
  <!-- fixme -->
  <a href="{{ $link }}">{{ $link }}</a>
</p>

<p>Veel succes!</p>

<p>Met vriendelijke groet,</p>

<p>Het team van ExamenFit</p>
