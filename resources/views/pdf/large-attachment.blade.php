@if ($attachment['image_width'] >= 200)
  <div class=large-attachment>
    <b>{{ $attachment['name'] }}</b>
    <img src="{{ $attachment['url'] }}">
  </div>
@endif
