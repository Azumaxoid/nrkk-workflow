<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSesEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ses-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SES email sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        try {
            $mail = new \App\Mail\ApplicationNotificationMail(
                'SESテストメール',
                'このメールはAmazon SESの動作テストです。正常に送信されています。',
                ['test' => 'データ'],
                'https://example.com'
            );

            \Mail::to($email)->send($mail);
            
            $this->info("テストメールが {$email} に送信されました。");
        } catch (\Exception $e) {
            newrelic_notice_error('SES email command failed', $e);
            $this->error("メール送信に失敗しました: " . $e->getMessage());
        }
    }
}
