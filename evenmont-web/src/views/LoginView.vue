<template>
  <AppLayout>
    <section class="container" style="max-width: 520px; padding: 60px 0">
      <h1 class="h1" style="margin: 0 0 14px">Connexion</h1>

      
      <div v-if="auth.unverified" class="alert alert--warn" style="margin-bottom: 12px">
        Ton email n’est pas vérifié. Clique sur le lien reçu (ou recrée ton compte si le lien a expiré).
        <button class="btn btn--ghost pill" style="margin-left:8px" @click="onResend" :disabled="auth.loading">
         Renvoyer le mail
        </button>
        <span v-if="resendSuccess" style="margin-left:8px;color:#2b7a2b;font-weight:500">Mail envoyé !</span>
      </div>

      <form @submit.prevent="onLogin" class="card" style="padding: 18px">
        <input
          v-model.trim="email"
          type="email"
          placeholder="Email"
          class="input"
          autocomplete="username"
          required
          autofocus
        />

        <input
          v-model="password"
          type="password"
          placeholder="Mot de passe"
          class="input"
          style="margin-top: 10px"
          autocomplete="current-password"
          required
        />

        <button class="btn pill" style="margin-top: 14px; width: 100%" :disabled="auth.loading">
          {{ auth.loading ? 'Connexion…' : 'Se connecter' }}
        </button>

        <p v-if="localError || auth.error" class="form-error">
          {{ localError || auth.error }}
        </p>
      </form>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuth } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuth()

const email = ref('orga@evenmont.com')
const password = ref('password')
const localError = ref('')

const resendSuccess = ref(false)

async function onResend() {
  resendSuccess.value = false
  const ok = await auth.resendVerification(email.value)
  resendSuccess.value = ok
}

async function onLogin() {
  localError.value = ''
  try {
    await auth.login(email.value, password.value)
    // Redirige vers la page demandée ou /events par défaut
    const to = (route.query.redirect && String(route.query.redirect)) || '/events'
    router.replace(to)
  } catch {
    // Le store met déjà auth.error, mais on met un fallback ici
    localError.value = 'Email ou mot de passe invalide'
  }
}
</script>
<style scoped>
.alert {
  border-radius: 12px;
  padding: 10px 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.06);
}
.alert--warn {
  background: rgba(255, 189, 105, 0.15);
  border-color: rgba(255, 189, 105, 0.4);
}
</style>
