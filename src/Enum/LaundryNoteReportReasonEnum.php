<?php

namespace App\Enum;

enum LaundryNoteReportReasonEnum: string
{
    case EQUIPMENT_BROKEN = 'equipment_broken';
    case CLEANLINESS_ISSUE = 'cleanliness_issue';
    case SAFETY_CONCERN = 'safety_concern';
    case STAFF_BEHAVIOR = 'staff_behavior';
    case PRICING_ISSUE = 'pricing_issue';
    case OTHER = 'other';
}
