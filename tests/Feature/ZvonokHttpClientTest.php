<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Zvonok\HttpZvonokClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZvonokHttpClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('dozvon.zvonok.base_url', 'https://zvonok.com/manager/cabapi_external/api/v1');
        config()->set('dozvon.zvonok.default_geo', 'RU');
        config()->set('dozvon.zvonok.accounts', []); // по умолчанию — только legacy-пара (фолбэк)
        config()->set('dozvon.zvonok.api_key', 'KEY123');
        config()->set('dozvon.zvonok.campaign_id', '555');
        Setting::put('zvonok_status_map', json_encode([
            'compl_finished' => 'answered',
            'attempts_exc' => 'no_answer',
        ]), 'json');
        Cache::flush();
    }

    public function test_create_calls_hits_phones_call_with_key_and_campaign(): void
    {
        Http::fake(['*' => Http::response(['status' => 'ok', 'data' => ['id' => 1]], 200)]);

        $campaign = (new HttpZvonokClient())->createCalls(['+79990000001'], ['text' => 'hi']);

        $this->assertSame('555', $campaign);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'phones/call/')
                && $request['public_key'] === 'KEY123'
                && $request['campaign_id'] === '555'
                && $request['phone'] === '+79990000001';
        });
    }

    public function test_create_calls_uses_country_account_by_geo(): void
    {
        // У каждой страны свой аккаунт Звонок.com: public_key + campaign_id выбираются по geo.
        config()->set('dozvon.zvonok.accounts', [
            'RU' => ['api_key' => 'KEY_RU', 'campaign_id' => '111'],
            'BY' => ['api_key' => 'KEY_BY', 'campaign_id' => '222'],
            'KZ' => ['api_key' => 'KEY_KZ', 'campaign_id' => '333'],
        ]);
        Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

        $campaign = (new HttpZvonokClient())->createCalls(['+375290000001'], ['geo' => 'BY']);

        $this->assertSame('222', $campaign);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'phones/call/')
            && $request['public_key'] === 'KEY_BY'
            && $request['campaign_id'] === '222');
    }

    public function test_create_calls_falls_back_to_legacy_keys_when_geo_account_missing(): void
    {
        // Для страны без своего аккаунта — фолбэк на общую legacy-пару.
        config()->set('dozvon.zvonok.accounts', ['RU' => ['api_key' => 'KEY_RU', 'campaign_id' => '111']]);
        Http::fake(['*' => Http::response(['status' => 'ok'], 200)]);

        $campaign = (new HttpZvonokClient())->createCalls(['+77010000001'], ['geo' => 'KZ']);

        $this->assertSame('555', $campaign); // из legacy campaign_id (setUp)
        Http::assertSent(fn ($request) => $request['public_key'] === 'KEY123' && $request['campaign_id'] === '555');
    }

    public function test_fetch_results_maps_statuses(): void
    {
        Http::fake([
            '*calls_by_phone*' => Http::sequence()
                ->push(['status' => 'ok', 'data' => ['status' => 'compl_finished', 'phone_oper' => 'МТС']], 200)
                ->push(['status' => 'ok', 'data' => ['status' => 'attempts_exc']], 200)
                ->push(['status' => 'ok', 'data' => ['status' => 'in_process']], 200),
        ]);

        $res = (new HttpZvonokClient())->fetchResults('555', ['+700', '+701', '+702']);

        $this->assertSame('answered', $res['+700']['status']);
        $this->assertSame('МТС', $res['+700']['operator']);
        $this->assertSame('compl_finished', $res['+700']['last_status']);
        $this->assertSame('no_answer', $res['+701']['status']);
        $this->assertArrayNotHasKey('+702', $res); // in_process — ещё не финал, пропускаем
    }
}
