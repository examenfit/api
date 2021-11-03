<!doctype html>
<p>Beste {{ $seat->first_name }} {{ $seat->last_name }},</p>

<p>Je docent {{ $user->first_name }} {{ $user->last_name }} heeft je uitgenodigd om ExamenFit te gaan gebruiken. Met ExamenFit oefen je examenvragen op de manier die bij jou past:</p>

<ul>
    <li>Je werkt je opgaven gewoon uit op papier</li>
    <li>Je kijkt jezelf digitaal na en je scores worden opgeslagen</li>
    <li>Kom je er niet uit, dan krijg je stapsgewijze tips en uitwerkingen</li>
    <li>Je kunt oefenen bij de hoofdstukken van je lesboek</li>
    <li>Je ziet per onderwerp hoe je er voor staat</li>
</ul>

<p>
    Klik op deze link om je account te activeren:<br>
    <a href="{{ $link }}">{{ $link }}</a>
</p>

<p>Veel succes!</p>

<p>Met vriendelijke groet,</p>

<p>Het team van ExamenFit</p>

