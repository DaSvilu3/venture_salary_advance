<?php

namespace App\Enums;

enum Role: string
{
    case Employee = 'employee';
    case EmployerAdmin = 'employer_admin';
    case PlatformAdmin = 'platform_admin';
    case PartnerViewer = 'partner_viewer';

    /**
     * The named route of the portal home for this role.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Employee => 'app.home',
            self::EmployerAdmin => 'employer.dashboard',
            self::PlatformAdmin => 'admin.dashboard',
            self::PartnerViewer => 'partner.dashboard',
        };
    }
}
