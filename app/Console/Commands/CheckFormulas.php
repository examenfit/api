<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

// Finds the `$$formulas$$` that katex cannot convert, before an export (or a student) runs into them.
class CheckFormulas extends Command
{
    protected $signature = 'formulas:check {--audits : also look in the audits table (old versions of edited texts)}';

    protected $description = 'List the stored formulas that katex cannot convert (exit code 1 when there are any)';

    public function handle()
    {
        $found = $this->collectFormulas();
        $formulas = array_map('strval', array_keys($found));
        $this->info(count($formulas) . ' unique formulas');

        $process = new Process(['node', base_path('scripts/check-formulas.js')]);
        $process->setInput(json_encode($formulas));
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('node failed: ' . trim($process->getErrorOutput()));
            return 2;
        }

        $failures = json_decode($process->getOutput(), true);

        if (!$failures) {
            $this->info('all formulas can be converted');
            return 0;
        }

        $rows = [];

        foreach ($failures as $failure) {
            $formula = $formulas[$failure['index']];
            $where = array_map(fn ($w) => "{$w[0]}.{$w[1]} #{$w[2]}", array_slice($found[$formula], 0, 4));
            $rows[] = [
                implode(', ', $where) . (count($found[$formula]) > 4 ? ', ...' : ''),
                mb_substr($failure['message'], 0, 70),
                mb_substr($formula, 0, 60),
            ];
        }

        $this->table(['where', 'katex says', 'formula'], $rows);
        $this->error(count($failures) . ' formulas cannot be converted');

        return 1;
    }

    // formula => [[table, column, id], ...]
    private function collectFormulas(): array
    {
        $db = config('database.connections.' . config('database.default') . '.database');
        $columns = DB::select(
            "select table_name t, column_name c from information_schema.columns where table_schema = ? and data_type in ('text', 'mediumtext', 'longtext', 'varchar')",
            [$db]
        );
        $found = [];

        foreach ($columns as $column) {
            if ($column->t === 'audits' && !$this->option('audits')) {
                continue;
            }

            $hasId = Schema::hasColumn($column->t, 'id');
            $rows = DB::table($column->t)->where($column->c, 'like', '%$$%')->get($hasId ? ['id', $column->c] : [$column->c]);

            foreach ($rows as $row) {
                if (preg_match_all('/(`\$\$(.+?)\$\$`)/m', (string) $row->{$column->c}, $matches)) {
                    foreach ($matches[2] as $formula) {
                        $found[(string) $formula][] = [$column->t, $column->c, $hasId ? $row->id : '-'];
                    }
                }
            }
        }

        return $found;
    }
}
