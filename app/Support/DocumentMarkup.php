<?php

namespace App\Support;

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

  function latexToMathML($formula)
  {
    $katex = base_path('/node_modules/katex/cli.js');
    $input = addslashes(addslashes($formula));
    $result = shell_exec("echo \"{$input}\" | {$katex}");
    return trim($result);
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
