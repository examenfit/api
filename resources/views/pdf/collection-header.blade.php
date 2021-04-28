<div class=collection-header>
  @include('pdf.examenfit-branding')
  <div class=collection-info>
    <div class=collection-name>
      {{ $name ?? '' }}
    </div>
    <div class=collection-meta>
      <div class=question-count>
        {{ count($questions) }} vragen
      </div>
      <div class=points> 
        @include('pdf.points')
      </div>
      <div class=duration>
        @include('pdf.time_in_minutes')
      </div>
    </div>
    <div class=collection-download>
      gedownload {{ date("Y-m-d H:i:s") }}
    </div>
  </div>
</div>
