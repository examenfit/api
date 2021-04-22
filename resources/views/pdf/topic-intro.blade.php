<div class=topic-intro>
  {{-- fixme: weird and dirty filtering on image size--}}
  @foreach($topic['attachments'] as $attachment)
    @include('pdf.small-attachment', [ 'attachment' => $attachment ])
  @endforeach
  <p>{!! $topic['introduction'] !!}</p>
  @foreach($topic['attachments'] as $attachment)
    @include('pdf.large-attachment', [ 'attachment' => $attachment ])
  @endforeach
</div>
