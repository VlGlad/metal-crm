export const WORKING_DOCUMENT_PARTICIPANT_ROLES = [
  'ROLE_LEAD_DESIGN_ENGINEER',
  'ROLE_DESIGN_ENGINEER',
  'ROLE_ADMIN'
]

export function canAccessWorkingDocuments(roles = []) {
  return WORKING_DOCUMENT_PARTICIPANT_ROLES.some(role => roles.includes(role))
}
