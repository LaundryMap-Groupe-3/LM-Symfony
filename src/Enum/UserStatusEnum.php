<?php

namespace App\Enum;

enum UserStatusEnum: string
{
    case VERIFIED = 'verified';
    case SUSPENDED = 'suspended';
    case PENDING = 'pending';
}
