<div class=question-intro>
  <p>
    {!! $introduction !!}
  </p>
  @foreach($attachments as $attachment)
    @include('pdf.large-attachment', $attachment)
  @endforeach
</div>
