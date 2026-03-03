<?php

namespace App\Enum;

enum LaundryEquipmentTypeEnum: string
{
    case WASHING_MACHINE = 'washing_machine';
    case DRYER = 'dryer';
    case IRONING_MACHINE = 'ironing_machine';
    case VACUUM = 'vacuum';
    case OTHER = 'other';
}
