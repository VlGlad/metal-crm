export const PROCUREMENT_REQUEST_PARTICIPANT_ROLES = [
  'ROLE_PO_HEAD',
  'ROLE_DEPARTMENT_HEAD',
  'ROLE_OMTS_HEAD',
  'ROLE_OMTS_DEPUTY_HEAD',
  'ROLE_WAREHOUSE_MANAGER',
  'ROLE_METAL_WAREHOUSE_MANAGER',
  'ROLE_ADMIN'
]

export function canAccessProcurementRequests(roles = []) {
  return PROCUREMENT_REQUEST_PARTICIPANT_ROLES.some(role => roles.includes(role))
}
