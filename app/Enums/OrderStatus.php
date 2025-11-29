<?php

namespace App\Enums;

enum OrderStatus: string
{
    case ASSIGNED = 'ASSIGNED';
    case PICKED_UP = 'PICKED_UP';
    case OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    case DELIVERED = 'DELIVERED';
    case FAILED = 'FAILED';
}
