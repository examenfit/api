<div class=collection>
  @include('pdf.collection-header')
  @foreach($collection->topics as $topic)
    @include('pdf.topic')
  @endforeach
  @include('pdf.collection-footer')
</div>
