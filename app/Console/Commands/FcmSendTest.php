<?php

namespace App\Console\Commands;

use App\Services\Fcm\FcmClient;
use Illuminate\Console\Command;

class FcmSendTest extends Command
{
    protected $signature = 'fcm:test {--topic=myrba_client} {--title=Test} {--body=Hello}';

    protected $description = 'Kirim test push notification via FCM topic';

    public function handle(): int
    {
        $topic = (string) $this->option('topic');
        $title = (string) $this->option('title');
        $body = (string) $this->option('body');

        try {
            $res = (new FcmClient())->sendToTopic($topic, $title, $body, ['type' => 'test']);
            $this->info('OK');
            $this->line(json_encode($res));
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}

