<!doctype html>

<p>Bericht:</p>
<pre>{{ $feedback }}</pre>
<p>opgave: {{ $topic }}</p>
<p>vraag: {{ $question }}</p>
<p>onderdeel: {{ $part }}</p>
<p>vak/examen: {{ $stream }} {{ $exam }}</p>

@if ($email)
<p>{{ $first_name }} {{ $last_name }}, {{ $email }}</p>
@endif

@if ($collection)
<p><q>{{ $collection }}</q> van {{ $creator }}</p>
@endif
