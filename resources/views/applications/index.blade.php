@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-file-alt me-2"></i>
                申請一覧
            </h2>
            @if(auth()->user()->isApplicant())
                <a href="{{ route('applications.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    新規申請
                </a>
            @endif
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('applications.index') }}">
                    <div class="row align-items-end">
                        <div class="col-md-3">
                            <label for="status" class="form-label">ステータス</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">すべて</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>下書き</option>
                                <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>提出済み</option>
                                <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>確認中</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>承認済み</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>却下</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="type" class="form-label">種別</label>
                            <select name="type" id="type" class="form-select">
                                <option value="">すべて</option>
                                <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>経費申請</option>
                                <option value="leave" {{ request('type') === 'leave' ? 'selected' : '' }}>休暇申請</option>
                                <option value="purchase" {{ request('type') === 'purchase' ? 'selected' : '' }}>購入申請</option>
                                <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>その他</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search me-1"></i>
                                検索
                            </button>
                            <a href="{{ route('applications.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                クリア
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($applications->count() > 0)
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>タイトル</th>
                                <th>種別</th>
                                <th>申請者</th>
                                <th>金額</th>
                                <th>ステータス</th>
                                <th>優先度</th>
                                <th>作成日</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $application)
                                <tr>
                                    <td>
                                        <a href="{{ route('applications.show', $application) }}" 
                                           class="text-decoration-none">
                                            {{ $application->title }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $application->type_label }}
                                        </span>
                                    </td>
                                    <td>{{ $application->applicant->name }}</td>
                                    <td>
                                        @if($application->amount)
                                            ¥{{ number_format($application->amount) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ 
                                            $application->status === 'approved' ? 'success' : 
                                            ($application->status === 'rejected' ? 'danger' : 
                                            ($application->status === 'draft' ? 'secondary' : 'warning')) 
                                        }}">
                                            {{ $application->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ 
                                            $application->priority === 'urgent' ? 'danger' : 
                                            ($application->priority === 'high' ? 'warning' : 
                                            ($application->priority === 'medium' ? 'info' : 'light')) 
                                        }}">
                                            {{ $application->priority_label }}
                                        </span>
                                    </td>
                                    <td>{{ $application->created_at->format('Y/m/d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('applications.show', $application) }}" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('update', $application)
                                                @if($application->canBeEdited())
                                                    <a href="{{ route('applications.edit', $application) }}" 
                                                       class="btn btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($applications->hasPages())
                <div class="card-footer">
                    {{ $applications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">申請が見つかりません</h4>
                <p class="text-muted">条件を変更して再度検索してください。</p>
                @if(auth()->user()->isApplicant())
                    <a href="{{ route('applications.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        新規申請を作成
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection