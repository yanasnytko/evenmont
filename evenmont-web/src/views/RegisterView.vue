<template>
  <AppLayout>
    <section class="container" style="max-width: 520px; padding: 24px 0">
      <h1 class="h1" style="margin: 0 0 12px">Créer un compte</h1>

      <form class="card" style="padding: 18px" @submit.prevent="onSubmit">
        <input v-model.trim="firstName" class="input" placeholder="Prénom (optionnel)" />
        <input
          v-model.trim="lastName"
          class="input"
          placeholder="Nom (optionnel)"
          style="margin-top: 10px"
        />

        <input
          v-model.trim="email"
          class="input"
          type="email"
          placeholder="Email"
          style="margin-top: 10px"
          required
        />
        <input
          v-model="password"
          class="input"
          type="password"
          placeholder="Mot de passe (min. 8)"
          style="margin-top: 10px"
          required
        />

        <div
          class="card"
          style="padding: 10px; margin-top: 10px; background: rgba(255, 255, 255, 0.04)"
        >
          <label style="display: flex; align-items: center; gap: 8px">
            <input type="checkbox" v-model="organizer" />
            <span>Je veux un compte <strong>Organisateur</strong></span>
          </label>
          <p class="muted" style="margin: 0.4rem 0 0">Tu pourras créer et gérer des événements.</p>
        </div>

        <button class="btn" style="margin-top: 14px" :disabled="loading">Créer mon compte</button>

        <p v-if="error" style="color: #ff5a7a; margin-top: 10px">{{ error }}</p>
        <p v-if="ok" style="color: #7feaa6; margin-top: 10px">
          Compte créé ! Vérifie ton email pour activer le compte.
        </p>
      </form>

      <p style="margin-top: 10px">
        Déjà inscrit ? <RouterLink class="link" to="/login">Se connecter</RouterLink>
      </p>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { api } from '@/services/api'

const email = ref('')
const password = ref('')
const firstName = ref('')
const lastName = ref('')
const organizer = ref(false)

const loading = ref(false)
const error = ref('')
const ok = ref(false)

async function onSubmit() {
  loading.value = true
  error.value = ''
  ok.value = false
  try {
    await api.post('/register', {
      email: email.value,
      password: password.value,
      firstName: firstName.value || null,
      lastName: lastName.value || null,
      organizer: organizer.value,
    })
    ok.value = true
    // On ne connecte pas auto : on attend vérification email
  } catch (e) {
    const code = e?.response?.status
    const data = e?.response?.data
    if (code === 409) error.value = 'Cet email est déjà utilisé'
    else if (code === 422) error.value = 'Vérifie tes champs (mot de passe min. 8)'
    else error.value = data?.error || 'Inscription impossible'
  } finally {
    loading.value = false
  }
}
</script>
