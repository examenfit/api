:root {
  --text-size: 10pt;
  --title-size: 12pt;
  --collection-name-size: 18pt;
  --icon-align: -1.2pt;
}
* {
  font: inherit;
  margin: 0;
  padding: 0;
}
@page {
  size: A4 portrait;
  margin: 0.5in 0in;
}
body {
  font-family: Helvetica, Arial, sans-serif;
  font-size: 0;
}
p {
  margin: 7pt 0;
}
b {
  font-weight: bold;
}
i {
  font-style: italic;
}
.collection {
  padding: 0 1in;
}
.collection-header {
  border-bottom: solid 8pt gray;
  margin-bottom: 28pt;
}
.collection-header > * {
  display: inline-block;
}
.collection-info {
  width: 66%;
}
.examenfit-branding {
  width: 34%;
}
.collection-header .examenfit-branding {
  text-align: right;
}
.examenfit-logo > * {
  width: 122pt;
  margin-bottom: 7pt;
}
.examenfit-punchline {
  font-size: 7pt;
  font-weight: 400;
  color: #999;
  margin-bottom: 14pt;
}
.collection-info {
}
.collection-name {
  font-size: var(--collection-name-size);
  margin-bottom: 14pt;
}
.collection-meta {
  font-size: var(--text-size);
  margin-bottom: 7pt;
}
.collection-meta > * {
  display: inline;
  font-weight: 400;
}
.collection-meta > *:not(:first-child):before {
  content: " | ";
  font-weight: 100;
}
.collection-download {
  font-size: 8pt;
  font-style: italic;
  font-weight: 100;
}
.collection-footer {
  border-top: solid 1pt silver;
  padding-top: 7pt;
}
.collection-footer > * {
  display: inline-block;
  vertical-align: top;
}
.examenfit-info {
  width: 66%;
  font-size: 9pt;
  text-align: right;
  padding-top: 3pt;
}
.formuleblad-title {
  font-size: var(--title-size);
  font-weight: bold;
}
.formuleblad-header {
  padding-bottom: 7pt;
  border-bottom: solid 1pt black;
  margin-bottom: 21pt;
}
.formuleblad-content {
  clear: both;
  font-size: var(--text-size);
  line-height: 15pt;
  margin-bottom: 28pt;
}
.topic-header {
  padding-bottom: 7pt;
  border-bottom: solid 1pt black;
  margin-bottom: 21pt;
  text-align: right;
}
.topic-title {
  font-size: var(--title-size);
  font-weight: bold;
  float: left;
}
.topic-meta {
  display: inline-block;
  font-size: var(--text-size);
  text-align: right;
}
.topic-meta > * {
  display: inline-block;
  border: solid 1pt silver;
  padding: 3pt 7pt;
}
.topic-intro {
  clear: both;
  font-size: var(--text-size);
  line-height: 15pt;
}
.small-attachment {
  float: right;
  width: 2in;
  margin-left: 14pt;
  margin-bottom: 7pt;
}
.small-attachment * {
  width: 2in;
}
.large-attachment {
  clear: both;
  width: 6in;
  margin: 21pt 0;
}
.attachment {
  page-break-inside: avoid;
  margin: 21pt 0;
}
.attachment b {
  display: block;
}
.attachment div {
  display: block;
}
.attachment img {
  max-width: 6in;
  max-height: 4in;
  object-fit: contain;
}
.question {
  margin-bottom: 28pt;
}
.question-intro {
  clear: both;
  font-size: var(--text-size);
  line-height: 15pt;
}
.question-figure {
  clear: both;
  text-align: center;
}
.question-figure img {
  width: 5in;
}
.question-header {
  clear: both;
  margin-top: 14pt;
  margin-bottom: 7pt;
  text-align: right;
}
.question-header > * {
  display: inline;
  font-size: var(--text-size);
}
.question-title {
  font-size: var(--text-size);
  font-weight: bold;
  margin-bottom: 3pt;
  float: left;
}
.question-header .points {
  float: left;
  padding-left: 7pt;
}
.complexity:before {
  content: " | ";
  font-weight: 100;
}
.question-text {
  font-size: var(--text-size);
  border: solid 1pt silver;
  margin-top: 14pt;
  margin-bottom: 14pt;
  padding: 7pt 26pt;
  line-height: 15pt;
}
.action {
  clear: both;
  margin-top: 11pt;
}
.action > * {
  display: inline-block;
}
.action-qr-code > * {
  width: 2cm;
  height: 2cm;
}
.action-info {
  font-size: var(--text-size);
  -font-weight: bold;
  padding: 24pt;
  vertical-align: top;
}
svg {
  min-height: 2ex;
}
.katex-mathml {
  display: none;
}

.topic ~ .topic {
  page-break-before: always;
}
.question,
.question-main {
  page-break-inside: avoid;
}
.icon {
  display: inline-block;
  width: var(--text-size);
  height: var(--text-size);
  vertical-align: baseline;
  margin-bottom: var(--icon-align);
}
.appendixes {
  padding: 0 1in;
  page-break-before: always;
}
.appendixes-header {
  font-size: 14pt;
  font-weight: bold;
  border-bottom: 4pt solid black;
  padding: 4pt 0;
  margin-bottom: 14pt;
}
.appendix {
  page-break-inside: avoid;
  margin: 7pt 0;
}
.appendix-name {
  font-size: var(--text-size);
}
.appendix-image {
  text-align: center; /* aligns images to center as well */
}
a {
  color: inherit;
  font-weight: bold;
  text-decoration: inherit;
}
