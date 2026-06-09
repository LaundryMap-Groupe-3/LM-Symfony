<?php

namespace App\Enum;

enum InteractionActionEnum: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case APPROVE = 'approve';
    case REJECT = 'reject';
    case SUSPEND = 'suspend';
    case UNSUSPEND = 'unsuspend';
    case BLOCK_CONTENT = 'block_content';
}
