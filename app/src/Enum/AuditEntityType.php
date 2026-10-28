<?php

namespace App\Enum;

use App\Entity\ProductionOrder;
use App\Entity\ProductionOrderFile;

enum AuditEntityType: string
{
    case ProductionOrder = ProductionOrder::class;
    case ProductionOrderFile = ProductionOrderFile::class;
}
