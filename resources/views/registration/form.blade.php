@extends('registration.layout')
@section('main')
<form method=post>

  @include('registration.google-sso')
  @include('registration.office365-sso')

  <p class=separator>of</p>

  @csrf

  <label>
    <input name=first_name type=text required maxlength=255 placeholder="Voornaam*">
    @error('first_name')
      <sub>Voornaam vereist.</sub>
    @enderror
  </label>

  <label>
    <input name=last_name type=text required maxlength=255 placeholder="Achternaam*">
    @error('last_name')
      <sub>Achternaam vereist.</sub>
    @enderror
  </label>

  <label>
    <input name=email type=email required placeholder="Emailadres*">
    @error('email')
      <sub>Geldig emailadres vereist.</sub>
    @enderror
  </label>

  <label>
    <input name=consent type=checkbox required>
    <span>Ik ga akkoord met de <a target=_blank href="https://examenfit.nl/algemene-voorwaarden">algemene voorwaarden</a>.*</span>
  </label>

  <label>
    <input onchange="this.form.newsletter.value=+this.matches(':checked')" type=checkbox>
    <span>Ik wil graag eens per maand een nieuwsbrief ontvangen.</span>
  </label>

  <input name=newsletter type=hidden value="0">
  <input name=license type=hidden value="trial">
  <input type=submit value="Versturen">

  <p>Je ontvangt op het door jou opgegeven emailadres een link om je account te activeren.</p>

</form>
@if($errors->any())
  <!-- $errors = {{ json_encode($errors) }} -->
@endif
@stop
