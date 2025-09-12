@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-cog me-2"></i>通知設定</h2>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('notifications.update-settings') }}">
                @csrf

                <!-- Slack設定 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fab fa-slack me-2"></i>Slack設定</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="slack_webhook_url" class="form-label">
                                Slack Webhook URL
                                <small class="text-muted">（Slack通知を受け取るためのWebhook URLを設定してください）</small>
                            </label>
                            <input type="url" 
                                   class="form-control @error('slack_webhook_url') is-invalid @enderror" 
                                   id="slack_webhook_url" 
                                   name="slack_webhook_url" 
                                   value="{{ old('slack_webhook_url', $user->slack_webhook_url) }}"
                                   placeholder="https://hooks.slack.com/services/...">
                            @error('slack_webhook_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Slack Webhookの設定方法は<a href="https://api.slack.com/messaging/webhooks" target="_blank">こちら</a>をご確認ください。
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 通知イベント設定 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i>通知イベント設定</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">各イベントで受け取りたい通知方法を選択してください。</p>

                        @foreach($eventTypes as $eventType => $label)
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>{{ $label }}</strong>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="enabled_{{ $eventType }}"
                                               name="settings[{{ $eventType }}][is_enabled]"
                                               value="1"
                                               {{ $settings[$eventType]->is_enabled ? 'checked' : '' }}>
                                        <label class="form-check-label" for="enabled_{{ $eventType }}">
                                            有効
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($channels as $channel => $channelLabel)
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="{{ $eventType }}_{{ $channel }}"
                                                   name="settings[{{ $eventType }}][channels][]"
                                                   value="{{ $channel }}"
                                                   {{ $settings[$eventType]->hasChannel($channel) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $eventType }}_{{ $channel }}">
                                                <i class="fas {{ $channel === 'email' ? 'fa-envelope' : ($channel === 'slack' ? 'fa-slack' : 'fa-bell') }} me-1"></i>
                                                {{ $channelLabel }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- テスト通知 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-vial me-2"></i>通知テスト</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">設定が正しく動作するかテスト通知を送信できます。</p>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="testNotification('email')">
                                <i class="fas fa-envelope me-1"></i>メールテスト
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="testNotification('slack')">
                                <i class="fab fa-slack me-1"></i>Slackテスト
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="testNotification('database')">
                                <i class="fas fa-bell me-1"></i>アプリ内通知テスト
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>戻る
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>設定を保存
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- テスト通知用のフォーム -->
<form id="testNotificationForm" method="POST" action="{{ route('notifications.test') }}" style="display: none;">
    @csrf
    <input type="hidden" name="type" id="testType">
</form>

<script>
function testNotification(type) {
    if (confirm(`${type}通知のテストを送信しますか？`)) {
        document.getElementById('testType').value = type;
        document.getElementById('testNotificationForm').submit();
    }
}

// 有効/無効の切り替えに応じてチェックボックスを制御
document.querySelectorAll('[id^="enabled_"]').forEach(function(enabledSwitch) {
    enabledSwitch.addEventListener('change', function() {
        const eventType = this.id.replace('enabled_', '');
        const checkboxes = document.querySelectorAll(`[name="settings[${eventType}][channels][]"]`);
        
        checkboxes.forEach(function(checkbox) {
            checkbox.disabled = !enabledSwitch.checked;
            if (!enabledSwitch.checked) {
                checkbox.checked = false;
            }
        });
    });
    
    // 初期状態の設定
    enabledSwitch.dispatchEvent(new Event('change'));
});
</script>
@endsection