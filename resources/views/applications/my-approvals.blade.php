@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tasks me-2"></i>承認待ち一覧</h2>
            </div>

            @if($approvals->count() > 0)
                <div class="row">
                    @foreach($approvals as $approval)
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-file-alt me-2"></i>
                                    {{ $approval->application->title }}
                                </h6>
                                <span class="badge bg-warning">ステップ {{ $approval->step_number }}</span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">申請者:</small>
                                    <div>{{ $approval->application->applicant?->name ?? '申請者不明' }}</div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">申請内容:</small>
                                    <div class="text-truncate">
                                        {{ Str::limit($approval->application->description, 100) }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted">提出日:</small>
                                    <div>{{ $approval->application->submitted_at?->format('Y年m月d日 H:i') ?? '未提出' }}</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('applications.show', $approval->application) }}" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>詳細を見る
                                    </a>
                                    <div class="btn-group" role="group">
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
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- ページネーション -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $approvals->links() }}
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">承認待ちの申請はありません</h4>
                        <p class="text-muted">現在、あなたの承認待ちの申請はありません。</p>
                    </div>
                </div>
            @endif
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