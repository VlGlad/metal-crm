export function emptyOtkInspection() {
  return {
    id: null,
    date: new Date().toISOString().slice(0, 10),
    name: '',
    project: '',
    presentedQuantity: 0,
    acceptedQuantity: 0,
    rejectedQuantity: 0,
    nonconformityDescription: '',
    nonconformityActNumber: '',
    executorName: '',
    controllerName: '',
    executorSignedAt: null,
    controllerSignedAt: null,
    status: 'draft'
  }
}

export function normalizeOtkInspection(item = {}) {
  return {
    id: item.id ?? null,
    date: item.date ?? new Date().toISOString().slice(0, 10),
    name: item.name ?? '',
    project: item.project ?? '',
    presentedQuantity: Number(item.presentedQuantity ?? 0),
    acceptedQuantity: Number(item.acceptedQuantity ?? 0),
    rejectedQuantity: Number(item.rejectedQuantity ?? 0),
    nonconformityDescription: item.nonconformityDescription ?? '',
    nonconformityActNumber: item.nonconformityActNumber ?? '',
    executorName: item.executorName ?? '',
    controllerName: item.controllerName ?? '',
    executorSignedAt: item.executorSignedAt ?? null,
    controllerSignedAt: item.controllerSignedAt ?? null,
    status: item.status ?? 'draft'
  }
}

export function payloadFromOtkInspection(form) {
  return {
    date: form.date,
    name: form.name.trim(),
    project: form.project.trim(),
    presentedQuantity: Number(form.presentedQuantity || 0),
    acceptedQuantity: Number(form.acceptedQuantity || 0),
    rejectedQuantity: Number(form.rejectedQuantity || 0),
    nonconformityDescription: form.nonconformityDescription.trim(),
    nonconformityActNumber: form.nonconformityActNumber.trim(),
    executorName: form.executorName.trim(),
    controllerName: form.controllerName.trim(),
    status: form.status
  }
}