<?php

namespace App\Enum;

enum ProductionOrderStatus: string
{
    case DRAFT = 'draft';
    case POTENTIAL = 'potential';
    case IN_WORK = 'in_work';
    case COMPLETED = 'completed';
}
