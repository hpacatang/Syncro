<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Org: own submissions only. Admin/PAIR: all submissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOrg() || $user->isAdmin() || $user->isPair();
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($user->isAdmin() || $user->isPair()) {
            return true;
        }

        return $user->isOrg() && (int) $submission->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOrg();
    }

    public function update(User $user, Submission $submission): bool
    {
        return $this->view($user, $submission);
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $this->view($user, $submission);
    }

    /**
     * Manual lifecycle transitions (evaluator dashboard).
     */
    public function transition(User $user, Submission $submission): bool
    {
        return ($user->isAdmin() || $user->isPair()) && $this->view($user, $submission);
    }

    /**
     * PAIR enhance / staff approve endpoints.
     */
    public function reviewAsStaff(User $user, Submission $submission): bool
    {
        return ($user->isAdmin() || $user->isPair()) && $this->view($user, $submission);
    }

    /**
     * Org approve / reject enhanced caption.
     */
    public function reviewAsOrg(User $user, Submission $submission): bool
    {
        return false;
    }
}
