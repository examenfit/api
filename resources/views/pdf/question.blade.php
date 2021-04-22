<div class=question>
  <div class=question-intro>
    <p>
      {!! $question['introduction'] !!}
    </p>
    @foreach($question['attachments'] as $attachment)
      @include('pdf.large-attachment')
    @endforeach
  </div>
  <div class=question-header>
    <div class=question-title>
      Vraag {{ $question['number'] }}
    </div>
    <div class=points>@include('pdf.points', $question)</div>
    <div class=duration>@include('pdf.time_in_minutes', $question)</div>
    <div class=complexity>@include('pdf.complexity', $question)</div>
  </div>
  <div class=question-text>
    <p>
      {!! $question['introduction'] !!}
    </p>
  </div>
  <div class=action>
    <div class=action-qr-code>
      @include('pdf.qr-code', $question)
    </div>
    <div class=action-info>
      Gebruik de QR-code om na te kijken<br>
      of om tips te krijgen
    </div>
  </div>
</div>
