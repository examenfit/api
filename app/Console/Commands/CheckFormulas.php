<?php

namespace App\Console\Commands;

use App\Support\FormulaChecker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

// Finds the `$$formulas$$` that katex cannot convert, before an export (or a student) runs into them.
class CheckFormulas extends Command
{
    protected $signature = 'formulas:check {--audits : also look in the audits table (old versions of edited texts)}';

    protected $description = 'List the stored formulas that katex cannot convert (exit code 1 when there are any)';

    private const FORMULA = '/(`\$\$(.+?)\$\$`)/m';

    // how many rows of a formula's places are listed before "..."
    private const PLACES_SHOWN = 4;

    public function handle(FormulaChecker $checker): int
    {
        $found = $this->collectFormulas();
        $formulas = array_map('strval', array_keys($found));
        $this->info(count($formulas) . ' unique formulas');

        try {
            $failures = $checker->failures($formulas);
        } catch (RuntimeException $error) {
            $this->error($error->getMessage());
            return 2;
        }

        return $this->report($failures, $formulas, $found);
    }

    private function report(array $failures, array $formulas, array $found): int
    {
        if ($failures === []) {
            $this->info('all formulas can be converted');
            return 0;
        }

        $this->showFailures($failures, $formulas, $found);

        return 1;
    }

    // formula => [[table, column, id], ...]
    private function collectFormulas(): array
    {
        $found = [];

        foreach ($this->searchableColumns() as $column) {
            foreach ($this->rowsWithFormulas($column) as $row) {
                $this->recordFormulas($found, $column, $row);
            }
        }

        return $found;
    }

    private function searchableColumns(): array
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        $columns = DB::select(
            "select table_name t, column_name c from information_schema.columns where table_schema = ? and data_type in ('text', 'mediumtext', 'longtext', 'varchar')",
            [$database]
        );

        return array_values(array_filter($columns, fn ($column) => $this->isSearched($column->t)));
    }

    private function isSearched(string $table): bool
    {
        $isAuditsTable = $table === 'audits';

        return !$isAuditsTable || $this->option('audits');
    }

    private function rowsWithFormulas(object $column)
    {
        $columns = Schema::hasColumn($column->t, 'id') ? ['id', $column->c] : [$column->c];

        return DB::table($column->t)->where($column->c, 'like', '%$$%')->get($columns);
    }

    private function recordFormulas(array &$found, object $column, object $row): void
    {
        foreach ($this->extractFormulas((string) $row->{$column->c}) as $formula) {
            $found[(string) $formula][] = [$column->t, $column->c, $row->id ?? '-'];
        }
    }

    private function extractFormulas(string $text): array
    {
        return preg_match_all(self::FORMULA, $text, $matches) ? $matches[2] : [];
    }

    private function showFailures(array $failures, array $formulas, array $found): void
    {
        $rows = array_map(fn ($failure) => $this->describeFailure($failure, $formulas[$failure['index']], $found), $failures);

        $this->table(['where', 'katex says', 'formula'], $rows);
        $this->error(count($failures) . ' formulas cannot be converted');
    }

    private function describeFailure(array $failure, string $formula, array $found): array
    {
        $places = $found[$formula];
        $shown = array_map(fn ($place) => "{$place[0]}.{$place[1]} #{$place[2]}", array_slice($places, 0, self::PLACES_SHOWN));
        $hasMore = count($places) > self::PLACES_SHOWN;

        return [implode(', ', $shown) . ($hasMore ? ', ...' : ''), mb_substr($failure['message'], 0, 70), mb_substr($formula, 0, 60)];
    }
}
