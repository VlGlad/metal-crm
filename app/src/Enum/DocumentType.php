<?php

namespace App\Enum;

enum DocumentType: string
{
    case KM_PROJECT = 'km_project';
    case ORDER_CALCULATION = 'order_calculation';
    case SPECIFICATION_AND_CONTRACTS = 'specification_and_contracts';
    case ORDER_SUPPORTING_DOCUMENTS = 'order_supporting_documents';
    case KMD = 'kmd';
    case CUTTING_DRAWINGS_AND_PROGRAMS = 'cutting_drawings_and_programs';
    case PRODUCTION_PLAN = 'production_plan';
    case PRODUCTION_SCHEDULE = 'production_schedule';
    case MATERIAL_REQUEST = 'material_request';
    case MATERIAL_PROCUREMENT_REQUEST = 'material_procurement_request';
    case INCOMING_CONTROL_ACT = 'incoming_control_act';
    case NONCONFORMITY_ACT = 'nonconformity_act';
    case SHIFT_REPORT = 'shift_report';
    case WELDING_JOURNAL = 'welding_journal';
    case ULTRASONIC_TESTING_REPORT = 'ultrasonic_testing_report';
    case PAINTING_JOURNAL = 'painting_journal';
    case ACCEPTANCE_ACT = 'acceptance_act';
    case PRODUCT_PASSPORT = 'product_passport';
    case WAYBILL = 'waybill';
}
