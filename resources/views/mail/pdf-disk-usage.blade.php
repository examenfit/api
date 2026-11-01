<p>De schijf van de PDF-generator is {{ $usage->percentage }}% vol (kritiek vanaf {{ config('pdf_generator.disk_usage_critical_percentage') }}%).</p>
@if ($usage->available_mb !== null)
<p>Nog beschikbaar: {{ $usage->available_mb }} MB.</p>
@endif
<p>Als de schijf vol raakt, mislukt het maken van PDF's. Ruim de oude bestanden op de PDF-server op (losse-opgaven/, pdf/, uitwerkbijlage/).</p>
<p>Alle metingen: Admin &rarr; Overzichten &rarr; Schijfruimte PDF-generator.</p>
