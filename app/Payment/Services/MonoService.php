<?php

namespace App\Payment\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

final class MonoService
{
    private string $token;
    private string $jarId;

    public function __construct()
    {
        $this->token = config('services.monobank.token');
        $this->jarId = config('services.monobank.jar_id');
    }

    /** Подписать / изменить URL WebHook
     * @throws ConnectionException
     * @throws RequestException
     */
    public function setWebhook(string $url): void
    {
        Http::withHeaders(['X-Token' => $this->token])
            ->post('https://api.monobank.ua/personal/webhook', [
                'webHookUrl' => $url,
            ])->throw()
        ;
    }

    /** Выписка Банки за период (Unix-time, сек)
     * @throws ConnectionException
     * @throws RequestException
     */
    public function jarStatement(int $from, int $to): array
    {
        return Http::withHeaders(['X-Token' => $this->token])
            ->get("https://api.monobank.ua/personal/statement/{$this->jarId}/$from/$to")
            ->throw()
            ->json()
        ;
    }
}
