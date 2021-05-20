@extends('registration.layout')
@section('main')
<form method=post>

  @csrf

  <label>
    <input id=first_name name=first_name type=text required maxlength=255 placeholder="Voornaam">
    @error('first_name')
      <sub>Voornaam vereist.</sub>
    @enderror
  </label>

  <label>
    <input id=last_name name=last_name type=text required maxlength=255 placeholder="Achternaam">
    @error('last_name')
      <sub>Achternaam vereist.</sub>
    @enderror
  </label>

  <label>
    <input id=email name=email type=email required placeholder="Emailadres">
    @error('email')
      <sub>Geldig emailadres vereist.</sub>
    @enderror
  </label>

  <label>
    <input id=consent name=consent type=checkbox required><i></i>
    <span>Ik ga akkoord met de <a href="#">algemene voorwaarden</a>.</span>
  </label>

  <input type=hidden name=newsletter value=0>
  <label>
    <span><input id=newsletter name=newsletter type=checkbox><i></i></span>
    <span>Ik wil graag eens per maand een nieuwsbrief ontvangen.</span>
  </label>

  <input name=license type=hidden value="trial">
  <input type=submit value="Versturen">

  <p>Je ontvangt op het door jou opgegeven emailadres een link om je emailadres te bevestigen.

</form>
@stop
