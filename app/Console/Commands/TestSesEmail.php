<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicationNotificationMail;

class TestSesEmail extends Command
{
    protected $signature = 'mail:test-ses {email} {--subject=Test Email from SES} {--message=This is a test email sent via Amazon SES}';
    protected $description = 'Test Amazon SES email configuration';

    public function handle()
    {
        $email = $this->argument('email');
        $subject = $this->option('subject');
        $message = $this->option('message');

        $this->info('Testing Amazon SES configuration...');
        $this->info('Sending test email to: ' . $email);
        $this->info('Mail driver: ' . config('mail.default'));
        $this->info('AWS Region: ' . config('services.ses.region'));
        
        // Display current configuration
        $this->table(
            ['Configuration', 'Value'],
            [
                ['MAIL_MAILER', config('mail.default')],
                ['AWS_DEFAULT_REGION', config('services.ses.region')],
                ['MAIL_FROM_ADDRESS', config('mail.from.address')],
                ['MAIL_FROM_NAME', config('mail.from.name')],
                ['AWS Access Key ID', substr(config('services.ses.key'), 0, 4) . '****' . (config('services.ses.key') ? substr(config('services.ses.key'), -4) : 'Not Set')],
            ]
        );

        try {
            // Send test email using the notification mail class
            Mail::to($email)->send(
                new ApplicationNotificationMail(
                    $subject,
                    $message,
                    ['test' => true, 'timestamp' => now()->toString()],
                    url('/')
                )
            );

            $this->info('✅ Test email sent successfully!');
            $this->info('Please check your inbox (and spam folder) for the test email.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            newrelic_notice_error('SES email test failed', $e);
            $this->error('❌ Failed to send test email');
            $this->error('Error: ' . $e->getMessage());
            
            // Provide helpful troubleshooting tips
            $this->warn('Troubleshooting tips:');
            $this->warn('1. Verify your AWS credentials are correct');
            $this->warn('2. Ensure your email address is verified in SES (if in sandbox mode)');
            $this->warn('3. Check that your AWS region is correct');
            $this->warn('4. Verify IAM permissions include ses:SendEmail and ses:SendRawEmail');
            
            return Command::FAILURE;
        }
    }
}