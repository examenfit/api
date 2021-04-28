@if ($image_width >= 200)
  <div class=large-attachment style="width: {{ $image_width }}pt">
    <b>{{ $name }}</b>
    <img src="{{ Storage::url($path) }}" style="width: {{ $image_width }}pt">
  </div>
@endif
