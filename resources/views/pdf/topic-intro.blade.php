<div class=topic-intro>
  {{-- fixme: weird and dirty filtering on image size--}}
  @foreach($attachments as $attachment)
    @include('pdf.small-attachment', $attachment)
  @endforeach
  <p>{!! $introduction !!}</p>
  @foreach($attachments as $attachment)
    @include('pdf.large-attachment', $attachment)
  @endforeach
</div>
