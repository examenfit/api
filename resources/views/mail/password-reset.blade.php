<!doctype html>
<p>
  Beste {{ $user->first_name }} {{ $user->last_name }},<br>
  Klik op onderstaande link om je wachtwoord opnieuw in te stellen.
<p>
  <a href="{{ $link }}">Wachtwoord opnieuw instellen</a>
<p>
  Heb je geen wachtwoord reset aangevraagd? Dan is je wachtwoord ongewijzigd en kan je nog steeds inloggen.
