@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-file-alt me-2"></i>申請詳細</h2>
                <a href="{{ route('applications.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>戻る
                </a>
            </div>

            <div class="row">
                <!-- 申請内容 -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>申請内容</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">申請ID:</th>
                                    <td>{{ $application->id }}</td>
                                </tr>
                                <tr>
                                    <th>タイトル:</th>
                                    <td>{{ $application->title }}</td>
                                </tr>
                                <tr>
                                    <th>申請者:</th>
                                    <td>{{ $application->applicant->name }} ({{ $application->applicant->email }})</td>
                                </tr>
                                <tr>
                                    <th>ステータス:</th>
                                    <td>
                                        @php
                                            $statusClass = [
                                                'draft' => 'secondary',
                                                'submitted' => 'primary',
                                                'under_review' => 'warning',
                                                'reviewing' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'cancelled' => 'dark'
                                            ];
                                            $statusText = [
                                                'draft' => '下書き',
                                                'submitted' => '提出済み',
                                                'under_review' => '確認中',
                                                'reviewing' => '確認中',
                                                'approved' => '承認済み',
                                                'rejected' => '却下',
                                                'cancelled' => 'キャンセル'
                                            ];
                                        @endphp
                                        <span class="badge bg-{{ $statusClass[$application->status] }}">
                                            {{ $statusText[$application->status] }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>作成日:</th>
                                    <td>{{ $application->created_at->format('Y年m月d日 H:i') }}</td>
                                </tr>
                                @if($application->submitted_at)
                                <tr>
                                    <th>提出日:</th>
                                    <td>{{ $application->submitted_at->format('Y年m月d日 H:i') }}</td>
                                </tr>
                                @endif
                            </table>

                            <div class="mt-4">
                                <h6><strong>詳細:</strong></h6>
                                <div class="p-3 bg-light rounded">
                                    {!! nl2br(e($application->description)) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- アクションボタン -->
                    @canany(['update', 'submit', 'cancel'], $application)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>アクション</h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group" role="group">
                                @can('update', $application)
                                    <a href="{{ route('applications.edit', $application) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-edit me-2"></i>編集
                                    </a>
                                @endcan

                                @can('submit', $application)
                                    <form method="POST" action="{{ route('applications.submit', $application) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success" onclick="return confirm('この申請を提出しますか？')">
                                            <i class="fas fa-paper-plane me-2"></i>提出
                                        </button>
                                    </form>
                                @endcan

                                @can('cancel', $application)
                                    <form method="POST" action="{{ route('applications.cancel', $application) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('この申請をキャンセルしますか？')">
                                            <i class="fas fa-times me-2"></i>キャンセル
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                    @endcanany
                </div>

                <!-- 承認フロー -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-route me-2"></i>承認フロー</h5>
                        </div>
                        <div class="card-body">
                            @if($application->approvals->count() > 0)
                                <div class="approval-flow">
                                    @foreach($application->approvals->sortBy('step') as $approval)
                                    <div class="approval-step mb-3 p-3 rounded 
                                        @if($approval->status === 'approved') bg-success-subtle
                                        @elseif($approval->status === 'rejected') bg-danger-subtle
                                        @elseif($approval->status === 'pending') bg-warning-subtle
                                        @else bg-light @endif">
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="fw-bold">ステップ {{ $approval->step_number }}</small>
                                            <span class="badge 
                                                @if($approval->status === 'approved') bg-success
                                                @elseif($approval->status === 'rejected') bg-danger
                                                @elseif($approval->status === 'pending') bg-warning
                                                @else bg-secondary @endif">
                                                @switch($approval->status)
                                                    @case('pending') 承認待ち @break
                                                    @case('approved') 承認済み @break
                                                    @case('rejected') 却下 @break
                                                    @case('skipped') スキップ @break
                                                    @default 未処理
                                                @endswitch
                                            </span>
                                        </div>

                                        <div class="mb-2">
                                            <strong>{{ $approval->user?->name ?? '承認者不明' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $approval->user?->email ?? 'メールアドレス不明' }}</small>
                                        </div>

                                        @if($approval->processed_at)
                                            <small class="text-muted d-block">
                                                {{ $approval->processed_at->format('m月d日 H:i') }} 処理済み
                                            </small>
                                        @endif

                                        @if($approval->comment)
                                            <div class="mt-2 p-2 bg-white rounded">
                                                <small>{{ $approval->comment }}</small>
                                            </div>
                                        @endif

                                        <!-- 承認アクション -->
                                        @if($approval->status === 'pending' && auth()->id() === $approval->approver_id && $approval->user)
                                            <div class="mt-3">
                                                <div class="btn-group-vertical w-100" role="group">
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="showApprovalModal({{ $approval->id }}, 'approve')">
                                                        <i class="fas fa-check me-1"></i>承認
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="showApprovalModal({{ $approval->id }}, 'reject')">
                                                        <i class="fas fa-times me-1"></i>却下
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm" 
                                                            onclick="showApprovalModal({{ $approval->id }}, 'skip')">
                                                        <i class="fas fa-forward me-1"></i>スキップ
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">承認フローが設定されていません。</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 承認アクションモーダル -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalTitle">承認処理</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approvalForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="comment" class="form-label">コメント</label>
                        <textarea name="comment" id="comment" class="form-control" rows="3" 
                                placeholder="承認・却下の理由やコメントを入力してください（任意）"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" id="approvalSubmit" class="btn btn-primary">実行</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showApprovalModal(approvalId, action) {
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
    const form = document.getElementById('approvalForm');
    const title = document.getElementById('approvalModalTitle');
    const submitBtn = document.getElementById('approvalSubmit');
    
    // フォームのアクションを設定
    form.action = `/approvals/${approvalId}/${action}`;
    
    // タイトルとボタンの表示を設定
    switch(action) {
        case 'approve':
            title.textContent = '承認処理';
            submitBtn.textContent = '承認する';
            submitBtn.className = 'btn btn-success';
            break;
        case 'reject':
            title.textContent = '却下処理';
            submitBtn.textContent = '却下する';
            submitBtn.className = 'btn btn-danger';
            break;
        case 'skip':
            title.textContent = 'スキップ処理';
            submitBtn.textContent = 'スキップする';
            submitBtn.className = 'btn btn-secondary';
            break;
    }
    
    modal.show();
}
</script>
@endsection