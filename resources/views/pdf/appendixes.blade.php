@if (empty($appendixes))
  <!-- geen uitwerkbijlagen -->
@else
  <div class=appendixes>  
    @include('pdf.appendixes-header')
    @foreach($appendixes as $appendix)
      @include('pdf.appendix', $appendix)
    @endforeach
    @include('pdf.appendixes-footer')
  </div>
@endif
