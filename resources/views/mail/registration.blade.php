<!doctype html>
<p>
  Beste {{ $registration->first_name }} {{ $registration->last_name }},
</p>

<p>
    Leuk dat je benieuwd bent naar ExamenFit. Hopelijk ga je de meerwaarde van onze examentool voor maatwerk waarderen!
</p>

<p>Je hebt je aangemeld voor een proeflicentie. Hiermee kun je:</p>
<ul>
    <li>Drie weken gratis ExamenFit uitproberen, zonder verplichtingen</li>
    <li>Al je leerlingen zonder licentie laten oefenen, met jouw selectie van vragen</li>
    <li>Voor drie leerlingen proeflicenties aanmaken, om zelfstandig te oefenen</li>
</ul>

<p>
    Klik op deze link om je proeflicentie te activeren.<br>
  <!-- fixme -->
  <a href="{{ $link }}">{{ $link }}</a>
</p>

<p>Veel succes!</p>

<p>Met vriendelijke groet,</p>

<p>Het team van ExamenFit</p>
