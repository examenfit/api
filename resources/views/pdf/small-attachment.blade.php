@if ($attachment['image_width'] < 200)
  <div class=small-attachment>
    <b>{{ $attachment['name'] }}</b>
    <img src="{{ $attachment['url'] }}">
  </div>
@endif
