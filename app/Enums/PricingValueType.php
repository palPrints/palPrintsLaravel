<?php

namespace App\Enums;

enum PricingValueType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
