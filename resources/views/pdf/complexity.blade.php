&nbsp;
<span>
  @include('pdf.meter')
</span>
&nbsp;
<span>
@if ($complexity == 'low')
  laag
@elseif ($complexity == 'high')
  hoog
@elseif ($complexity == 'average')
  gemiddeld
@else
  onbekend
@endif
</span>
