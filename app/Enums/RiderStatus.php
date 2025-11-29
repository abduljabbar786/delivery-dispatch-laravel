<?php

namespace App\Enums;

enum RiderStatus: string
{
    case IDLE = 'IDLE';
    case BUSY = 'BUSY';
    case OFFLINE = 'OFFLINE';
}
