<?php

namespace App\Support;

/**
 * Pembaca CSV yang toleran terhadap dua jebakan umum Excel Indonesia:
 *  1. BOM UTF-8 (\xEF\xBB\xBF) di awal file — membuat nama kolom pertama
 *     tidak terbaca ("\ufefflayanan" != "layanan").
 *  2. Pemisah titik-koma (;) — Excel dengan locale Indonesia menyimpan
 *     "CSV" memakai ; alih-alih koma.
 */
class CsvReader
{
    /** @var resource */
    private $handle;

    private string $delimiter = ',';

    private bool $first = true;

    /** @param resource $handle */
    public function __construct($handle)
    {
        $this->handle = $handle;
        $this->detectDelimiter();
    }

    private function detectDelimiter(): void
    {
        $pos = ftell($this->handle);
        $line = (string) fgets($this->handle);
        fseek($this->handle, $pos);

        // Delimiter = yang paling sering muncul di baris header.
        $this->delimiter = substr_count($line, ';') > substr_count($line, ',') ? ';' : ',';
    }

    /** @return array<int,string|null>|false */
    public function row()
    {
        $row = fgetcsv($this->handle, 0, $this->delimiter);
        if ($this->first && is_array($row) && isset($row[0])) {
            // Buang BOM UTF-8 dari sel pertama.
            $row[0] = preg_replace('/^\x{FEFF}/u', '', (string) $row[0]);
            $this->first = false;
        }

        return $row;
    }

    /** Header ternormalisasi: huruf kecil + trim + tanpa BOM. */
    public function header(): array
    {
        $row = $this->row();
        if (! is_array($row)) {
            return [];
        }

        return array_map(fn ($h) => strtolower(trim((string) $h)), $row);
    }
}
