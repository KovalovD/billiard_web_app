<?php

namespace App\Payment\Http\Controllers;

use App\Payment\Models\Donation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonoWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()?->all();

        if (($payload['type'] ?? '') !== 'StatementItem') {
            return response()->json(['ignored' => true]);
        }

        $tx = $payload['data']['statementItem'] ?? [];
        $acc = $payload['data']['account'] ?? '';

        // Отфильтровываем события не нашей банки
        if ($acc !== config('services.monobank.jar_id')) {
            return response()->json(['ignored' => true]);
        }

        Donation::firstOrCreate(
            ['order_id' => (string) $tx['id']],
            [
                'amount'   => $tx['amount'] / 100,
                'currency' => $tx['currencyCode'] === 980 ? 'UAH' : $tx['currencyCode'],
                'status'   => 'success',
                'paid_at'  => Carbon::createFromTimestamp($tx['time']),
                'payload'  => $tx,
            ],
        );

        return response()->json(['ok' => true]);
    }
}
