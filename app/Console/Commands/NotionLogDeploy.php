<?php
// app/Console/Commands/NotionLogDeploy.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotionLogDeploy extends Command
{
    /** @var string */
    protected $signature = 'notion:log-deploy
        {--branch=main : Имя ветки}
        {--sha= : Короткий SHA коммита (7-симв.)}';

    /** @var string */
    protected $description = 'Создаёт запись о деплое в таблице Notion Deployments и тегает всех сотрудников';

    public function handle(): int
    {
        /* -------- 0. Конфиг -------- */
        $token = config('app.notion_api');
        $databaseId = config('app.notion_deploy_db');
        $apiVersion = '2022-06-28';

        /* -------- 1. Параметры -------- */
        $branch = $this->option('branch');
        $sha = $this->option('sha') ?: substr(hash('sha1', uniqid('', true)), 0, 7);
        $deployed = now()->setTimezone('Europe/Kyiv');

        /* -------- 2. Люди -------- */
        $users = collect();
        $cursor = null;

        do {
            $resp = Http::withToken($token)
                ->withHeaders(['Notion-Version' => $apiVersion])
                ->get('https://api.notion.com/v1/users', $cursor ? ['start_cursor' => $cursor] : [])
            ;

            if (!$resp->successful()) {
                $this->error("Unable to read users: ".$resp->body());
                return self::FAILURE;
            }

            $body = $resp->json();
            $users = $users->concat($body['results']);
            $cursor = $body['has_more'] ? $body['next_cursor'] : null;

        } while ($cursor);

        $people = $users->filter(fn($u) => $u['type'] === 'person');
        $mentions = $people
            ->flatMap(fn($u) => [
                ['type' => 'mention', 'mention' => ['type' => 'user', 'user' => ['id' => $u['id']]]],
                ['type' => 'text', 'text' => ['content' => ' ']],
            ])
            ->slice(0, -1)
            ->values()
            ->all()
        ;

        /* -------- 3. Следующий номер -------- */
        $latest = Http::withToken($token)
            ->withHeaders(['Notion-Version' => $apiVersion])
            ->post("https://api.notion.com/v1/databases/{$databaseId}/query", [
                'page_size' => 1,
                'sorts'     => [['timestamp' => 'created_time', 'direction' => 'descending']],
            ])
        ;

        $nextNumber = $latest->json('results.0.properties.№.number', 0) + 1;

        /* -------- 4. Создаём страницу -------- */
        $payload = [
            'parent'     => ['database_id' => $databaseId],
            'properties' => [
                'Name'   => ['title' => [['text' => ['content' => "Deploy {$branch} {$sha}"]]]],
                'ID'     => ['number' => $nextNumber],
                'Date'   => ['date' => ['start' => $deployed->toAtomString()]],
                'Person' => ['people' => $people->pluck('id')->map(fn($id) => ['id' => $id])->all()],
            ],
            'children'   => [
                [
                    'object'    => 'block',
                    'type'      => 'paragraph',
                    'paragraph' => ['rich_text' => $mentions],
                ],
            ],
        ];

        $result = Http::withToken($token)
            ->withHeaders(['Notion-Version' => $apiVersion, 'Content-Type' => 'application/json'])
            ->post('https://api.notion.com/v1/pages', $payload)
        ;

        if (!$result->successful()) {
            Log::error('Notion create error', ['body' => $result->body()]);
            $this->error("Notion error: ".$result->body());
            return self::FAILURE;
        }

        $this->info('Deploy logged. Page id: '.$result->json('id'));
        return self::SUCCESS;
    }
}
