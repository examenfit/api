<div class=topic>
  @include('pdf.topic-header')  
  @include('pdf.topic-intro')  
  @foreach($topic['questions'] as $question)
    @include('pdf.question')
  @endforeach
  @include('pdf.topic-footer')  
</div>
