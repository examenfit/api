@if ($image_width >= 200)
  <div class=large-attachment>
    <b>{{ $name }}</b>
    <img src="{{ Storage::url($path) }}">
  </div>
@endif
