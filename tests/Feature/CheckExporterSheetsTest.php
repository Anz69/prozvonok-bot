<?php

namespace Tests\Feature;

use App\Models\BotUser;
use App\Models\CheckJob;
use App\Models\CheckNumber;
use App\Services\Zvonok\CheckResultExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class CheckExporterSheetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_splits_into_status_sheets(): void
    {
        Storage::fake('local');

        $user = BotUser::create(['telegram_id' => 1]);
        $job = CheckJob::create([
            'bot_user_id' => $user->id,
            'geo_code' => 'RU',
            'numbers_total' => 3,
            'numbers_valid' => 3,
            'cost' => 0,
            'status' => CheckJob::STATUS_PROCESSING,
        ]);
        $job->numbers()->create(['phone' => '+79000000001', 'status' => CheckNumber::STATUS_ANSWERED]);
        $job->numbers()->create(['phone' => '+79000000002', 'status' => CheckNumber::STATUS_ANSWERED]);
        $job->numbers()->create(['phone' => '+79000000003', 'status' => CheckNumber::STATUS_NO_ANSWER]);

        $relative = app(CheckResultExporter::class)->export($job);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($relative));
        $this->assertSame(['Все', 'Дозвон', 'НДЗ'], $spreadsheet->getSheetNames());

        // На вкладке «Дозвон» — заголовок + 2 строки, на «НДЗ» — заголовок + 1
        $this->assertSame(3, $spreadsheet->getSheetByName('Дозвон')->getHighestRow());
        $this->assertSame(2, $spreadsheet->getSheetByName('НДЗ')->getHighestRow());
    }
}
