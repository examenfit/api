<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class DocumentMarkup {

  const BOLD = '/\*\*(.+?)\*\*/m';
  const LATEX = '/(`\$\$(.+?)\$\$`)/m'; // without parentheses the backticks fuck stuff up!?
  const LINEBREAK = '/\n/m';

  public function fixLineBreaks($text) {
    $text = preg_replace_callback(DocumentMarkup::LINEBREAK, function($m) {
      return '<br>';
    }, $text);
    return $text;
  }

  public function strToBoldHtml($str) {
      return "<b>{$str}</b>";
  }

  public function fixBold($text) {
    $text = preg_replace_callback(DocumentMarkup::BOLD, function($m) {
      return $this->strToBoldHtml($m[1]);
    }, $text);
    return $text;
  }

  // @see https://www.php.net/manual/en/function.escapeshellcmd.php
  function latexToMathML($formula)
  {
    $katex = base_path('/node_modules/katex/cli.js');

    $cmd = str_replace(array('\\', '%'), array('\\\\', '%%'), $formula);
    $cmd = escapeshellarg($cmd);

    $output = trim((string) shell_exec("printf $cmd | $katex"));

    if ($output === '') {
      // katex rejected the formula: show the TeX source instead of nothing (and do not fail the page)
      Log::warning("DocumentMarkup: katex rejected the formula: " . mb_substr($formula, 0, 200));
      return '<span class="formula-error">' . htmlspecialchars($formula, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    return $output;
  }

  public function fixLatex($text) {
    $text = preg_replace_callback(DocumentMarkup::LATEX, function($m) {
      return $this->latexToMathML($m[2]);
    }, $text);
    return $text;
  }

  public function fix($text) {
    $text = $this->fixLineBreaks($text);
    $text = $this->fixBold($text);
    $text = $this->fixLatex($text);
    return $text;
  }
}
