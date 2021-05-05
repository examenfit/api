@if ($use_text || $use_introduction || $use_attachments)
<div class=question>
  @include('pdf.question-intro')
  @if ($use_text)
    <div class=question-main>
      @include('pdf.question-header')
      @include('pdf.question-text')
      @include('pdf.question-action')
    </div>
  @endif
</div>
@endif
