<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSlackNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:slack-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Slack notification sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $webhookUrl = env('SLACK_WEBHOOK_URL');
        
        if (!$webhookUrl) {
            $this->error('SLACK_WEBHOOK_URL が設定されていません。');
            return;
        }

        $this->info('Webhook URL: ' . substr($webhookUrl, 0, 50) . '...');

        try {
            $payload = [
                'event_type' => 'test',
                'title' => 'テスト通知',
                'message' => 'これは承認ワークフローシステムからのテスト通知です。',
                'timestamp' => now()->toISOString(),
                'application_id' => 999,
                'application_title' => 'テスト申請',
                'application_type' => 'test',
                'applicant_name' => 'テストユーザー',
                'applicant_email' => 'test@example.com',
                'approver_name' => 'テスト承認者',
                'status' => 'test',
                'url' => 'https://example.com/applications/999',
            ];

            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, $payload);

            if ($response->successful()) {
                $this->info('✅ Slack通知の送信に成功しました！');
                $this->info('レスポンス: ' . $response->body());
            } else {
                $this->error('❌ Slack通知の送信に失敗しました。');
                $this->error('ステータスコード: ' . $response->status());
                $this->error('レスポンス: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('❌ エラーが発生しました: ' . $e->getMessage());
        }
    }
}
