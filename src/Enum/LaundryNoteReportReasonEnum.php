<?php

namespace App\Enum;

enum LaundryNoteReportReasonEnum: string
{
    case SPAM = 'spam';
    case INSULTING = 'insulting';
    case INAPPROPRIATE = 'inappropriate';
    case OFF_TOPIC = 'off_topic';
    case OTHER = 'other';
}
