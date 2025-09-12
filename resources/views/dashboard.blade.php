@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-tachometer-alt me-2"></i>
                ダッシュボード
            </h2>
            <div>
                @if(auth()->user()->isApplicant())
                    <a href="{{ route('applications.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        新規申請
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    @if(auth()->user()->isApplicant())
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">申請総数</h5>
                            <h3>{{ $stats['my_applications'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-file-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">下書き</h5>
                            <h3>{{ $stats['draft_applications'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-edit fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">審査中</h5>
                            <h3>{{ $stats['pending_applications'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @if(auth()->user()->isReviewer())
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">承認待ち</h5>
                            <h3>{{ $stats['pending_approvals'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tasks fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">承認済み</h5>
                            <h3>{{ $stats['my_approvals_count'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    @if(auth()->user()->isAdmin())
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">全申請数</h5>
                            <h3>{{ $stats['total_applications'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-bar fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="row">
    @if(auth()->user()->isApplicant() && $recentApplications->count() > 0)
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        最近の申請
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($recentApplications as $application)
                            <a href="{{ route('applications.show', $application) }}" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $application->title }}</h6>
                                        <p class="mb-1 text-muted small">{{ $application->type_label }}</p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ $application->status_label }}
                                        </span>
                                        <small class="text-muted d-block">
                                            {{ $application->created_at->format('m/d H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('applications.index') }}" class="btn btn-sm btn-outline-primary">
                        すべて見る
                    </a>
                </div>
            </div>
        </div>
    @endif
    
    @if(auth()->user()->isReviewer() && $pendingApprovals->count() > 0)
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        承認待ち
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($pendingApprovals as $approval)
                            <a href="{{ route('applications.show', $approval->application) }}" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $approval->application->title }}</h6>
                                        <p class="mb-1 text-muted small">
                                            申請者: {{ $approval->application->applicant->name }}
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-warning">
                                            {{ $approval->step_type_label }}待ち
                                        </span>
                                        <small class="text-muted d-block">
                                            {{ $approval->created_at->format('m/d H:i') }}
                                        </small>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('applications.my-approvals') }}" class="btn btn-sm btn-outline-primary">
                        すべて見る
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

@if(empty($recentApplications) || $recentApplications->count() === 0)
@if(empty($pendingApprovals) || $pendingApprovals->count() === 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">ようこそ！</h4>
                <p class="text-muted">
                    @if(auth()->user()->isApplicant())
                        新しい申請を作成してワークフローを開始しましょう。
                    @else
                        承認待ちの申請が表示されます。
                    @endif
                </p>
                @if(auth()->user()->isApplicant())
                    <a href="{{ route('applications.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        初回申請を作成
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endif
@endsection