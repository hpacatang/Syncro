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
        if ($user->isStaffReviewer()) {
            return true;
        }

        return $user->canSubmitPosts() && (int) $submission->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
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
        return $user->isStaffReviewer() && $this->view($user, $submission);
    }

    public function reviewAsStaff(User $user, Submission $submission): bool
    {
        return $user->isStaffReviewer() && $this->view($user, $submission);
    }

    public function reviewAsOrg(User $user, Submission $submission): bool
    {
        return $user->isOrg() && (int) $submission->user_id === (int) $user->id;
    }
}
