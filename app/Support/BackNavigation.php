<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class BackNavigation
{
    private const HOME_ROUTES = [
        'dashboard',
        'org.dashboard',
        'login',
        'register',
        'authenticate',
    ];

    public static function shouldShow(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $name = request()->route()?->getName();

        return $name && ! in_array($name, self::HOME_ROUTES, true);
    }

    /**
     * @return array{href: string, label: string}
     */
    public static function resolve(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $route = request()->route()?->getName() ?? '';
        $dashboard = $user?->isOrg() ? route('org.dashboard') : route('dashboard');

        $map = [
            'dashboard.submissions.review' => [
                'href' => route('dashboard.submissions'),
                'label' => 'Back to submissions',
            ],
            'org.submissions.review' => [
                'href' => route('org.submissions'),
                'label' => 'Back to submissions',
            ],
            'dashboard.submissions' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'org.submissions' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'org.submit' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'dashboard.notifications' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'org.notifications' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'staff.caption-assist' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'staff.media-gallery' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'settings.tone' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'users.index' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'users.create' => [
                'href' => route('users.index'),
                'label' => 'Back to users',
            ],
            'users.edit' => [
                'href' => route('users.index'),
                'label' => 'Back to users',
            ],
            'users.show' => [
                'href' => route('users.index'),
                'label' => 'Back to users',
            ],
            'audit-logs.index' => [
                'href' => $dashboard,
                'label' => 'Back to dashboard',
            ],
            'audit-logs.show' => [
                'href' => route('audit-logs.index'),
                'label' => 'Back to audit logs',
            ],
        ];

        if (isset($map[$route])) {
            return $map[$route];
        }

        $previous = url()->previous();
        $current = url()->current();

        if (
            $previous !== $current
            && Str::startsWith($previous, url('/'))
            && ! Str::contains($previous, ['/login', '/register', '/authenticate'])
        ) {
            return ['href' => $previous, 'label' => 'Back'];
        }

        return ['href' => $dashboard, 'label' => 'Back to dashboard'];
    }
}
