<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Org users may only see submissions they own (user_id).
 * Admin and PAIR reviewers are not scoped — global queue access.
 */
class OrgOwnedSubmissionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if ($user && $user->isOrg()) {
            $builder->where($model->getTable().'.user_id', $user->id);
        }
    }
}
