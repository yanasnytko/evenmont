<template>
  <AppLayout>
    <!-- Loading -->
    <section v-if="loading" class="container section">
      <div class="card" style="height: 220px; opacity: 0.6"></div>
    </section>

    <!-- Erreur -->
    <section v-else-if="error" class="container section">
      <div class="alert alert--error">{{ error }}</div>
      <RouterLink class="btn btn--ghost pill mt-2" to="/events">← Retour aux événements</RouterLink>
    </section>

    <!-- 404 -->
    <section v-else-if="!event" class="container section">
      <h1 class="h1" style="display: flex; align-items: center; gap: 8px">Événement introuvable</h1>
      <p class="muted">Il a peut-être été supprimé ou n’existe pas.</p>
      <RouterLink class="btn pill mt-2" to="/events">Voir les événements</RouterLink>
    </section>

    <!-- Page -->
    <section v-else class="event container">
      <!-- Col gauche -->
      <div class="left">
        <h1 class="h1">
          {{ event.title }}
          <span v-if="isPast" class="badge badge--gray" style="margin-left: 8px">PASSÉ</span>
        </h1>

        <div class="row" style="gap: 10px; align-items: center; margin: 0.35rem 0 0.75rem">
          <span v-if="dateLabel" class="badge badge--blue">{{ dateLabel }}</span>
          <span v-if="event.city" class="badge badge--pink">{{ event.city }}</span>
          <span v-if="capacityInfo" class="badge">{{ capacityInfo }}</span>
          <span v-if="priceLabel" class="badge badge--green">{{ priceLabel }}</span>
        </div>

        <!-- Catégories -->
        <div v-if="event.categories?.length" class="chips">
          <span v-for="c in event.categories" :key="c.slug ?? c.name ?? c" class="chip">
            {{ typeof c === 'string' ? c : (c.name ?? c.slug) }}
          </span>
        </div>

        <!-- Organisateur -->
        <div v-if="organizer" class="org-line">
          <RouterLink class="org-link" :to="{ name: 'organizer', params: { id: organizer.id } }">
            <span
              class="org-avatar"
              :style="{ backgroundImage: organizer.avatar ? `url(${organizer.avatar})` : '' }"
            >
              <span v-if="!organizer.avatar">{{ initials(organizer.name) }}</span>
            </span>
            <span>par {{ organizer.name }}</span>
          </RouterLink>
        </div>

        <p v-if="event.description" class="desc">{{ event.description }}</p>

        <div class="row" style="gap: 10px; margin-top: 14px">
          <!-- Bouton auth-aware avec toggle -->
          <button
            v-if="isAuthenticated"
            class="btn pill"
            :class="isRegistered ? 'btn--secondary' : ''"
            :disabled="ctaDisabled"
            @click="onPrimaryCta"
          >
            {{ primaryCtaLabel }}
          </button>

          <RouterLink
            v-else
            class="btn pill"
            :to="{ name: 'login', query: { redirect: route.fullPath } }"
          >
            Se connecter pour s’inscrire
          </RouterLink>

          <RouterLink
            v-if="isOrganizer && event?.id"
            class="btn btn--ghost pill"
            :to="{ name: 'editEvent', params: { id: event.id } }"
          >
            Modifier l’événement
          </RouterLink>

          <RouterLink to="/events" class="btn btn--ghost pill">Retour</RouterLink>
        </div>

        <p v-if="msg" class="mt-2">{{ msg }}</p>
      </div>

      <!-- Col droite -->
      <aside class="right">
        <div class="cover card">
          <img :src="event.image || fallback" :alt="event.title || 'Image événement'" />
        </div>
        <p class="hint">L’adresse exacte sera communiquée à l’inscription.</p>
      </aside>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import { api } from '@/services/api'
import { useAuth } from '@/stores/auth'
import { toAbsolute } from '@/services/url'

const route = useRoute()
const router = useRouter()
const auth = useAuth()

const event = ref(null)
const organizer = ref(null) // {id, name, avatar?}
const loading = ref(true)
const error = ref('')
const msg = ref('')
const fallback = '/img/demo.jpg'

const isAuthenticated = computed(() => !!auth.user)
const isOrganizer = computed(
  () => !!(auth.user && event.value?.organizerId && auth.user.id === event.value.organizerId),
)

const isPast = computed(() => {
  const s = event.value?.startAt ? new Date(event.value.startAt) : null
  return !!(s && s.getTime() < Date.now())
})

const isPaidEvent = computed(() => Number(event.value?.price ?? 0) > 0)
const priceLabel = computed(() => {
  const p = Number(event.value?.price ?? 0)
  return p > 0 ? p.toFixed(2).replace('.', ',') + ' €' : null
})

// Capacité (si renvoyée par l’API de détail)
const capacity = ref(null) // number | null
const registrationsCount = ref(null) // number | null
const remainingSeats = computed(() => {
  if (capacity.value == null || registrationsCount.value == null) return null
  return Math.max(0, Number(capacity.value) - Number(registrationsCount.value))
})
const isFull = computed(() => {
  if (capacity.value == null || registrationsCount.value == null) return false
  return Number(registrationsCount.value) >= Number(capacity.value)
})
const capacityInfo = computed(() => {
  if (capacity.value == null) return ''
  if (remainingSeats.value == null) return `Capacité : ${capacity.value}`
  return `Places restantes : ${remainingSeats.value}/${capacity.value}`
})

// Statut d’inscription courant
const isRegistered = ref(false)
const registrationId = ref(null)

const dateLabel = computed(() => {
  const s = event.value?.startAt ? new Date(event.value.startAt) : null
  const e = event.value?.endAt ? new Date(event.value.endAt) : null
  if (!s) return ''
  const opts = { day: '2-digit', month: '2-digit', year: '2-digit' }
  const start = s.toLocaleDateString(undefined, opts)
  if (!e) return start
  const sameDay = s.toDateString() === e.toDateString()
  return sameDay ? start : `${start} → ${e.toLocaleDateString(undefined, opts)}`
})

function initials(name = '') {
  const parts = name.trim().split(/\s+/)
  return (parts[0]?.[0] || '').toUpperCase() + (parts[1]?.[0] || '').toUpperCase()
}

async function loadEvent() {
  loading.value = true
  error.value = ''
  event.value = null
  organizer.value = null
  msg.value = ''
  try {
    const { data } = await api.get(`/events/${route.params.id}`)
    event.value = {
      id: data.id,
      title: data.title ?? 'Sans titre',
      city: data.city ?? '',
      description: data.description ?? '',
      startAt: data.startAt ?? null,
      endAt: data.endAt ?? null,
      image: data.image || (data.coverUrl ? toAbsolute(data.coverUrl) : fallback),
      categories: Array.isArray(data.categories) ? data.categories : [],
      organizerId: data.organizerId ?? null,
      price: typeof data.price === 'number' ? data.price : 0,
    }
    // capacité si dispo
    capacity.value = typeof data.capacity === 'number' ? data.capacity : null
    registrationsCount.value =
      typeof data.registrationsCount === 'number' ? data.registrationsCount : null

    // organisateur
    if (event.value.organizerId) {
      try {
        const { data: org } = await api.get(`/organizers/${event.value.organizerId}`)
        organizer.value = {
          id: org.id,
          name: org.name || org.email || 'Organisateur',
          avatar: org.avatarUrl ? toAbsolute(org.avatarUrl) : '',
        }
      } catch {
        organizer.value = null
      }
    }
  } catch (e) {
    const s = e?.response?.status
    error.value = s === 404 ? 'Événement introuvable.' : 'Impossible de charger l’événement.'
  } finally {
    loading.value = false
  }
}

async function loadRegistrationStatus() {
  isRegistered.value = false
  registrationId.value = null
  if (!isAuthenticated.value || !event.value?.id) return
  try {
    const { data } = await api.get(`/events/${event.value.id}/registrations/me`)
    isRegistered.value = !!data.registered
    registrationId.value = data.registrationId ?? null
  } catch {
    // non connecté / pas inscrit : silencieux
  }
}

async function registerToEvent() {
  if (isPaidEvent.value) return startPayment()
  msg.value = ''
  try {
    const { data } = await api.post(`/events/${event.value.id}/registrations`)
    isRegistered.value = true
    if (registrationsCount.value != null) registrationsCount.value += 1
    msg.value =
      data?.message === 'already_registered'
        ? 'Tu es déjà inscrit·e.'
        : 'Inscription enregistrée ✅'
  } catch (e) {
    const code = e?.response?.data?.error
    if (code === 'event_full') msg.value = 'Désolé, l’événement est complet.'
    else if (code === 'event_past') msg.value = 'L’événement est passé.'
    else if (code === 'organizer_cannot_register') msg.value = 'Tu es l’organisateur·rice.'
    else msg.value = 'Échec de l’inscription.'
  }
}

async function unregisterFromEvent() {
  msg.value = ''
  try {
    await api.delete(`/events/${event.value.id}/registrations`)
    isRegistered.value = false
    registrationId.value = null
    if (registrationsCount.value != null && registrationsCount.value > 0) {
      registrationsCount.value -= 1
    }
    msg.value = 'Désinscription effectuée ✅ (un e-mail de confirmation a été envoyé)'
  } catch {
    msg.value = 'Désinscription impossible pour le moment.'
  }
}

async function startPayment() {
  msg.value = ''
  try {
    // crée le paiement chez Mollie et récupère l'URL de checkout
    const { data } = await api.post(`/events/${event.value.id}/pay`, {})
    if (data?.checkoutUrl) {
      // Redirection vers la page de paiement Mollie
      window.location.href = data.checkoutUrl
    } else {
      msg.value = "Erreur: pas d'URL de paiement."
    }
  } catch (e) {
    const code = e?.response?.data?.error
    if (code === 'event_full') msg.value = "L'événement est complet."
    else msg.value = 'Paiement indisponible pour le moment.'
  }
}

/** CTA calculé : label, disabled & action */
const primaryCtaLabel = computed(() => {
  if (isPast.value) return 'Événement passé'
  if (isOrganizer.value) return 'Tu es l’organisateur·rice'
  if (isRegistered.value) return 'Se désinscrire'
  if (isPaidEvent.value)
    return `Payer et m’inscrire${priceLabel.value ? ' (' + priceLabel.value + ')' : ''}`
  if (isFull.value) return 'Complet'
  return 'M’INSCRIRE'
})
const ctaDisabled = computed(
  () => isPast.value || isOrganizer.value || (isFull.value && !isRegistered.value),
)
function onPrimaryCta() {
  if (!isAuthenticated.value) {
    return router.push({ name: 'login', query: { redirect: route.fullPath } })
  }
  if (ctaDisabled.value) return

  if (isRegistered.value) return unregisterFromEvent()
  // pas inscrit :
  return isPaidEvent.value ? startPayment() : registerToEvent()
}

onMounted(async () => {
  await loadEvent()
  await loadRegistrationStatus()
})

// recharger si on change d’event ou si l’utilisateur se connecte/se déconnecte
watch(
  () => route.params.id,
  async () => {
    await loadEvent()
    await loadRegistrationStatus()
  },
)
watch(
  () => auth.user,
  async () => {
    await loadRegistrationStatus()
  },
)
</script>

<style scoped>
.event {
  display: grid;
  gap: 28px;
  padding: 24px 0 32px;
  grid-template-columns: 1.2fr 0.8fr;
}
.left .desc {
  line-height: 1.55;
  color: var(--text);
  white-space: pre-line;
}

/* Catégories (chips) */
.chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 6px 0 10px;
}
.chip {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 0.85rem;
  background: var(--primary-ghost);
  border: 1px solid rgba(35, 87, 137, 0.35);
}

/* Organisateur */
.org-line {
  margin: 6px 0 12px;
}
.org-link {
  display: inline-flex;
  gap: 10px;
  align-items: center;
  color: var(--text);
}
.org-link:hover {
  opacity: 0.9;
}
.org-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.12);
  background-size: cover;
  background-position: center;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  color: #fff;
}

.right .cover {
  overflow: hidden;
}
.right .cover img {
  width: 100%;
  height: 260px;
  object-fit: cover;
  display: block;
  border-radius: var(--radius);
}
.hint {
  color: var(--muted);
  margin-top: 10px;
}

@media (max-width: 900px) {
  .event {
    grid-template-columns: 1fr;
  }
  .right .cover img {
    height: 220px;
  }
}
.mt-2 {
  margin-top: 0.75rem;
}
</style>
