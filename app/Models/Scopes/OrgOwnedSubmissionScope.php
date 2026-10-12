<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrgOwnedSubmissionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if ($user && $user->canSubmitPosts()) {
            $builder->where($model->getTable().'.user_id', $user->id);
        } elseif ($user && $user->isDepartment()) {
            if ($user->department_id) {
                $builder->where($model->getTable().'.review_department_id', $user->department_id);
            } else {
                $builder->whereRaw('1 = 0');
            }
        }
    }
}
