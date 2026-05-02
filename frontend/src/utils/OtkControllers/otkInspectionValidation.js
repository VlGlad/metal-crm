export function validateOtkInspection(form) {
  if (!form.date) {
    return 'Укажите дату.'
  }

  if (!String(form.name ?? '').trim()) {
    return 'Укажите наименование.'
  }

  if (!String(form.project ?? '').trim()) {
    return 'Укажите проект.'
  }

  const presented = Number(form.presentedQuantity || 0)
  const accepted = Number(form.acceptedQuantity || 0)
  const rejected = Number(form.rejectedQuantity || 0)

  if (presented < 0 || accepted < 0 || rejected < 0) {
    return 'Количество не может быть меньше нуля.'
  }

  if (accepted + rejected > presented) {
    return 'Сумма принятых и забракованных не может превышать предъявленное количество.'
  }

  if (
    rejected > 0 &&
    !String(form.nonconformityDescription ?? '').trim() &&
    !String(form.nonconformityActNumber ?? '').trim()
  ) {
    return 'При наличии брака укажите описание несоответствия или номер акта.'
  }

  return ''
}