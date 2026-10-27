<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canSubmitPosts() || $user->isStaffReviewer();
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($user->isAdmin() || $user->isPair()) {
            return true;
        }

        if ($user->isDepartment()) {
            return (int) $submission->user_id === (int) $user->id
                || ($user->department_id !== null
                    && (int) $submission->review_department_id === (int) $user->department_id);
        }

        return $user->canSubmitPosts() && (int) $submission->user_id === (int) $user->id;
    }

    /**
     * PAIR role is explicitly granted create permission per panel feedback §2.2.
     * Department accounts must additionally be approved before posting.
     */
    public function create(User $user): bool
    {
        if ($user->isPair()) {
            return true;
        }

        if ($user->isDepartment()) {
            return $user->account_status === 'approved';
        }

        return $user->canSubmitPosts();
    }

    public function update(User $user, Submission $submission): bool
    {
        return $this->view($user, $submission);
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $this->view($user, $submission);
    }

    public function transition(User $user, Submission $submission): bool
    {
        return $this->reviewAsStaff($user, $submission);
    }

    public function reviewAsStaff(User $user, Submission $submission): bool
    {
        if ($user->isDepartment()) {
            return $user->department_id !== null
                && (int) $submission->review_department_id === (int) $user->department_id;
        }

        return ($user->isAdmin() || $user->isPair()) && $this->view($user, $submission);
    }

    public function reviewAsOrg(User $user, Submission $submission): bool
    {
        return $user->isOrg() && (int) $submission->user_id === (int) $user->id;
    }
}
