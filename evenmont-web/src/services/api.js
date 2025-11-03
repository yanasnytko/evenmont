import axios from 'axios'

// const API_BASE = 'http://localhost:8000/api'
const API_BASE =
  import.meta.env.VITE_API_BASE ||
  `${import.meta.env.VITE_API_ORIGIN}`

export const api = axios.create({
  baseURL: API_BASE,
  withCredentials: true,
})

let accessToken = sessionStorage.getItem('evenmont_at') || null
export function setToken(t) {
  accessToken = t
  if (t) sessionStorage.setItem('evenmont_at', t)
  else sessionStorage.removeItem('evenmont_at')
}
export function clearToken() {
  setToken(null)
}

api.interceptors.request.use((cfg) => {
  if (accessToken) cfg.headers.Authorization = `Bearer ${accessToken}`
  return cfg
})

let refreshPromise = null

api.interceptors.response.use(
  (r) => r,
  async (err) => {
    const original = err.config || {}
    const status = err.response?.status
    const url = original?.url || ''

    // Pas de refresh pour ces routes
    if (url.includes('/login_check') || url.includes('/token/refresh')) {
      throw err
    }

    // Pas de token en mémoire → ne tente pas de refresh
    if (status === 401 && !accessToken) throw err

    if (status === 401 && !original._retry) {
      original._retry = true

      // Single-flight: une seule requête de refresh
      if (!refreshPromise) {
        refreshPromise = api 
          .post('/token/refresh', {}) 
          .then(({ data }) => {
            const newToken = data.token || data.access_token || data.jwt || null
            if (!newToken) throw new Error('No token in refresh response')
            setToken(newToken)
          })
          .catch((e) => {
            // Échec du refresh → on purge et on laisse la 401 remonter
            clearToken()
            throw e
          })
          .finally(() => {
            refreshPromise = null
          })
      }

      await refreshPromise

      if (accessToken) {
        // Rejoue la requête initiale avec le nouveau token
        return api(original)
      }
    }

    throw err
  },
)
