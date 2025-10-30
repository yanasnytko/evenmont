import { defineStore } from 'pinia'
import { api, setToken, clearToken } from '@/services/api'
import axios from 'axios'

export const useAuth = defineStore('auth', {
  state: () => ({
    user: null,
    loading: false,
    error: null,
    unverified: false,
    hideVerifyBanner: !!localStorage.getItem('evenmont_hide_verify_banner'),
  }),
  actions: {
    async me() {
      const token = sessionStorage.getItem('evenmont_at')
      if (!token) {
        this.user = null
        return null
      }

      try {
        const { data } = await api.get('/me')
        this.user = data?.email ? data : null
        this.unverified = !!(this.user && this.user.email && this.user.emailVerified === false)
        return this.user
      } catch (e) {
        // 401 -> tenter un refresh explicite une seule fois
        if (e.response?.status === 401) {
          try {
            const { data } = await axios.post('/api/token/refresh', {}, { withCredentials: true })
            const newToken = data.token || data.access_token || data.jwt || null
            if (newToken) {
              setToken(newToken)
              const { data: me2 } = await api.get('/me')
              this.user = me2?.email ? me2 : null
              return this.user
            }
          } catch {
            clearToken()
          }
        }
        this.user = null
        this.unverified = false
        throw e
      }
    },

    async login(email, password) {
      this.loading = true
      this.error = null
      this.unverified = false
      try {
        const { data } = await api.post('/login_check', { email, password })
        const token = data.token || data.access_token || data.jwt
        if (!token) throw new Error('Token manquant')
        setToken(token)
        await this.me()
      } catch (e) {
        const msg = e?.response?.data?.message || ''
        if (/pas.*vérifié/i.test(msg) || /not.*verified/i.test(msg)) {
          this.unverified = true
          this.error = 'Ton email n’est pas vérifié.'
        } else {
          this.error = 'Email ou mot de passe invalide'
        }
        throw e
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch {
        // ignore
      }
      clearToken()
      this.user = null
      this.unverified = false
    },

    dismissVerifyBanner() {
      this.hideVerifyBanner = true
      localStorage.setItem('evenmont_hide_verify_banner', '1')
    },

    async register({ email, password }) {
      this.loading = true
      this.error = ''
      try {
        await api.post('/register', { email, password })
        return true
      } catch (e) {
        this.error = e?.response?.data?.error || 'register_failed'
        return false
      } finally {
        this.loading = false
      }
    },
  },
})
