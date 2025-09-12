@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-bell me-2"></i>通知一覧
                    @if($unreadCount > 0)
                        <span class="badge bg-danger">{{ $unreadCount }}</span>
                    @endif
                </h2>
                <div>
                    @if($unreadCount > 0)
                        <button type="button" class="btn btn-outline-primary me-2" onclick="markAllAsRead()">
                            <i class="fas fa-check me-1"></i>すべて既読にする
                        </button>
                    @endif
                    <a href="{{ route('notifications.settings') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-cog me-1"></i>通知設定
                    </a>
                </div>
            </div>

            @if($notifications->count() > 0)
                <div class="card">
                    <div class="card-body p-0">
                        @foreach($notifications as $notification)
                        <div class="notification-item border-bottom p-3 {{ $notification->isUnread() ? 'bg-light' : '' }}" 
                             onclick="markAsReadAndRedirect({{ $notification->id }})" 
                             style="cursor: pointer;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        @if($notification->isUnread())
                                            <span class="badge bg-primary me-2">NEW</span>
                                        @endif
                                        <h6 class="mb-0">{{ $notification->title }}</h6>
                                    </div>
                                    <p class="mb-2 text-muted">{{ Str::limit($notification->message, 100) }}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            {{ $notification->time_ago }}
                                        </small>
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
                                @if($notification->isUnread())
                                    <div class="ms-2">
                                        <span class="badge bg-danger rounded-circle" style="width: 10px; height: 10px;"></span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- ページネーション -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">通知はありません</h4>
                        <p class="text-muted">まだ通知が届いていません。</p>
                        <a href="{{ route('notifications.settings') }}" class="btn btn-outline-primary">
                            <i class="fas fa-cog me-1"></i>通知設定
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function markAsReadAndRedirect(notificationId) {
    // 既読マークを付けてから詳細ページに遷移
    fetch(`/notifications/mark-as-read/${notificationId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    }).then(() => {
        window.location.href = `/notifications/${notificationId}`;
    });
}

function markAllAsRead() {
    if (confirm('すべての通知を既読にしますか？')) {
        fetch('/notifications/mark-as-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        }).then(() => {
            location.reload();
        });
    }
}
</script>

<style>
.notification-item:hover {
    background-color: #f8f9fa !important;
}
</style>
@endsection