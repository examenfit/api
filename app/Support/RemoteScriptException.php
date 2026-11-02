<?php

namespace App\Support;

use RuntimeException;

// Thrown when the pdf server cannot be reached or a script on it fails. Rendered as a readable 502 instead of a 500
// with a stack trace ("The file /tmp/x.pdf does not exist"); RemoteScripts has already logged the details.
class RemoteScriptException extends RuntimeException
{
    public function render()
    {
        return response()->json(['message' => 'De PDF-server is niet bereikbaar of gaf een fout. Probeer het later opnieuw.'], 502);
    }
}
