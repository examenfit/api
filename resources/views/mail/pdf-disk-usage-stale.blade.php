<p>De PDF-server meldt zijn schijfgebruik niet meer.
@if ($lastMeasuredAt)
De laatste meting is van {{ $lastMeasuredAt->format('Y-m-d H:i') }}.
@else
Er is nog nooit een meting ontvangen.
@endif
</p>
<p>Mogelijke oorzaken: de cronjob op de PDF-server draait niet meer, het token (PDF_DISK_USAGE_TOKEN) klopt niet meer, de api was niet bereikbaar, of ~/.disk-usage.env ontbreekt op de PDF-server.</p>
<p>Kijk in het cron-log op de PDF-server. Zolang er geen metingen binnenkomen komt er ook geen mail als de schijf vol raakt.</p>
