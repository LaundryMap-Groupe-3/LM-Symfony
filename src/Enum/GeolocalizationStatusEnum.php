<?php

namespace App\Enum;

enum GeolocalizationStatusEnum: string
{
    case VERIFIED = 'geolocalized';
    case PENDING = 'pending';
    case FAILED = 'geolocation_error';
}
