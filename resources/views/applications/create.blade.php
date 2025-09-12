@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('applications.index') }}" class="btn btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="mb-0">
                <i class="fas fa-plus-circle me-2"></i>
                新規申請作成
            </h2>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('applications.store') }}">
                    @csrf
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="title" class="form-label">申請タイトル <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}" 
                                   required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="type" class="form-label">申請種別 <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" 
                                    id="type" 
                                    name="type" 
                                    required>
                                <option value="">選択してください</option>
                                <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>経費申請</option>
                                <option value="leave" {{ old('type') === 'leave' ? 'selected' : '' }}>休暇申請</option>
                                <option value="purchase" {{ old('type') === 'purchase' ? 'selected' : '' }}>購入申請</option>
                                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>その他</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">申請内容 <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="4" 
                                  required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="amount" class="form-label">金額</label>
                            <div class="input-group">
                                <span class="input-group-text">¥</span>
                                <input type="number" 
                                       class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" 
                                       name="amount" 
                                       value="{{ old('amount') }}" 
                                       min="0" 
                                       step="0.01">
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="priority" class="form-label">優先度 <span class="text-danger">*</span></label>
                            <select class="form-select @error('priority') is-invalid @enderror" 
                                    id="priority" 
                                    name="priority" 
                                    required>
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>低</option>
                                <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>中</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>高</option>
                                <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>緊急</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="requested_date" class="form-label">希望日</label>
                            <input type="date" 
                                   class="form-control @error('requested_date') is-invalid @enderror" 
                                   id="requested_date" 
                                   name="requested_date" 
                                   value="{{ old('requested_date') }}">
                            @error('requested_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">期限</label>
                            <input type="date" 
                                   class="form-control @error('due_date') is-invalid @enderror" 
                                   id="due_date" 
                                   name="due_date" 
                                   value="{{ old('due_date') }}"
                                   min="{{ date('Y-m-d') }}">
                            @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="attachments" class="form-label">添付ファイル</label>
                        <input type="file" 
                               class="form-control @error('attachments') is-invalid @enderror" 
                               id="attachments" 
                               name="attachments[]" 
                               multiple>
                        <div class="form-text">
                            複数のファイルを選択できます。PDF, Word, Excel, 画像ファイル等
                        </div>
                        @error('attachments')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('applications.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>
                            キャンセル
                        </a>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                下書き保存
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    申請について
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6><i class="fas fa-user me-2"></i>申請者</h6>
                    <p class="mb-0">{{ auth()->user()->name }}</p>
                    <small class="text-muted">{{ auth()->user()->department ?? '部署未設定' }}</small>
                </div>
                
                <div class="mb-3">
                    <h6><i class="fas fa-route me-2"></i>承認フロー</h6>
                    <p class="text-muted small mb-0">
                        申請提出後、設定された承認フローに従って処理されます。
                    </p>
                </div>
                
                <div class="mb-0">
                    <h6><i class="fas fa-lightbulb me-2"></i>ヒント</h6>
                    <ul class="list-unstyled small text-muted">
                        <li><i class="fas fa-check-circle text-success me-1"></i> まず下書きとして保存</li>
                        <li><i class="fas fa-check-circle text-success me-1"></i> 内容を確認してから提出</li>
                        <li><i class="fas fa-check-circle text-success me-1"></i> 添付ファイルで詳細を補足</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection