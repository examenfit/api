@if ($use_introduction || $use_attachments)
<div class=question-intro>
  @if ($use_introduction)
  <p>
    {!! $introduction !!}
  </p>
  @endif
  @if ($use_attachments)
    @foreach($attachments as $attachment)
      {{-- @include('pdf.small-attachment', $attachment) --}}
      {{-- @include('pdf.large-attachment', $attachment) --}}
      @include('pdf.attachment', $attachment)
    @endforeach
  @endif
</div>
@endif
