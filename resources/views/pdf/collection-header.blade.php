<div class=collection-header>
  <div class=collection-info>
    <div class=collection-name>
      {{ $name ?? '' }}
    </div>
    <div class=collection-meta>
      <div class=topic-count>
        @include('pdf.topic-count')
      </div>
      <div class=question-count>
        @include('pdf.question-count')
      </div>
      <div class=points> 
        @include('pdf.points')
      </div>
      <div class=duration>
        @include('pdf.time_in_minutes')
      </div>
    </div>
    <div class=collection-download>
      gedownload {{ $timestamp }}
    </div>
  </div>
  @include('pdf.examenfit-branding')
</div>
