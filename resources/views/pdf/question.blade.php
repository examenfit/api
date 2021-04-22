<div class=question>
  <div class=question-intro>
    <p>
      {!! $introduction !!}
    </p>
    @foreach($attachments as $attachment)
      @include('pdf.large-attachment', $attachment)
    @endforeach
  </div>
  <div class=question-main>
    <div class=question-header>
      <div class=question-title>
        Vraag {{ $number }}
      </div>
      <div class=points>@include('pdf.points')</div>
      <div class=duration>@include('pdf.time_in_minutes')</div>
      <div class=complexity>@include('pdf.complexity')</div>
    </div>
    <div class=question-text>
      <p>
        {!! $text !!}
      </p>
    </div>
    <div class=action>
      <div class=action-qr-code>
        @include('pdf.qr-code')
      </div>
      <div class=action-info>
        Gebruik de QR-code om na te kijken<br>
        of om tips te krijgen
      </div>
    </div>
  </div>
</div>
