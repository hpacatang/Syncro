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

        if ($user && $user->isDepartment()) {
            if ($user->department_id) {
                $builder->where(function (Builder $query) use ($model, $user) {
                    $query->where($model->getTable().'.review_department_id', $user->department_id)
                        ->orWhere($model->getTable().'.user_id', $user->id);
                });
            } else {
                $builder->whereRaw('1 = 0');
            }
        } elseif ($user && $user->isOrg()) {
            $builder->where($model->getTable().'.user_id', $user->id);
        }
    }
}
