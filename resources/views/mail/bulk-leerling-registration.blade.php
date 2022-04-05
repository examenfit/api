<!doctype html>
<p>[TODO]</p>
<p>
  Beste {{ $registration->first_name }} {{ $registration->last_name }},
</p>

<p>Leuk dat je ExamenFit gaat gerbuiken!</p>

<p>
  Klik op deze link om je licentie te activeren.<br>
  <!-- fixme -->
  <a href="{{ $link }}">{{ $link }}</a>
</p>

<p>
  Met je licentie kun je:
</p>
<ul>
  <li>Examenvragen oefenen bij alle examenonderwerpen</li>
  <li>Examenvragen oefenen bij de hoofdstukken van je lesboek</li>
  <li>Zien aan welke onderwerpen je nog moet wekren</li>
</ul>

<p>Veel succes!</p>

<p>Met vriendelijke groet,</p>

<p>Het team van ExamenFit</p>
