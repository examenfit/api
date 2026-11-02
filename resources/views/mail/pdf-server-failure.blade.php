<p>Een gebruiker kon geen PDF of uitwerkbijlage laten aanmaken: een opdracht naar de PDF-server mislukte ({{ config('app.url') }}, {{ now()->format('d-m-Y H:i') }}).</p>
<p>Opdracht: <code>{{ $command }}</code><br>
Afsluitcode: {{ $status ?? 'geen (de opdracht startte niet)' }}</p>
<p>Uitvoer:</p>
<pre>{{ $output !== '' ? $output : '(geen uitvoer)' }}</pre>
<p>Gebruikelijke oorzaken: de PDF-server is niet bereikbaar of herstart, de ssh-sleutel of de host key van de server is veranderd, of de schijf is vol (zie Admin &rarr; Overzichten &rarr; Schijfruimte PDF-generator).</p>
<p>Je krijgt hooguit eens per {{ config('pdf_generator.failure_alert_interval_hours') }} uur zo'n mail; de log (RemoteScripts) bevat elke fout.</p>
