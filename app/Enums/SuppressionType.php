<?php

namespace App\Enums;

enum SuppressionType: string
{
    case BOUNCE    = 'bounce';
    case COMPLAINT = 'complaint';
}
