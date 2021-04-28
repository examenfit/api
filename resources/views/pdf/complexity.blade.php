@if ($complexity == 'low')
  complexiteit: laag
@elseif ($complexity == 'high')
  complexiteit: hoog
@elseif ($complexity == 'average')
  complexiteit: gemiddeld
@else
  complexiteit: {{ $complexity }}
@endif
