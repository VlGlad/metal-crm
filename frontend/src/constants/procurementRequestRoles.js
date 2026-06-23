export const PROCUREMENT_REQUEST_PARTICIPANT_ROLES = [
  'ROLE_PO_HEAD',
  'ROLE_DEPARTMENT_HEAD',
  'ROLE_ADMIN'
]

export function canAccessProcurementRequests(roles = []) {
  return PROCUREMENT_REQUEST_PARTICIPANT_ROLES.some(role => roles.includes(role))
}
