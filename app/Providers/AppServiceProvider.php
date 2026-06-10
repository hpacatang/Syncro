<?php

namespace App\Providers;

use App\Models\Submission;
use App\Policies\SubmissionPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        
    }

    
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Gate::policy(Submission::class, SubmissionPolicy::class);
    }
}
