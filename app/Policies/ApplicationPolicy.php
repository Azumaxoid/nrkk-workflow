<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApplicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Application $application)
    {
        // 管理者は全て閲覧可能
        if ($user->isAdmin()) {
            return true;
        }
        
        // 申請者本人は閲覧可能
        if ($user->id === $application->applicant_id) {
            return true;
        }
        
        // 承認者は同じ組織の申請のみ閲覧可能
        if ($user->isApprover() || $user->isReviewer()) {
            return $user->organization_id === $application->applicant->organization_id;
        }
        
        return false;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Application $application)
    {
        return $user->id === $application->applicant_id;
    }

    public function delete(User $user, Application $application)
    {
        return $user->id === $application->applicant_id && $application->isDraft();
    }

    public function submit(User $user, Application $application)
    {
        return $user->id === $application->applicant_id && $application->canBeSubmitted();
    }

    public function cancel(User $user, Application $application)
    {
        return $user->id === $application->applicant_id && $application->canBeCancelled();
    }

    private function canUserApprove(User $user, Application $application)
    {
        return $application->approvals()
            ->where('approver_id', $user->id)
            ->exists();
    }
}