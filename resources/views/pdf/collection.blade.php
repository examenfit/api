<div class=collection>
  @include('pdf.collection-header')
  @foreach($topics as $topic)
    @include('pdf.topic', $topic)
  @endforeach
  @include('pdf.collection-footer')
</div>
