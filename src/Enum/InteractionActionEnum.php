<?php

namespace App\Enum;

enum InteractionActionEnum: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
}
