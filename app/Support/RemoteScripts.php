<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

// Runs the make scripts on the pdf server over ssh and fetches the result with scp.
class RemoteScripts
{
    // ssh authentication goes with the public key of the web user. BatchMode: never ask for a password, and
    // ConnectTimeout: fail fast, so an unreachable server does not keep a php worker waiting.
    private const SSH_OPTIONS = ['-o', 'BatchMode=yes', '-o', 'ConnectTimeout=10'];

    // making a large pdf takes a while
    private const TIMEOUT = 300;

    public function __construct(
        private string $server,
        private string $ssh = 'ssh',
        private string $scp = 'scp'
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(config('app.examenfit_scripts_url'));
    }

    // runs make/$script on the server and copies the resulting pdf to /tmp; returns the local path
    public function makePdf(string $script, string $hash, string $api, string $pdf): string
    {
        $local = "/tmp/$pdf";

        $this->run([$this->ssh, ...self::SSH_OPTIONS, $this->login(), $this->remoteCommand($script, $hash, $api)]);
        $this->run([$this->scp, ...self::SSH_OPTIONS, "{$this->login()}:pdf/$pdf", $local]);

        return $local;
    }

    private function login(): string
    {
        return "examenfit@{$this->server}";
    }

    // ssh hands this string to the shell on the server, so every argument is escaped
    private function remoteCommand(string $script, string ...$arguments): string
    {
        return implode(' ', array_map('escapeshellarg', ["make/$script", ...$arguments]));
    }

    private function run(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(self::TIMEOUT);

        try {
            $process->run();
        } catch (\Throwable $e) {
            $this->fail($command, null, $e->getMessage());
        }

        if (!$process->isSuccessful()) {
            $this->fail($command, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
        }
    }

    private function fail(array $command, ?int $status, string $output): never
    {
        Log::error('RemoteScripts: command failed', ['command' => implode(' ', $command), 'exit' => $status, 'output' => trim($output)]);

        throw new RemoteScriptException('remote script failed: ' . $command[0]);
    }
}
