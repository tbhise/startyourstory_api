<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Import marketing contacts from a CSV into `marketing_contacts`.
 *
 *   php artisan marketing:import storage/app/marketing_contacts.csv
 *   php artisan marketing:import /absolute/path/contacts.csv
 *
 * Expected CSV (header row, any column order):
 *
 *   firm_name,email,phone
 *   Kumar & Associates,contact@kumarca.in,9876543210
 *
 * Accepted header aliases: firm_name | firm | name  ·  email  ·  phone | mobile.
 * A file with NO recognisable header is read positionally as firm_name, email, phone.
 *
 * Rules: a valid email is mandatory, emails are lower-cased and must be unique
 * (existing rows and repeats inside the file are reported as duplicates), and a
 * bad row never stops the import. Over-long values are trimmed to the column
 * width (firm_name 255, phone 20); an email longer than 255 is rejected instead,
 * since truncating an address would silently create a wrong one.
 */
class ImportMarketingContacts extends Command
{
    protected $signature = 'marketing:import
        {file : Path to the CSV file (absolute, or relative to the project root)}';

    protected $description = 'Import marketing contacts from a CSV into marketing_contacts (skips duplicates and invalid rows).';

    /** Cap on per-row diagnostic lines, so a re-import of a known file stays readable. */
    private const MAX_DETAIL_LINES = 25;

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('file'));

        if (!is_file($path) || !is_readable($path)) {
            $this->error("[ERROR] Cannot read file: {$path}");
            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("[ERROR] Failed to open file: {$path}");
            return self::FAILURE;
        }

        $this->info("[INFO] Reading {$path}");

        // Emails already taken — existing rows first, then everything accepted
        // from this file, so in-file repeats are caught too.
        $taken = DB::table('marketing_contacts')
            ->pluck('email')
            ->mapWithKeys(fn ($e) => [mb_strtolower((string) $e) => true])
            ->all();

        $line = 0;
        $rows = 0;
        $imported = 0;
        $duplicates = 0;
        $invalid = 0;
        $failed = 0;
        $details = 0;
        $columns = null;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            // Blank line.
            if ($row === [null] || (count($row) === 1 && trim((string) $row[0]) === '')) {
                continue;
            }

            // First non-blank line: either a header (mapped) or positional data.
            if ($columns === null) {
                $row[0] = $this->stripBom((string) ($row[0] ?? ''));
                $columns = $this->resolveHeader($row);

                if ($columns !== null) {
                    if ($columns['firm_name'] === null) {
                        fclose($handle);
                        $this->error('[ERROR] The CSV header has an email column but no firm_name column. Required header: firm_name,email,phone');
                        return self::FAILURE;
                    }
                    continue; // it WAS a header — nothing to import from it
                }

                $columns = ['firm_name' => 0, 'email' => 1, 'phone' => 2];
                $this->warn('[WARN] No header row detected — reading columns positionally as firm_name, email, phone.');
            }

            $rows++;
            $lineNo = $line;

            $firmName = trim((string) ($row[$columns['firm_name']] ?? ''));
            $email    = mb_strtolower(trim((string) ($row[$columns['email']] ?? '')));
            $phone    = $columns['phone'] !== null ? trim((string) ($row[$columns['phone']] ?? '')) : '';

            // ── Validate ────────────────────────────────────────────────────
            if ($firmName === '') {
                $invalid++;
                $this->detail($details, "[INVALID] line {$lineNo}: missing firm_name");
                continue;
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
                $invalid++;
                $this->detail($details, "[INVALID] line {$lineNo}: bad email '" . ($email ?: '—') . "'");
                continue;
            }
            if (isset($taken[$email])) {
                $duplicates++;
                $this->detail($details, "[DUPLICATE] line {$lineNo}: {$email}");
                continue;
            }

            // ── Insert ──────────────────────────────────────────────────────
            try {
                DB::table('marketing_contacts')->insert([
                    'firm_name'  => mb_substr($firmName, 0, 255),
                    'email'      => $email,
                    'phone'      => $phone !== '' ? mb_substr($phone, 0, 20) : null,
                    'status'     => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $taken[$email] = true;
                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $this->detail($details, "[FAILED] line {$lineNo}: {$email} — " . mb_substr($e->getMessage(), 0, 120));
            }
        }

        fclose($handle);

        $skipped = $duplicates + $invalid + $failed;

        $this->newLine();
        $this->line('Rows read:   ' . $rows);
        $this->info('Imported:    ' . $imported);
        $this->line('Skipped:     ' . $skipped);
        $this->line('  Duplicates: ' . $duplicates);
        $this->line('  Invalid:    ' . $invalid);
        if ($failed > 0) {
            $this->error('  Failed:     ' . $failed . ' (insert errors — see the lines above)');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** Absolute path, or resolved against the project root. */
    private function resolvePath(string $file): string
    {
        $file = str_replace('\\', '/', trim($file));

        $isAbsolute = str_starts_with($file, '/') || preg_match('/^[A-Za-z]:\//', $file) === 1;

        return $isAbsolute ? $file : base_path($file);
    }

    /**
     * Map a header row to column indexes, or null when the row is not a header.
     * A null firm_name index means the header is unusable (caller errors out).
     *
     * @return array{firm_name:?int,email:int,phone:?int}|null
     */
    private function resolveHeader(array $row): ?array
    {
        $cells = array_map(
            fn ($c) => strtolower(str_replace([' ', '-'], '_', trim((string) $c))),
            $row
        );

        $find = fn (array $aliases) => array_reduce(
            $aliases,
            fn ($carry, $alias) => $carry ?? (($i = array_search($alias, $cells, true)) === false ? null : $i),
            null
        );

        $email = $find(['email', 'email_address', 'e_mail']);
        if ($email === null) {
            return null; // no email column → this is a data row, not a header
        }

        return [
            'firm_name' => $find(['firm_name', 'firm', 'name', 'company', 'company_name']),
            'email'     => $email,
            'phone'     => $find(['phone', 'mobile', 'phone_number', 'mobile_number', 'contact']),
        ];
    }

    /** Print a per-row diagnostic until the cap is hit. */
    private function detail(int &$printed, string $message): void
    {
        if ($printed < self::MAX_DETAIL_LINES) {
            $this->warn($message);
        } elseif ($printed === self::MAX_DETAIL_LINES) {
            $this->warn('[WARN] Further per-row messages suppressed — see the summary below.');
        }

        $printed++;
    }

    /** Strip the UTF-8 BOM Excel prepends to the first header cell. */
    private function stripBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }
}
