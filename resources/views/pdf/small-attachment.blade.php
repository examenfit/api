@if ($image_width < 200)
  <div class=small-attachment>
    <b>{{ $name }}</b>
    <img src="{{ Storage::url($path) }}">
  </div>
@endif
