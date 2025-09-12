<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotificationSetting;
use App\Models\NotificationLog;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $notifications = auth()->user()
            ->notificationLogs()
            ->ofType('database')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $unreadCount = $this->notificationService->getUnreadCount(auth()->id());

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function settings()
    {
        $user = auth()->user();
        $settings = $user->notificationSettings()->get()->keyBy('event_type');
        
        // すべてのイベントタイプのデフォルト設定を作成
        foreach (NotificationSetting::EVENT_TYPES as $eventType => $label) {
            if (!isset($settings[$eventType])) {
                $setting = NotificationSetting::create([
                    'user_id' => $user->id,
                    'event_type' => $eventType,
                    'channels' => ['database', 'email'],
                    'is_enabled' => true,
                ]);
                $settings[$eventType] = $setting;
            }
        }

        return view('notifications.settings', [
            'settings' => $settings,
            'eventTypes' => NotificationSetting::EVENT_TYPES,
            'channels' => NotificationSetting::AVAILABLE_CHANNELS,
            'user' => $user,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'slack_webhook_url' => 'nullable|url',
            'settings' => 'array',
            'settings.*.channels' => 'array',
            'settings.*.is_enabled' => 'boolean',
        ]);

        $user = auth()->user();
        
        // Slack Webhook URLの更新
        if ($request->has('slack_webhook_url')) {
            $user->update([
                'slack_webhook_url' => $request->slack_webhook_url
            ]);
        }

        // 通知設定の更新
        if ($request->has('settings')) {
            foreach ($request->settings as $eventType => $settingData) {
                NotificationSetting::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'event_type' => $eventType,
                    ],
                    [
                        'channels' => $settingData['channels'] ?? [],
                        'is_enabled' => $settingData['is_enabled'] ?? false,
                    ]
                );
            }
        }

        return redirect()->route('notifications.settings')
            ->with('success', '通知設定を更新しました。');
    }

    public function markAsRead(Request $request, $id = null)
    {
        if ($id) {
            $this->notificationService->markAsRead(auth()->id(), $id);
        } else {
            $this->notificationService->markAsRead(auth()->id());
        }

        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $notification = NotificationLog::where('user_id', auth()->id())
            ->findOrFail($id);

        $notification->markAsRead();

        return view('notifications.show', compact('notification'));
    }

    public function testNotification(Request $request)
    {
        $request->validate([
            'type' => 'required|in:email,slack,database',
        ]);

        $user = auth()->user();
        $type = $request->type;
        $title = 'テスト通知';
        $message = 'これはテスト通知です。通知設定が正しく動作しています。';

        // 特定のチャンネルだけでテスト送信
        $result = $this->notificationService->sendNotificationToChannel(
            $user,
            $type,
            'test_notification',
            $title,
            $message,
            ['test' => true]
        );

        if ($result) {
            return redirect()->back()->with('success', 'テスト通知を送信しました。');
        } else {
            return redirect()->back()->with('error', 'テスト通知の送信に失敗しました。');
        }
    }
}