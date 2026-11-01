<?php

namespace App\Console\Commands;

use App\Support\FormulaChecker;
use App\Support\FormulaRepair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Repairs the stored `$$formulas$$` that katex 0.19 rejects (see FormulaRepair). A dry run unless --apply is given.
// Writes with DB::table, so no audit rows: the old and the new formula of every row go to the log instead.
class RepairFormulas extends Command
{
    protected $signature = 'formulas:repair {--apply : write the repaired texts (default: only show what would change)}';

    protected $description = 'Put braces around the radicand of \sqrt[n]\frac{a}{b} in the stored formulas (dry run unless --apply)';

    private const FORMULA = '/`\$\$(.+?)\$\$`/';

    public function handle(FormulaRepair $repair, FormulaChecker $checker): int
    {
        $changes = $this->findChanges($repair);
        $this->info(count($changes) . ' texts change');
        $this->showChanges($changes);

        if ($this->stillFailing($changes, $checker)) {
            $this->error('a repaired formula is still rejected by katex: nothing is written');
            return 1;
        }

        if (!$this->option('apply')) {
            $this->comment('dry run: nothing written (use --apply after a database dump)');
            return 0;
        }

        $this->writeChanges($changes);

        return 0;
    }

    // [['table', 'column', 'id', 'old', 'new'], ...]
    private function findChanges(FormulaRepair $repair): array
    {
        $changes = [];

        foreach ($this->textColumns() as $column) {
            foreach ($this->rowsWithFormulas($column->t, $column->c) as $row) {
                $new = $repair->repairText((string) $row->text);

                if ($new !== (string) $row->text) {
                    $changes[] = ['table' => $column->t, 'column' => $column->c, 'id' => $row->id, 'old' => (string) $row->text, 'new' => $new];
                }
            }
        }

        return $changes;
    }

    private function textColumns(): array
    {
        $database = config('database.connections.' . config('database.default') . '.database');

        return DB::select(
            "select c.table_name t, c.column_name c from information_schema.columns c
             where c.table_schema = ? and c.data_type in ('text', 'mediumtext', 'longtext', 'varchar')
               and c.table_name <> 'audits'
               and exists (select 1 from information_schema.columns i where i.table_schema = c.table_schema and i.table_name = c.table_name and i.column_name = 'id')",
            [$database]
        );
    }

    private function rowsWithFormulas(string $table, string $column)
    {
        return DB::table($table)->where($column, 'like', '%$$%')->get(['id', DB::raw("`$column` as text")]);
    }

    private function showChanges(array $changes): void
    {
        $rows = [];

        foreach ($changes as $change) {
            foreach ($this->changedFormulas($change) as [$old, $new]) {
                $rows[] = ["{$change['table']}.{$change['column']} #{$change['id']}", mb_substr($old, 0, 70), mb_substr($new, 0, 70)];
            }
        }

        $this->table(['where', 'old', 'new'], $rows);
    }

    // [[old formula, new formula], ...]: only the formulas of the text that differ
    private function changedFormulas(array $change): array
    {
        preg_match_all(self::FORMULA, $change['old'], $old);
        preg_match_all(self::FORMULA, $change['new'], $new);

        return array_values(array_filter(
            array_map(null, $old[1], $new[1]),
            fn (array $pair) => $pair[0] !== $pair[1]
        ));
    }

    private function stillFailing(array $changes, FormulaChecker $checker): bool
    {
        $repaired = [];

        foreach ($changes as $change) {
            $repaired = array_merge($repaired, array_column($this->changedFormulas($change), 1));
        }

        return $repaired !== [] && $checker->failures($repaired) !== [];
    }

    private function writeChanges(array $changes): void
    {
        DB::transaction(function () use ($changes) {
            foreach ($changes as $change) {
                $this->writeChange($change);
            }
        });

        $this->info(count($changes) . ' texts written; the old texts are in the log (formulas:repair)');
    }

    // only when the text is still what we read: an author may have saved it in the meantime
    private function writeChange(array $change): void
    {
        $updated = DB::table($change['table'])
            ->where('id', $change['id'])
            ->where($change['column'], $change['old'])
            ->update([$change['column'] => $change['new']]);

        Log::info('formulas:repair', $change + ['updated' => $updated]);
    }
}
