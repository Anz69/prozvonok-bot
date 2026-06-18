<?php

namespace App\Services\Zvonok;

use App\Models\CheckJob;
use App\Models\CheckNumber;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Генерация выходного .xlsx по результатам проверки (раздел 3.3, формат — раздел 8.7):
 * вкладки «Все» / «Дозвон» / «НДЗ», колонки номер/статус/оператор/MNP/активность/
 * часовой пояс/последний статус/транскрипция, с подсветкой строк по статусу.
 */
class CheckResultExporter
{
    private const HEADERS = [
        'Номер', 'Статус', 'Оператор', 'MNP', 'Активность',
        'Часовой пояс', 'Последний статус', 'Транскрипция',
    ];

    /**
     * @return string относительный путь в диске local
     */
    public function export(CheckJob $job): string
    {
        $spreadsheet = new Spreadsheet();

        $all = $spreadsheet->getActiveSheet();
        $this->fillSheet($all, 'Все', $job->numbers()->cursor());
        $this->fillSheet(
            $spreadsheet->createSheet(),
            'Дозвон',
            $job->numbers()->where('status', CheckNumber::STATUS_ANSWERED)->cursor(),
        );
        $this->fillSheet(
            $spreadsheet->createSheet(),
            'НДЗ',
            $job->numbers()->where('status', CheckNumber::STATUS_NO_ANSWER)->cursor(),
        );
        $spreadsheet->setActiveSheetIndex(0);

        Storage::disk('local')->makeDirectory('checks/results');
        $relative = "checks/results/job_{$job->id}.xlsx";
        $absolute = Storage::disk('local')->path($relative);

        (new Xlsx($spreadsheet))->save($absolute);
        $spreadsheet->disconnectWorksheets();

        return $relative;
    }

    /**
     * @param  iterable<CheckNumber>  $numbers
     */
    private function fillSheet(Worksheet $sheet, string $title, iterable $numbers): void
    {
        $sheet->setTitle($title);
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $row = 2;
        foreach ($numbers as $number) {
            $isAnswered = $number->status === CheckNumber::STATUS_ANSWERED;

            $sheet->fromArray([
                $number->phone,
                $isAnswered ? '🟢 Дозвон' : '🔴 НДЗ',
                $number->operator,
                $number->mnp_operator,
                $number->is_active === null ? '' : ($number->is_active ? 'да' : 'нет'),
                $number->timezone,
                $number->last_status,
                $number->transcription,
            ], null, "A{$row}");

            $color = $isAnswered ? 'FFE6F4EA' : 'FFFBE9E7';
            $sheet->getStyle("A{$row}:H{$row}")
                ->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($color);

            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
