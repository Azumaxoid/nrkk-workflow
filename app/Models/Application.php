<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\NotificationService;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'priority',
        'amount',
        'requested_date',
        'due_date',
        'attachments',
        'rejection_reason',
        'applicant_id',
        'submitted_at',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_date' => 'date',
        'due_date' => 'date',
        'attachments' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class)->orderBy('step_number');
    }

    public function approvalFlow()
    {
        return $this->belongsTo(ApprovalFlow::class, 'approval_flow_id');
    }

    public function currentApproval()
    {
        return $this->hasOne(Approval::class)->where('status', 'pending')->orderBy('step_number');
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isSubmitted()
    {
        return $this->status === 'submitted';
    }

    public function isUnderReview()
    {
        return $this->status === 'under_review';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['draft']);
    }

    public function canBeSubmitted()
    {
        return $this->status === 'draft';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['submitted', 'under_review']);
    }

    public function submit()
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        
        // 提出通知を送信
        $notificationService = app(NotificationService::class);
        $notificationService->applicationSubmitted($this);
    }

    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        
        // 承認通知を送信
        $notificationService = app(NotificationService::class);
        $notificationService->applicationApproved($this);
    }

    public function reject($reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
        
        // 却下通知を送信
        $notificationService = app(NotificationService::class);
        $notificationService->applicationRejected($this, $reason);
    }

    public function cancel()
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByApplicant($query, $applicantId)
    {
        return $query->where('applicant_id', $applicantId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'draft' => '下書き',
            'submitted' => '提出済み',
            'under_review' => '確認中',
            'approved' => '承認済み',
            'rejected' => '却下',
            'cancelled' => 'キャンセル',
        };
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'expense' => '経費申請',
            'leave' => '休暇申請',
            'purchase' => '購入申請',
            'other' => 'その他',
        };
    }

    public function getPriorityLabelAttribute()
    {
        return match($this->priority) {
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'urgent' => '緊急',
        };
    }
}