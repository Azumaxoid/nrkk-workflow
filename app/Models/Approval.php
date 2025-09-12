<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\NotificationService;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'approval_flow_id',
        'approver_id',
        'step_number',
        'step_type',
        'status',
        'comment',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function approvalFlow()
    {
        return $this->belongsTo(ApprovalFlow::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isSkipped()
    {
        return $this->status === 'skipped';
    }

    public function isReviewStep()
    {
        return $this->step_type === 'review';
    }

    public function isApprovalStep()
    {
        return $this->step_type === 'approve';
    }

    public function approve($comment = null)
    {
        $this->update([
            'status' => 'approved',
            'comment' => $comment,
            'acted_at' => now(),
        ]);

        $this->checkApplicationStatus();
        
        // ステップ承認通知を送信
        $notificationService = app(NotificationService::class);
        $notificationService->stepProcessed($this, 'approved');
    }

    public function reject($comment = null)
    {
        $this->update([
            'status' => 'rejected',
            'comment' => $comment,
            'acted_at' => now(),
        ]);

        $this->application->reject($comment);
        
        // ステップ却下通知を送信
        $notificationService = app(NotificationService::class);
        $notificationService->stepProcessed($this, 'rejected');
    }

    public function skip($comment = null)
    {
        $this->update([
            'status' => 'skipped',
            'comment' => $comment,
            'acted_at' => now(),
        ]);

        $this->checkApplicationStatus();
        
        // ステップスキップ通知を送信
        $notificationService = app(NotificationService::class);
        $notificationService->stepProcessed($this, 'skipped');
    }

    protected function checkApplicationStatus()
    {
        $application = $this->application;
        $flow = $application->approvalFlow ?? ApprovalFlow::find($this->approval_flow_id);
        
        if (!$flow) {
            return;
        }

        $allApprovals = $application->approvals;
        $rejectedApprovals = $allApprovals->where('status', 'rejected');

        // 却下があった場合は処理終了
        if ($rejectedApprovals->count() > 0) {
            $application->update(['status' => 'rejected']);
            return;
        }

        // ステップごとに承認状況をチェック
        $groupedApprovals = $allApprovals->groupBy('step_number');
        $allStepsCompleted = true;

        foreach ($flow->flow_config as $stepIndex => $stepConfig) {
            $stepNumber = $stepIndex + 1;
            $approvalMode = $stepConfig['approval_mode'] ?? 'all';
            $stepApprovals = $groupedApprovals->get($stepNumber, collect());

            $approved = $stepApprovals->where('status', 'approved');
            $pending = $stepApprovals->where('status', 'pending');

            if ($approvalMode === 'any_one') {
                // 誰か一人が承認すればそのステップはクリア
                if ($approved->count() > 0) {
                    // 同じステップの他の承認をスキップ
                    $pending->each(function ($approval) {
                        $approval->update(['status' => 'skipped']);
                    });
                } else if ($pending->count() > 0) {
                    $allStepsCompleted = false;
                    break;
                }
            } else {
                // デフォルト: 全員の承認が必要
                if ($pending->count() > 0) {
                    $allStepsCompleted = false;
                    break;
                }
            }
        }

        if ($allStepsCompleted) {
            $application->approve();
        } else {
            $application->update(['status' => 'under_review']);
        }
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }

    public function scopeByApplication($query, $applicationId)
    {
        return $query->where('application_id', $applicationId);
    }

    public function scopeByStep($query, $stepNumber)
    {
        return $query->where('step_number', $stepNumber);
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => '待機中',
            'approved' => '承認',
            'rejected' => '却下',
            'skipped' => 'スキップ',
        };
    }

    public function getStepTypeLabelAttribute()
    {
        return match($this->step_type) {
            'review' => '確認',
            'approve' => '承認',
        };
    }
}