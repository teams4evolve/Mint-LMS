export function joinRestUrl(base, path) {
  const normalizedBase = String(base || '').replace(/\/+$/, '');
  const normalizedPath = String(path || '').replace(/^\/+/, '');
  return `${normalizedBase}/${normalizedPath}`;
}

export function getAdminConfig() {
  return window.mintLmsAdmin || {
    restBase: '/wp-json/mintlms/v1',
    mediaBase: '/wp-json/wp/v2/media',
    nonce: '',
    urls: {},
  };
}

export function getStudentConfig() {
  return window.mintLmsStudent || {
    restUrl: '/wp-json/mintlms/v1',
    nonce: '',
    i18n: {},
  };
}

export async function mintApi(path, options = {}, config = getAdminConfig()) {
  const url = joinRestUrl(config.restBase, path);
  const headers = {
    'Content-Type': 'application/json',
    'X-WP-Nonce': config.nonce,
    ...(options.headers || {}),
  };

  const response = await fetch(url, { ...options, headers });
  let json = {};

  try {
    json = await response.json();
  } catch {
    json = {};
  }

  if (!response.ok || json.success === false) {
    const message =
      json.error?.message ||
      json.message ||
      (typeof json.data?.message === 'string' ? json.data.message : '') ||
      response.statusText ||
      'Request failed';
    throw new Error(message);
  }

  return json.data;
}
