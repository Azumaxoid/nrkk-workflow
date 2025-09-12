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
        return $user->isAdmin() || 
               $user->id === $application->applicant_id ||
               ($user->isReviewer() && $this->canUserApprove($user, $application));
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