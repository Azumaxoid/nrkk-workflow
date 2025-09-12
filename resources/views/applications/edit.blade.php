@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-edit me-2"></i>申請編集</h2>
                <a href="{{ route('applications.show', $application) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>戻る
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-edit me-2"></i>申請内容を編集</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('applications.update', $application) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">申請タイトル <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $application->title) }}" 
                                   required>
                            @error('title')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">申請内容 <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="8" 
                                      required 
                                      placeholder="申請の詳細を記入してください">{{ old('description', $application->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                申請理由、詳細、必要な情報を詳しく記入してください。
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">申請カテゴリー</label>
                            <select class="form-select @error('type') is-invalid @enderror" 
                                    id="type" 
                                    name="type">
                                <option value="">カテゴリーを選択してください</option>
                                <option value="leave" {{ old('type', $application->type) === 'leave' ? 'selected' : '' }}>休暇申請</option>
                                <option value="expense" {{ old('type', $application->type) === 'expense' ? 'selected' : '' }}>経費申請</option>
                                <option value="purchase" {{ old('type', $application->type) === 'purchase' ? 'selected' : '' }}>購入申請</option>
                                <option value="travel" {{ old('type', $application->type) === 'travel' ? 'selected' : '' }}>出張申請</option>
                                <option value="other" {{ old('type', $application->type) === 'other' ? 'selected' : '' }}>その他</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">優先度</label>
                                    <select class="form-select @error('priority') is-invalid @enderror" 
                                            id="priority" 
                                            name="priority">
                                        <option value="normal" {{ old('priority', $application->priority) === 'normal' ? 'selected' : '' }}>通常</option>
                                        <option value="high" {{ old('priority', $application->priority) === 'high' ? 'selected' : '' }}>高</option>
                                        <option value="urgent" {{ old('priority', $application->priority) === 'urgent' ? 'selected' : '' }}>緊急</option>
                                    </select>
                                    @error('priority')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">希望処理日</label>
                                    <input type="date" 
                                           class="form-control @error('due_date') is-invalid @enderror" 
                                           id="due_date" 
                                           name="due_date" 
                                           value="{{ old('due_date', $application->due_date?->format('Y-m-d')) }}">
                                    @error('due_date')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('applications.show', $application) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>キャンセル
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>更新する
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 現在のステータス情報 -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>申請情報</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <strong>申請ID:</strong> {{ $application->id }}
                        </div>
                        <div class="col-sm-6">
                            <strong>ステータス:</strong>
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
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-sm-6">
                            <strong>作成日:</strong> {{ $application->created_at->format('Y年m月d日 H:i') }}
                        </div>
                        <div class="col-sm-6">
                            <strong>最終更新:</strong> {{ $application->updated_at->format('Y年m月d日 H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection