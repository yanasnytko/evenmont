<template>
  <AppLayout>
    <section class="container section">
      <div class="card" style="padding: 16px">
        <h1 class="h2">Finalisation du paiement…</h1>
        <p v-if="msg">{{ msg }}</p>
        <p v-if="error" class="alert alert--error">{{ error }}</p>
        <div class="row" style="gap: 10px; margin-top: 10px">
          <RouterLink class="btn pill" :to="{ name: 'eventDetail', params: { id: eventId } }"
            >Revenir à l’événement</RouterLink
          >
          <RouterLink class="btn btn--ghost pill" to="/events">Voir tous les événements</RouterLink>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import { api } from '@/services/api'

const route = useRoute()
const router = useRouter()

const paymentId = Number(route.query.pid ?? '')
const eventId = Number(route.query.event ?? '')
const msg = ref('')
const error = ref('')
const loading = ref(false)

async function tryFinalize() {
  loading.value = true
  try {
    const { data } = await api.post(`/payments/${paymentId}/finalize`)
    console.log('API finalize response:', data)

    // cas “payé”
    if (data?.status === 'paid') {
      if (data?.registered) {
        msg.value = 'Paiement confirmé ✅ Vous êtes inscrit·e !'
        setTimeout(() => {
          router.replace({ name: 'eventDetail', params: { id: eventId || data?.eventId } })
        }, 1200)
        return true
      }
      // payé mais pas “registered” (rare) → on affiche un message clair
      msg.value = 'Paiement confirmé, enregistrement en cours…'
      return false
    }

    // en attente
    if (data?.status === 'open' || data?.status === 'pending') {
      msg.value = 'Paiement en attente…'
      return false
    }

    // plein
    if (data?.reason === 'event_full') {
      error.value =
        'Événement complet au moment de la confirmation. Le paiement a été marqué comme échoué.'
      return true
    }

    // autres statuts négatifs
    if (['failed', 'canceled', 'expired'].includes(String(data?.status))) {
      error.value = 'Paiement non confirmé (' + data.status + ').'
      return true
    }

    // défaut
    error.value = 'Paiement non confirmé.'
    return false
  } catch (e) {
    console.error('API finalize error:', e)
    // 409 (plein) côté API
    if (e?.response?.status === 409 && e?.response?.data?.reason === 'event_full') {
      error.value = 'Événement complet au moment de la confirmation.'
      return true
    }
    const resp = e?.response?.data
    error.value = resp?.message || resp?.error || e?.message || 'Échec de la finalisation.'
    return false
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  console.log('PaymentReturnView mounted', { paymentId, eventId })
  if (!paymentId) {
    error.value = 'Paiement introuvable.'
    return
  }
  queueMicrotask(async () => {
    const delays = [0, 1000, 2000, 3000, 5000]
    for (let i = 0; i < delays.length; i++) {
      if (delays[i]) await new Promise((r) => setTimeout(r, delays[i]))
      try {
        const done = await tryFinalize()
        if (done) break
      } catch (e) {
        error.value = e?.response?.data?.message || 'Échec de la finalisation.'
        break
      }
    }
  })
})
</script>
