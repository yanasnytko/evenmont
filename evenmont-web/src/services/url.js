// src/services/url.js
export const API_ORIGIN = import.meta.env.VITE_API_ORIGIN || 'http://localhost:8000';

export function toAbsolute(url) {
  if (!url) return '';
  if (/^https?:\/\//i.test(url)) return url;
  return `${API_ORIGIN}${url.startsWith('/') ? url : `/${url}`}`;
}

export function stripOrigin(url) {
  if (!url) return '';
  try {
    const u = new URL(url, API_ORIGIN);
    // si l’URL pointe sur le même host que l’API → renvoie le pathname
    if (`${u.origin}` === `${new URL(API_ORIGIN).origin}`) return u.pathname + (u.search || '') + (u.hash || '');
    return url; // image externe : garde l’absolu
  } catch {
    return url; // déjà relatif
  }
}