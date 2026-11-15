<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class Utils
{
    /**
     * Get the name of the Super Admin role.
     */
    public static function getSuperAdminName(): string
    {
        return config('filament-shield.super_admin.name', 'super_admin');
    }

    /**
     * Cached navigation badge count so every sidebar render
     * does not fire one COUNT query per resource.
     */
    public static function navigationBadge(string $key, callable $count, int $ttl = 120): string
    {
        return (string) Cache::remember(
            'nav-badge:'.$key,
            $ttl,
            fn (): int => (int) $count()
        );
    }

    // You can add more utility methods as needed
}
