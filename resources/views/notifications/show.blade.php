@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-bell me-2"></i>通知詳細</h2>
                <a href="{{ route('notifications.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>通知一覧に戻る
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $notification->title }}</h5>
                        <div>
                            <span class="badge bg-{{ $notification->isSent() ? 'success' : ($notification->isFailed() ? 'danger' : 'warning') }}">
                                {{ $notification->status_label }}
                            </span>
                            <span class="badge bg-secondary">
                                {{ $notification->type_label }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">メッセージ内容</h6>
                        <div class="p-3 bg-light rounded">
                            {!! nl2br(e($notification->message)) !!}
                        </div>
                    </div>

                    @if($notification->data)
                    <div class="mb-4">
                        <h6 class="text-muted mb-2">詳細情報</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                @foreach($notification->data as $key => $value)
                                    @if(!is_array($value) && !is_object($value))
                                    <tr>
                                        <th width="30%">{{ ucfirst($key) }}:</th>
                                        <td>{{ $value }}</td>
                                    </tr>
                                    @endif
                                @endforeach
                            </table>
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">通知情報</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">イベントタイプ:</th>
                                    <td>{{ $notification->event_type }}</td>
                                </tr>
                                <tr>
                                    <th>通知方法:</th>
                                    <td>{{ $notification->type_label }}</td>
                                </tr>
                                <tr>
                                    <th>ステータス:</th>
                                    <td>{{ $notification->status_label }}</td>
                                </tr>
                                @if($notification->sent_at)
                                <tr>
                                    <th>送信日時:</th>
                                    <td>{{ $notification->sent_at->format('Y年m月d日 H:i') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">タイムスタンプ</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">作成日時:</th>
                                    <td>{{ $notification->created_at->format('Y年m月d日 H:i') }}</td>
                                </tr>
                                @if($notification->read_at)
                                <tr>
                                    <th>既読日時:</th>
                                    <td>{{ $notification->read_at->format('Y年m月d日 H:i') }}</td>
                                </tr>
                                @endif
                                @if($notification->error_message)
                                <tr>
                                    <th>エラー:</th>
                                    <td class="text-danger">{{ $notification->error_message }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($notification->data && isset($notification->data['application_id']))
                    <div class="mt-4">
                        <a href="{{ route('applications.show', $notification->data['application_id']) }}" 
                           class="btn btn-primary">
                            <i class="fas fa-external-link-alt me-1"></i>関連する申請を確認
                        </a>
                    </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>{{ $notification->time_ago }}
                        </small>
                        @if($notification->isUnread())
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="markAsRead()">
                                <i class="fas fa-check me-1"></i>既読にする
                            </button>
                        @else
                            <span class="text-success">
                                <i class="fas fa-check-circle me-1"></i>既読
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markAsRead() {
    fetch(`/notifications/mark-as-read/{{ $notification->id }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    }).then(() => {
        location.reload();
    });
}
</script>
@endsection