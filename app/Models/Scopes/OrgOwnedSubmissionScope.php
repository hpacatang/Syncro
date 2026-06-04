<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Org and department users only see their own submissions.
 * PAIR and admin reviewers see the full queue.
 */
class OrgOwnedSubmissionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if ($user && $user->canSubmitPosts()) {
            $builder->where($model->getTable().'.user_id', $user->id);
        }
    }
}
