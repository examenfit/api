<!doctype html>

<p>Beste {{ $user->first_name }} {{ $user->last_name }},</p>

<p>Klik op deze link om je wachtwoord opnieuw in te stellen:</p>

<p><a href="{{ $link }}">Wachtwoord opnieuw instellen</a><p>

<p>Heb je geen verzoek gedaan om je wachtwoord te wijzigen? Dan kun je deze email negeren. Je wachtwoord is ongewijzigd en je kan nog steeds inloggen.</p>

<p>Heb je problemen met inloggen? Mail ons je probleem op <a href="mailto:info@examenfit.nl">info@examenfit.nl</a>.</p>

<p>Met vriendelijke groet,</p>

<p>Team ExamenFit</p>
