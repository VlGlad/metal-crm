export const PRODUCTION_PROGRESS_ROLES = [
  'ROLE_MASTER',
  'ROLE_CRO',
  'ROLE_SSC',
  'ROLE_CPO',
  'ROLE_CONTROLLER_OTK',
  'ROLE_OTK_HEAD',
  'ROLE_OTK_ENGINEER',
  'ROLE_ADMIN'
]

export function canAccessProductionProgress(roles = []) {
  return PRODUCTION_PROGRESS_ROLES.some(role => roles.includes(role))
}
