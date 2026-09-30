<?php

namespace App\Enums;

enum LeadPlatform: string
{
    case Website = 'website';
    case LinkedIn = 'linkedin';
    case Upwork = 'upwork';
    case Fiverr = 'fiverr';
    case Referral = 'referral';
    case Email = 'email';
    case Other = 'other';

    /**
     * The human-readable name shown to admins.
     */
    public function label(): string
    {
        return match ($this) {
            self::LinkedIn => 'LinkedIn',
            default => $this->name,
        };
    }
}
