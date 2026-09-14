<?php

namespace App\Helpers;

class Utils
{
    /**
     * Get the name of the Super Admin role.
     *
     * @return string
     */
    public static function getSuperAdminName(): string
    {
        return config('filament-shield.super_admin.name', 'super_admin');
    }

    // You can add more utility methods as needed
}
