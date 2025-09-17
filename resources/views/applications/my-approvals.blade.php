@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tasks me-2"></i>承認待ち一覧</h2>
                <div class="d-flex align-items-center">
                    <div id="bulkActions" style="display: none;" class="me-2">
                        <button type="button" class="btn btn-success" id="bulkApproveBtn" onclick="bulkApprove()">
                            <i class="fas fa-check-double me-1"></i>選択を一括承認
                        </button>
                        <button type="button" class="btn btn-danger" id="bulkRejectBtn" onclick="bulkReject()">
                            <i class="fas fa-times-circle me-1"></i>選択を一括却下
                        </button>
                    </div>
                    @if($approvals->count() > 0)
                    <div class="btn-group">
                        <button type="button" class="btn btn-success btn-sm" id="approveAllBtn" onclick="approveAll()">
                            <i class="fas fa-check-circle me-1"></i>全て承認
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" id="rejectAllBtn" onclick="rejectAll()">
                            <i class="fas fa-ban me-1"></i>全て却下
                        </button>
                    </div>
                    @endif
                </div>
            </div>

            @if($approvals->count() > 0)
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        <label class="form-check-label" for="selectAll">
                            すべて選択
                        </label>
                    </div>
                </div>
                <div class="row">
                    @foreach($approvals as $approval)
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-2" type="checkbox" value="{{ $approval->id }}"
                                           id="approval_{{ $approval->id }}" onchange="updateBulkActions()">
                                    <h6 class="mb-0">
                                        <i class="fas fa-file-alt me-2"></i>
                                        {{ $approval->application->title }}
                                    </h6>
                                </div>
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

                                <div class="mb-3">
                                    <small class="text-muted">承認フロー:</small>
                                    <div>{{ $approval->approvalFlow->name ?? '承認フロー不明' }}</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('applications.show', $approval->application) }}"
                                       class="btn btn-outline-primary btn-sm" id="viewDetailsBtn_{{ $approval->id }}">
                                        <i class="fas fa-eye me-1"></i>詳細を見る
                                    </a>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-success btn-sm"
                                                id="approveBtn_{{ $approval->id }}" onclick="showApprovalModal({{ $approval->id }}, 'approve')">
                                            <i class="fas fa-check me-1"></i>承認
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm"
                                                id="rejectBtn_{{ $approval->id }}" onclick="showApprovalModal({{ $approval->id }}, 'reject')">
                                            <i class="fas fa-times me-1"></i>却下
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm"
                                                id="skipBtn_{{ $approval->id }}" onclick="showApprovalModal({{ $approval->id }}, 'skip')">
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
                <button type="button" class="btn-close" id="approvalModalCloseBtn" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn btn-secondary" id="approvalCancelBtn" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" id="approvalSubmit" class="btn btn-primary">実行</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 一括承認モーダル -->
<div class="modal fade" id="bulkApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkApprovalModalTitle">一括処理</h5>
                <button type="button" class="btn-close" id="bulkApprovalModalCloseBtn" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkApprovalForm" method="POST">
                @csrf
                <input type="hidden" name="approval_ids[]" id="bulkApprovalIds">
                <div class="modal-body">
                    <p id="bulkApprovalMessage"></p>
                    <div class="mb-3">
                        <label for="bulkComment" class="form-label">コメント</label>
                        <textarea name="comment" id="bulkComment" class="form-control" rows="3"
                                placeholder="承認・却下の理由やコメントを入力してください"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="bulkApprovalCancelBtn" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" id="bulkApprovalSubmit" class="btn btn-primary">実行</button>
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

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[type="checkbox"][id^="approval_"]');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });

    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][id^="approval_"]:checked');
    const bulkActions = document.getElementById('bulkActions');

    if (checkboxes.length > 0) {
        bulkActions.style.display = 'block';
    } else {
        bulkActions.style.display = 'none';
    }
}

function getSelectedApprovalIds() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"][id^="approval_"]:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function bulkApprove() {
    const ids = getSelectedApprovalIds();
    if (ids.length === 0) {
        alert('承認する項目を選択してください。');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('bulkApprovalModal'));
    const form = document.getElementById('bulkApprovalForm');
    const title = document.getElementById('bulkApprovalModalTitle');
    const message = document.getElementById('bulkApprovalMessage');
    const submitBtn = document.getElementById('bulkApprovalSubmit');
    const commentField = document.getElementById('bulkComment');

    title.textContent = '一括承認';
    message.textContent = `${ids.length}件の申請を一括承認しますか？`;
    submitBtn.textContent = '一括承認する';
    submitBtn.className = 'btn btn-success';
    commentField.required = false;

    form.action = '{{ route("approvals.bulk-approve") }}';

    // Clear existing hidden inputs
    const existingInputs = form.querySelectorAll('input[name="approval_ids[]"]');
    existingInputs.forEach(input => input.remove());

    // Add selected IDs as hidden inputs
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'approval_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    modal.show();
}

function bulkReject() {
    const ids = getSelectedApprovalIds();
    if (ids.length === 0) {
        alert('却下する項目を選択してください。');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('bulkApprovalModal'));
    const form = document.getElementById('bulkApprovalForm');
    const title = document.getElementById('bulkApprovalModalTitle');
    const message = document.getElementById('bulkApprovalMessage');
    const submitBtn = document.getElementById('bulkApprovalSubmit');
    const commentField = document.getElementById('bulkComment');

    title.textContent = '一括却下';
    message.textContent = `${ids.length}件の申請を一括却下しますか？`;
    submitBtn.textContent = '一括却下する';
    submitBtn.className = 'btn btn-danger';
    commentField.required = true;

    form.action = '{{ route("approvals.bulk-reject") }}';

    // Clear existing hidden inputs
    const existingInputs = form.querySelectorAll('input[name="approval_ids[]"]');
    existingInputs.forEach(input => input.remove());

    // Add selected IDs as hidden inputs
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'approval_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    modal.show();
}

function approveAll() {
    const modal = new bootstrap.Modal(document.getElementById('bulkApprovalModal'));
    const form = document.getElementById('bulkApprovalForm');
    const title = document.getElementById('bulkApprovalModalTitle');
    const message = document.getElementById('bulkApprovalMessage');
    const submitBtn = document.getElementById('bulkApprovalSubmit');
    const commentField = document.getElementById('bulkComment');

    title.textContent = '全て承認';
    message.textContent = '承認待ちの全ての申請を承認します。コメントを入力してください（任意）。';
    submitBtn.textContent = '全て承認する';
    submitBtn.className = 'btn btn-success';
    commentField.required = false;

    form.action = '{{ route("approvals.approve-all") }}';

    // Clear existing hidden inputs
    const existingInputs = form.querySelectorAll('input[name="approval_ids[]"]');
    existingInputs.forEach(input => input.remove());

    modal.show();
}

function rejectAll() {
    const modal = new bootstrap.Modal(document.getElementById('bulkApprovalModal'));
    const form = document.getElementById('bulkApprovalForm');
    const title = document.getElementById('bulkApprovalModalTitle');
    const message = document.getElementById('bulkApprovalMessage');
    const submitBtn = document.getElementById('bulkApprovalSubmit');
    const commentField = document.getElementById('bulkComment');

    title.textContent = '全て却下';
    message.textContent = '承認待ちの全ての申請を却下します。却下理由を入力してください（必須）。';
    submitBtn.textContent = '全て却下する';
    submitBtn.className = 'btn btn-danger';
    commentField.required = true;

    form.action = '{{ route("approvals.reject-all") }}';

    // Clear existing hidden inputs
    const existingInputs = form.querySelectorAll('input[name="approval_ids[]"]');
    existingInputs.forEach(input => input.remove());

    modal.show();
}
</script>
@endsection