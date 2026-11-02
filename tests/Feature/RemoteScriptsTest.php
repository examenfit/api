<?php

namespace Tests\Feature;

use App\Support\RemoteScriptException;
use App\Support\RemoteScripts;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

// ssh and scp are replaced by small shell scripts, so no pdf server is needed.
class RemoteScriptsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/remote-scripts-' . uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory . '/*'));
        rmdir($this->directory);
        @unlink('/tmp/remote-scripts-test.pdf');

        parent::tearDown();
    }

    private function writeCommand(string $name, string $body): string
    {
        $path = "{$this->directory}/$name";
        file_put_contents($path, "#!/bin/sh\n$body\n");
        chmod($path, 0755);

        return $path;
    }

    private function remoteScripts(string $ssh, string $scp): RemoteScripts
    {
        return new RemoteScripts('pdf.example', $ssh, $scp);
    }

    public function test_a_pdf_is_made_and_fetched_to_tmp()
    {
        $ssh = $this->writeCommand('ssh', 'true');
        $scp = $this->writeCommand('scp', 'for last; do :; done; echo pdf > "$last"');

        $local = $this->remoteScripts($ssh, $scp)->makePdf('pdf', 'abc', 'https://api.test/x', 'remote-scripts-test.pdf');

        $this->assertSame('/tmp/remote-scripts-test.pdf', $local);
        $this->assertFileExists($local);
    }

    public function test_ssh_gets_fail_fast_options_and_an_escaped_remote_command()
    {
        $ssh = $this->writeCommand('ssh', 'echo "$@" > ' . $this->directory . '/ssh-arguments');
        $scp = $this->writeCommand('scp', 'true');

        $this->remoteScripts($ssh, $scp)->makePdf('pdf', 'abc', 'https://api.test/a b', 'remote-scripts-test.pdf');

        $arguments = file_get_contents($this->directory . '/ssh-arguments');
        $this->assertStringContainsString('-o BatchMode=yes -o ConnectTimeout=10 examenfit@pdf.example', $arguments);
        $this->assertStringContainsString("'make/pdf' 'abc' 'https://api.test/a b'", $arguments);
    }

    public function test_a_failing_ssh_logs_command_status_and_output_and_throws()
    {
        Log::spy();
        $ssh = $this->writeCommand('ssh', 'echo "Permission denied (publickey)." >&2; exit 255');
        $scp = $this->writeCommand('scp', 'echo should-not-run > ' . $this->directory . '/scp-ran');

        try {
            $this->remoteScripts($ssh, $scp)->makePdf('pdf', 'abc', 'https://api.test/x', 'remote-scripts-test.pdf');
            $this->fail('expected a RemoteScriptException');
        } catch (RemoteScriptException $e) {
            $this->assertFileDoesNotExist($this->directory . '/scp-ran', 'scp is not run after a failed ssh');
        }

        Log::shouldHaveReceived('error')->withArgs(fn ($message, $context) => $context['exit'] === 255
            && str_contains($context['output'], 'Permission denied')
            && str_contains($context['command'], 'pdf.example'))->once();
    }

    public function test_a_failing_scp_throws()
    {
        $ssh = $this->writeCommand('ssh', 'true');
        $scp = $this->writeCommand('scp', 'echo "No such file" >&2; exit 1');

        $this->expectException(RemoteScriptException::class);

        $this->remoteScripts($ssh, $scp)->makePdf('pdf', 'abc', 'https://api.test/x', 'remote-scripts-test.pdf');
    }

    public function test_a_command_that_cannot_start_throws_the_same_exception()
    {
        $this->expectException(RemoteScriptException::class);

        $this->remoteScripts('/nonexistent/ssh', 'true')->makePdf('pdf', 'abc', 'https://api.test/x', 'remote-scripts-test.pdf');
    }

    public function test_the_exception_is_rendered_as_a_readable_502()
    {
        $response = (new RemoteScriptException('x'))->render();

        $this->assertSame(502, $response->getStatusCode());
        $this->assertStringContainsString('PDF-server', $response->getContent());
    }
}
