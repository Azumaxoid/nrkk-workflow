<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationSetting;
use App\Models\NotificationLog;
use App\Models\Application;
use App\Models\Approval;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ApplicationNotificationMail;

class NotificationService
{
    public function __construct()
    {
        //
    }

    public function sendNotificationToChannel($user, $channel, $eventType, $title, $message, $data = null)
    {
        // New Relicメトリクス記録
        $this->recordNotificationMetrics('single_channel', $channel, $eventType, $user);

        // 特定のチャンネルだけに送信
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'type' => $channel, // typeフィールドを追加
            'event_type' => $eventType,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'status' => 'pending',
        ]);

        $result = $this->sendByChannel($user, $channel, $eventType, $title, $message, $data);

        // 送信結果をNew Relicに記録
        $this->recordNotificationResult($result, $channel, $eventType);

        return $result;
    }

    public function sendNotification($userId, $eventType, $title, $message, $data = null)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        // New Relicメトリクス記録
        $this->recordNotificationMetrics('multi_channel', null, $eventType, $user);

        $settings = $user->notificationSettings()
            ->forEvent($eventType)
            ->enabled()
            ->get();

        if ($settings->isEmpty()) {
            // デフォルト設定を作成
            $this->createDefaultSettings($user, $eventType);
            $settings = $user->notificationSettings()
                ->forEvent($eventType)
                ->enabled()
                ->get();
        }

        $results = [];
        $channelCount = 0;
        foreach ($settings as $setting) {
            foreach ($setting->channels as $channel) {
                $result = $this->sendByChannel($user, $channel, $eventType, $title, $message, $data);
                $results[$channel] = $result;
                $channelCount++;

                // 各チャンネル結果をNew Relicに記録
                $this->recordNotificationResult($result, $channel, $eventType);
            }
        }

        // 総合メトリクス記録

        return $results;
    }

    protected function sendByChannel($user, $channel, $eventType, $title, $message, $data)
    {
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'type' => $channel,
            'event_type' => $eventType,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'status' => NotificationLog::STATUS_PENDING,
        ]);

        try {
            switch ($channel) {
                case 'email':
                    return $this->sendEmail($user, $title, $message, $log);
                case 'slack':
                    return $this->sendSlack($user, $title, $message, $log);
                case 'database':
                    return $this->sendDatabase($log);
                default:
                    $log->markAsFailed('Unknown channel: ' . $channel);
                    return false;
            }
        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            Log::error('Notification sending failed', [
                'user_id' => $user->id,
                'channel' => $channel,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function sendEmail($user, $title, $message, $log)
    {
        try {
            $actionUrl = null;
            if ($log->data && isset($log->data['application_id'])) {
                $actionUrl = url('/applications/' . $log->data['application_id']);
            }
            
            Mail::to($user->email)->send(
                new ApplicationNotificationMail($title, $message, $log->data, $actionUrl)
            );
            $log->markAsSent();
            return true;
        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            return false;
        }
    }

    protected function sendSlack($user, $title, $message, $log)
    {
        if (!$user->slack_webhook_url) {
            $log->markAsFailed('Slack webhook URL not configured');
            return false;
        }

        try {
            // Slack Workflowsに対応したシンプルなJSON形式
            $payload = [
                'event_type' => $log->event_type,
                'title' => $title,
                'message' => $message,
                'timestamp' => now()->toISOString(),
            ];

            // データがある場合は追加情報を含める
            if ($log->data) {
                $payload = array_merge($payload, [
                    'application_id' => $log->data['application_id'] ?? null,
                    'application_title' => $log->data['application_title'] ?? null,
                    'application_type' => $log->data['application_type'] ?? null,
                    'applicant_name' => $log->data['applicant_name'] ?? null,
                    'applicant_email' => $log->data['applicant_email'] ?? null,
                    'approver_name' => $log->data['approver_name'] ?? null,
                    'approval_step' => $log->data['approval_step'] ?? null,
                    'due_date' => $log->data['due_date'] ?? null,
                    'priority' => $log->data['priority'] ?? null,
                    'status' => $log->data['status'] ?? null,
                    'url' => $log->data['url'] ?? null,
                ]);
            }

            $response = Http::post($user->slack_webhook_url, $payload);

            if ($response->successful()) {
                $log->markAsSent();
                return true;
            } else {
                $log->markAsFailed('Slack API error: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            return false;
        }
    }

    protected function sendDatabase($log)
    {
        // データベース通知は既にログに記録されているので、送信済みマークのみ
        $log->markAsSent();
        return true;
    }

    protected function createDefaultSettings($user, $eventType)
    {
        NotificationSetting::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'channels' => ['database', 'email', 'slack'], // デフォルトチャネルにSlackを追加
            'is_enabled' => true,
        ]);
    }

    // 申請関連通知のヘルパーメソッド
    public function applicationSubmitted($application)
    {
        $title = '新しい申請が提出されました';
        $message = "{$application->applicant->name}さんから申請「{$application->title}」が提出されました。";
        
        $data = [
            'application_id' => $application->id,
            'application_title' => $application->title,
            'application_type' => $application->type,
            'applicant_name' => $application->applicant->name,
            'applicant_email' => $application->applicant->email,
            'due_date' => $application->due_date,
            'priority' => $application->priority ?? 'normal',
            'status' => $application->status,
            'url' => url('/applications/' . $application->id),
        ];
        
        // 承認者に通知
        $approvers = $application->approvals()->with('user')->get()->pluck('user')->filter();
        foreach ($approvers as $approver) {
            $this->sendNotification(
                $approver->id,
                'application_submitted',
                $title,
                $message,
                array_merge($data, ['approver_name' => $approver->name])
            );
        }
    }

    public function approvalRequested($approval)
    {
        $title = '承認依頼';
        $message = "申請「{$approval->application->title}」の承認依頼があります。\n申請者: {$approval->application->applicant->name}";
        
        $this->sendNotification(
            $approval->approver_id,
            'approval_requested',
            $title,
            $message,
            ['approval_id' => $approval->id, 'application_id' => $approval->application->id]
        );
    }

    public function applicationApproved($application)
    {
        $title = '申請が承認されました';
        $message = "申請「{$application->title}」が承認されました。";
        
        $this->sendNotification(
            $application->applicant_id,
            'application_approved',
            $title,
            $message,
            ['application_id' => $application->id]
        );
    }

    public function applicationRejected($application, $reason = null)
    {
        $title = '申請が却下されました';
        $message = "申請「{$application->title}」が却下されました。";
        if ($reason) {
            $message .= "\n理由: {$reason}";
        }
        
        $this->sendNotification(
            $application->applicant_id,
            'application_rejected',
            $title,
            $message,
            ['application_id' => $application->id, 'reason' => $reason]
        );
    }

    public function stepProcessed($approval, $action)
    {
        $actionLabels = [
            'approved' => '承認',
            'rejected' => '却下',
            'skipped' => 'スキップ'
        ];
        
        $title = "ステップが{$actionLabels[$action]}されました";
        $message = "申請「{$approval->application->title}」のステップ{$approval->step_number}が{$actionLabels[$action]}されました。";
        
        // 申請者に通知
        $this->sendNotification(
            $approval->application->applicant_id,
            "step_{$action}",
            $title,
            $message,
            ['approval_id' => $approval->id, 'application_id' => $approval->application->id]
        );
    }

    public function getUnreadCount($userId)
    {
        return NotificationLog::forUser($userId)
            ->ofType('database')
            ->unread()
            ->count();
    }

    public function markAsRead($userId, $notificationId = null)
    {
        $query = NotificationLog::forUser($userId)->ofType('database');

        if ($notificationId) {
            $query->where('id', $notificationId);
        }

        $readCount = $query->unread()->update(['read_at' => now()]);

        // New Relicメトリクス記録
        if ($notificationId) {
        }

        return $readCount;
    }

    /**
     * 通知メトリクスをNew Relicに記録
     */
    protected function recordNotificationMetrics(string $type, ?string $channel, string $eventType, User $user): void
    {
        // New Relic記録メソッドは削除済み
    }

    /**
     * 通知送信結果をNew Relicに記録
     */
    protected function recordNotificationResult(bool $result, string $channel, string $eventType): void
    {
        // New Relic記録メソッドは削除済み
    }
}