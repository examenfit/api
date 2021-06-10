<div class=action>
  @if ($has_answers)
    <div class=action-qr-code>
      @include('pdf.qr-code')
    </div>
    <div class=action-info>
      Gebruik de QR-code om na te kijken of om tips te krijgen
    </div>
  @else
    <br/>
  @endif
</div>
