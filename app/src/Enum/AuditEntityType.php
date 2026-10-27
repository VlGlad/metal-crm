<?php

namespace App\Enum;

use App\Entity\ProductionOrder;

enum AuditEntityType: string
{
    case ProductionOrder = ProductionOrder::class;
}
