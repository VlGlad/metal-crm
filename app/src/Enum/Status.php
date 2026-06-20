<?php

namespace App\Enum;

enum ShiftTaskStatus: string
{
    // ЦРО
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case IN_PROGRESS = 'in_progress';
    case CLOSED = 'closed';

    // ССЦ
    case WAITING_ASSEMBLY = 'waiting_assembly';
    case ASSEMBLY_IN_PROGRESS = 'assembly_in_progress';
    case ASSEMBLY_COMPLETED = 'assembly_completed';

    case WAITING_WELDING = 'waiting_welding';
    case WELDING_IN_PROGRESS = 'welding_in_progress';
    case WELDING_COMPLETED = 'welding_completed';

    case WAITING_QC = 'waiting_qc';
    case REVISION_REQUIRED = 'revision_required';

    case READY_FOR_CPO_TRANSFER = 'ready_for_cpo_transfer';
}
