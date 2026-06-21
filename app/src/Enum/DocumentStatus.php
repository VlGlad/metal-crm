<?php

namespace App\Enum;

enum DocumentStatus: string
{
    case DRAFT = 'draft';
    case ON_REVIEW = 'on_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';
}