export const MONTHLY_PLAN_PARTICIPANT_ROLES = [
  'ROLE_PTO_HEAD',
  'ROLE_PTO_DEPUTY_HEAD',
  'ROLE_PTO_ENGINEER',
  'ROLE_PO_HEAD',
  'ROLE_PRODUCTION_HEAD',
  'ROLE_OMTS_HEAD',
  'ROLE_ADMIN'
]

export function canAccessMonthlyPlans(roles = []) {
  return MONTHLY_PLAN_PARTICIPANT_ROLES.some(role => roles.includes(role))
}
