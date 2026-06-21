<?php

namespace App\Enum;

enum DocumentPermission: string
{
    case VIEW = 'DOCUMENT_VIEW';
    case CREATE = 'DOCUMENT_CREATE';
    case EDIT = 'DOCUMENT_EDIT';
    case APPROVE = 'DOCUMENT_APPROVE';
    case SIGN = 'DOCUMENT_SIGN';
    case DELETE = 'DOCUMENT_DELETE';
}
