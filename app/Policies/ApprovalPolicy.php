<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalPolicy
{
    use HandlesAuthorization;

    public function act(User $user, Approval $approval)
    {
        return $user->id === $approval->approver_id && 
               $approval->isPending() && 
               $user->is_active;
    }
}