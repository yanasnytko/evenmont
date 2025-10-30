<!-- src/views/EventsView.vue -->
<template>
  <AppLayout>
    <!-- Filtres -->
    <section class="section">
      <div class="card" style="padding: 14px">
        <form
          class="row"
          style="align-items: center; gap: 10px; flex-wrap: wrap"
          @submit.prevent="applyFilters"
        >
          <!-- recherche -->
          <div class="search" style="flex: 1; min-width: 240px">
            <input v-model.trim="q" type="search" placeholder="Mots-clés (titre, ville…)" />
            <button
              aria-label="Rechercher"
              style="border: 0; background: transparent; color: var(--text)"
            >
              🔍
            </button>
          </div>

          <!-- dates (optionnel côté API) -->
          <input v-model="from" class="input" type="date" style="max-width: 180px" />
          <input v-model="to" class="input" type="date" style="max-width: 180px" />

          <!-- Filtres -->
          <select v-model="category">
            <option value="">Catégories</option>
            <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>
          </select>

          <!-- tri -->
          <select v-model="sort" class="input" style="max-width: 220px">
            <option value="date_asc">Date · plus proche</option>
            <option value="date_desc">Date · plus loin</option>
            <option value="title_asc">Titre A→Z</option>
            <option value="title_desc">Titre Z→A</option>
          </select>

          <button class="btn pill" :disabled="loading">Rechercher</button>
          <button
            type="button"
            class="btn btn--ghost pill"
            @click="resetFilters"
            :disabled="loading"
          >
            Réinitialiser
          </button>
        </form>
      </div>
    </section>

    <!-- Header -->
    <section class="section ev-head">
      <h1 class="h1">Événements</h1>
      <RouterLink v-if="isOrganizer" class="btn pill hide-mobile" :to="{ name: 'createEvent' }"
        >Créer un événement</RouterLink
      >
    </section>

    <!-- Résultats -->
    <section class="section">
      <header class="row" style="justify-content: space-between; align-items: baseline">
        <h2 class="h2">Événements</h2>
        <span class="muted" v-if="total">{{ total }} résultat(s)</span>
      </header>

      <div v-if="error" class="alert alert--error">{{ error }}</div>

      <div v-else-if="loading" class="event-grid mt-2">
        <div v-for="n in limit" :key="n" class="card" style="height: 240px; opacity: 0.6"></div>
      </div>

      <div v-else-if="!events.length" class="paper" style="padding: 18px; text-align: center">
        Aucun événement trouvé.
      </div>

      <div v-else class="event-grid mt-2">
        <EventCard
          v-for="ev in events"
          :key="ev.id"
          :event="ev"
          @toggle-like="toggleLike"
          :can-register="isAuthenticated"
          :registered="ev.registered"
          :price="ev.price"
          :isFull="ev.isFull"
          @register="quickRegister"
          @unregister="quickUnregister"
          @pay="startPayment"
        />
      </div>

      <!-- Pagination -->
      <div class="row" style="justify-content: center; gap: 8px; margin-top: 16px" v-if="pages > 1">
        <button
          class="btn btn--ghost pill"
          :disabled="page === 1 || loading"
          @click="goPage(page - 1)"
        >
          Précédent
        </button>
        <span class="badge badge--blue">Page {{ page }} / {{ pages }}</span>
        <button class="btn pill" :disabled="page === pages || loading" @click="goPage(page + 1)">
          Suivant
        </button>
      </div>
    </section>

    <RouterLink
      v-if="isOrganizer"
      class="fab show-mobile"
      :to="{ name: 'createEvent' }"
      aria-label="Créer un événement"
      >+</RouterLink
    >
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import EventCard from '@/components/EventCard.vue'
import { api } from '@/services/api'
import { toAbsolute } from '@/services/url'
import { useAuth } from '@/stores/auth'

const auth = useAuth()
const isOrganizer = computed(() => !!auth.user && auth.user.roles?.includes('ROLE_ORGANIZER'))
const isAuthenticated = computed(() => !!auth.user)

const route = useRoute()
const router = useRouter()

// Filtres synchronisés avec l’URL
const q = ref(route.query.q?.toString() || '')
const sort = ref(route.query.sort?.toString() || 'date_asc')
const from = ref(route.query.from?.toString() || '')
const to = ref(route.query.to?.toString() || '')
const category = ref(route.query.category?.toString() || '')
const categories = ref([])

async function loadCategories() {
  try {
    const { data } = await api.get('/tags', { params: { withCounts: 0 } })
    const list = Array.isArray(data?.items) ? data.items : Array.isArray(data) ? data : []
    categories.value = list.map((t) => ({
      slug: t.slug ?? t.code ?? '',
      name: t.name ?? t.label ?? t.slug ?? 'Catégorie',
    }))
  } catch  {
    console.warn('loadCategories failed')
    categories.value = []
  }
}

const page = ref(Number(route.query.page) || 1)
const limit = 12

// Données
const events = ref([])
const total = ref(0)
const pages = ref(1)
const loading = ref(false)
const error = ref('')

// Construit l’objet params pour l’API (n’envoie pas de vides)
const params = computed(() => {
  const p = { q: q.value || undefined, sort: sort.value, page: page.value, limit }
  if (from.value) p.from = from.value
  if (to.value) p.to = to.value
  if (category.value) p.category = category.value
  return p
})

async function fetchEvents() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/events', { params: params.value })
    const items = Array.isArray(data?.items) ? data.items : Array.isArray(data) ? data : []
    events.value = items.map(normalizeEvent)
    total.value = Number(data?.total ?? items.length)
    pages.value = Number(data?.pages ?? Math.max(1, Math.ceil(total.value / limit)))

    // si connecté, enrichir avec le statut d'inscription (1 requête par event visible)
    if (isAuthenticated.value && events.value.length) {
      await loadRegistrationStatuses()
    }
  } catch  {
    error.value = 'Impossible de charger les événements'
  } finally {
    loading.value = false
  }
}

function normalizeEvent(e) {
  const price = typeof e.price === 'number' ? e.price : Number(e.ticketPrice ?? 0)
  const capacity = typeof e.capacity === 'number' ? e.capacity : (e.maxSeats ?? null)
  const regCount =
    typeof e.registrationsCount === 'number' ? e.registrationsCount : (e.nbRegistrations ?? null)
  const isFull = capacity != null && regCount != null ? Number(regCount) >= Number(capacity) : false

  return {
    id: e.id,
    title: e.title ?? 'Sans titre',
    city: e.city ?? e.location ?? '',
    description: e.description ?? '',
    image: e.image || (e.coverUrl ? toAbsolute(e.coverUrl) : ''),
    startAt: e.startAt ?? e.date ?? null,
    categories: Array.isArray(e.categories) ? e.categories : [],
    liked: !!e.liked,

    price,
    isFull,

    registered: undefined, // sera rempli via /registrations/me si connecté
  }
}

async function loadRegistrationStatuses() {
  const tasks = events.value.map(async (ev) => {
    try {
      const { data } = await api.get(`/events/${ev.id}/registrations/me`)
      ev.registered = !!data.registered
    } catch  {
      ev.registered = undefined
    }
  })
  await Promise.all(tasks)
}

function applyFilters() {
  page.value = 1
  router.replace({
    query: {
      q: q.value || undefined,
      sort: sort.value,
      from: from.value || undefined,
      to: to.value || undefined,
      category: category.value || undefined,
      page: 1,
    },
  })
}

function resetFilters() {
  q.value = ''
  sort.value = 'date_asc'
  from.value = ''
  to.value = ''
  category.value = ''
  page.value = 1
  router.replace({ query: {} })
}

function goPage(p) {
  page.value = Math.min(Math.max(1, p), pages.value)
  router.replace({ query: { ...route.query, page: page.value } })
}

// UX local “like”
async function toggleLike(ev) {
  ev.liked = !ev.liked
  try {
    await api.post(`/events/${ev.id}/like`, { liked: ev.liked })
  } catch  {
    // no-op
  }
}

/* Les trois handlers ci-dessous sont utilisés dans le template via @register / @unregister / @pay */

async function quickRegister(ev) {
  try {
    await api.post(`/events/${ev.id}/registrations`)
    ev.registered = true
    alert('Inscription enregistrée ✅')
  } catch (e) {
    const code = e?.response?.data?.error
    if (code === 'event_full') alert("L'événement est complet.")
    else if (code === 'event_past') alert("L'événement est passé.")
    else if (code === 'organizer_cannot_register') alert('Tu es l’organisateur·rice.')
    else alert('Échec de l’inscription.')
  }
}

async function quickUnregister(ev) {
  try {
    await api.delete(`/events/${ev.id}/registrations`)
    ev.registered = false
    alert('Désinscription effectuée ✅')
  } catch  {
    alert('Désinscription impossible pour le moment.')
  }
}

async function startPayment(ev) {
  try {
    const { data } = await api.post(`/events/${ev.id}/pay`, {})
    if (data?.checkoutUrl) {
      window.location.href = data.checkoutUrl
    } else {
      alert("Erreur: pas d'URL de paiement.")
    }
  } catch (e) {
    const code = e?.response?.data?.error
    alert(code === 'event_full' ? "L'événement est complet." : 'Paiement indisponible.')
  }
}

onMounted(() => {
  loadCategories()
  fetchEvents()
})

watch(
  () => route.query,
  () => {
    q.value = route.query.q?.toString() || ''
    sort.value = route.query.sort?.toString() || 'date_asc'
    from.value = route.query.from?.toString() || ''
    to.value = route.query.to?.toString() || ''
    category.value = route.query.category?.toString() || ''
    page.value = Number(route.query.page) || 1
    fetchEvents()
  },
)

watch(
  () => auth.user,
  async () => {
    if (isAuthenticated.value && events.value.length) {
      await loadRegistrationStatuses()
    } else {
      events.value = events.value.map((e) => ({ ...e, registered: undefined }))
    }
  },
)
</script>

<style scoped>
/* Header */
.ev-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 4px;
  padding-bottom: 4px;
}

/* FAB mobile */
.fab {
  position: fixed;
  right: 18px;
  bottom: 18px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--primary);
  color: #fff;
  font-size: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  box-shadow: var(--shadow);
  z-index: 60;
}

/* Helpers responsive */
.hide-mobile {
  display: inline-flex;
}
.show-mobile {
  display: none;
}
@media (max-width: 820px) {
  .hide-mobile {
    display: none;
  }
  .show-mobile {
    display: inline-flex;
  }
}
</style>
