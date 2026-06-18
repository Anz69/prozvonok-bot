<?php

namespace App\Services;

use libphonenumber\PhoneNumberUtil;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Парсинг и нормализация номеров из .xlsx/.txt (раздел 3.3.3):
 * приведение к E.164, дедупликация, отсев мусора. Регион — по выбранному ГЕО.
 */
class NumberFileParser
{
    /**
     * @return array{valid: list<string>, total: int, invalid: int}
     */
    public function parse(string $path, string $geoCode): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $candidates = match ($extension) {
            'txt', 'csv' => $this->readText($path),
            'xlsx', 'xls' => $this->readSpreadsheet($path),
            default => [],
        };

        return $this->normalize($candidates, $geoCode);
    }

    /**
     * @return list<string>
     */
    private function readText(string $path): array
    {
        $content = file_get_contents($path) ?: '';

        return preg_split('/\R/u', $content) ?: [];
    }

    /**
     * @return list<string>
     */
    private function readSpreadsheet(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $values = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->toArray(null, true, false, false) as $row) {
                foreach ((array) $row as $cell) {
                    if ($cell !== null && $cell !== '') {
                        $values[] = (string) $cell;
                    }
                }
            }
        }

        return $values;
    }

    /**
     * @param  list<string>  $candidates
     * @return array{valid: list<string>, total: int, invalid: int}
     */
    private function normalize(array $candidates, string $geoCode): array
    {
        $util = PhoneNumberUtil::getInstance();
        $region = strtoupper($geoCode);

        $valid = [];
        $total = 0;
        $invalid = 0;

        foreach ($candidates as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }

            // оставляем только цифры и ведущий +
            $cleaned = preg_replace('/(?!^\+)\D/', '', $raw);
            if ($cleaned === '' || $cleaned === '+') {
                continue;
            }

            $total++;

            try {
                $number = $util->parse($cleaned, $region);
                if (! $util->isValidNumber($number)) {
                    $invalid++;
                    continue;
                }
                $e164 = $util->format($number, \libphonenumber\PhoneNumberFormat::E164);
                $valid[$e164] = true; // дедупликация по ключу
            } catch (\Throwable) {
                $invalid++;
            }
        }

        return [
            'valid' => array_keys($valid),
            'total' => $total,
            'invalid' => $invalid,
        ];
    }
}
