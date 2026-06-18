<?php

namespace Tests\Unit;

use App\Services\NumberFileParser;
use Tests\TestCase;

class NumberFileParserTest extends TestCase
{
    public function test_parses_normalizes_dedups_and_filters_garbage(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'num') . '.txt';
        file_put_contents($path, implode("\n", [
            '+7 (900) 123-45-67', // валидный
            '8 900 123 45 67',    // тот же номер в другом формате → дубль
            '9001234567',         // тот же без кода страны
            'мусор без цифр',     // отсев (нет цифр)
            '12345',              // невалидный номер
        ]));

        $result = (new NumberFileParser())->parse($path, 'RU');
        @unlink($path);

        $this->assertSame(['+79001234567'], $result['valid']);
        $this->assertSame(4, $result['total']);   // 4 кандидата с цифрами
        $this->assertSame(1, $result['invalid']); // '12345'
    }
}
