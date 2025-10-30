<template>
  <AppLayout>
    <!-- HERO -->
    <HeroSection
      image="/img/hero.jpg"
      title="EvenMont"
      subtitle="Des événements en altitude"
      :stats="stats"
      cta-to="/events"
      cta-label="Explorer"
      :secondary-to="{ name: 'organizers' }"
      secondary-label="Devenir organisateur"
    />

    <!-- FILTRES / RECHERCHE LOCALE -->
    <section class="section">
      <div class="card" style="padding: 14px">
        <form class="filters" @submit.prevent="applyFilters">
          <div class="search">
            <input v-model.trim="q" type="search" placeholder="Rechercher un événement…" />
            <button aria-label="Rechercher" class="search-btn">🔍</button>
          </div>

          <select v-model="sort" class="input select">
            <option value="date_asc">Date · plus proche</option>
            <option value="date_desc">Date · plus loin</option>
            <option value="title_asc">Titre A→Z</option>
            <option value="title_desc">Titre Z→A</option>
          </select>

          <div class="actions">
            <button class="btn pill" :disabled="loading">Filtrer</button>
            <button
              type="button"
              class="btn btn--ghost pill"
              @click="resetFilters"
              :disabled="loading"
            >
              Réinitialiser
            </button>
          </div>
        </form>
      </div>
    </section>

    <!-- LISTE D'ÉVÉNEMENTS -->
    <section class="section">
      <header class="row" style="justify-content: space-between; align-items: baseline">
        <h2 class="h2">À l’affiche</h2>
        <span class="muted" v-if="total">{{ total }} résultat(s)</span>
      </header>

      <!-- états -->
      <div v-if="error" class="alert alert--error">{{ error }}</div>

      <div v-else-if="loading" class="event-grid mt-2">
        <div v-for="n in limit" :key="n" class="card" style="height: 240px; opacity: 0.6"></div>
      </div>

      <div v-else-if="!events.length" class="paper" style="padding: 18px; text-align: center">
        Aucun événement trouvé.
      </div>

      <div v-else class="event-grid mt-2">
        <EventCard
          v-for="e in events"
          :key="e.id"
          :event="e"
          @toggle-like="toggleLike"
          :can-register="isAuthenticated"
          :registered="e.registered"
          :price="e.price"
          :isFull="e.isFull"
          @register="quickRegister"
          @unregister="quickUnregister"
          @pay="startPayment"
        />
      </div>

      <!-- PAGINATION -->
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
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import HeroSection from '@/components/HeroSection.vue'
import EventCard from '@/components/EventCard.vue'
import { api } from '@/services/api'
import { toAbsolute } from '@/services/url'
import { useAuth } from '@/stores/auth'

const route = useRoute()
const router = useRouter()

// Auth
const auth = useAuth()
const isAuthenticated = computed(() => !!auth.user)

// UI / stats hero (optionnel)
const stats = ref([
  { value: '120+', label: 'Événements' },
  { value: '35', label: 'Stations' },
  { value: '4.8★', label: 'Satisfaction' },
])

// Filtres & pagination (sync avec URL)
const q = ref(route.query.q?.toString() || '')
const sort = ref(route.query.sort?.toString() || 'date_asc')
const page = ref(Number(route.query.page) || 1)
const limit = 12

// Données
const events = ref([])
const total = ref(0)
const pages = ref(1)
const loading = ref(false)
const error = ref('')

// Fetch
async function fetchEvents() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/events', {
      params: { q: q.value || undefined, sort: sort.value, page: page.value, limit },
    })
    const items = Array.isArray(data?.items) ? data.items : Array.isArray(data) ? data : []
    events.value = items.map(normalizeEvent)
    total.value = Number(data?.total ?? items.length)
    pages.value = Number(data?.pages ?? Math.max(1, Math.ceil(total.value / limit)))

    // Si connecté, enrichit chaque event avec le statut d'inscription
    if (isAuthenticated.value && events.value.length) {
      await loadRegistrationStatuses()
    }
  } catch {
    error.value = 'Impossible de charger les événements'
  } finally {
    loading.value = false
  }
}

// Normalisation (+ paiement/capacité)
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
    image: e.image || (e.coverUrl ? toAbsolute(e.coverUrl) : ''),
    description: e.description ?? '',
    startAt: e.startAt ?? e.date ?? null,
    liked: !!e.liked,

    price,
    isFull,

    registered: undefined, // sera complété si auth
  }
}

// Statuts d'inscription en batch (N requêtes simples)
async function loadRegistrationStatuses() {
  const jobs = events.value.map(async (ev) => {
    try {
      const { data } = await api.get(`/events/${ev.id}/registrations/me`)
      ev.registered = !!data.registered
    } catch {
      ev.registered = undefined
    }
  })
  await Promise.all(jobs)
}

// Actions UI
function applyFilters() {
  router.replace({ query: { q: q.value || undefined, sort: sort.value, page: 1 } })
}
function resetFilters() {
  q.value = ''
  sort.value = 'date_asc'
  page.value = 1
  router.replace({ query: {} })
}
function goPage(p) {
  page.value = Math.min(Math.max(1, p), pages.value)
  router.replace({ query: { ...route.query, page: page.value } })
}

// Like local
async function toggleLike(event) {
  event.liked = !event.liked
  try {
    await api.post(`/events/${event.id}/like`, { liked: event.liked })
  } catch {
    /* no-op demo */
  }
}

/* Handlers des CTA de la carte
   - gratuit  -> POST /events/:id/registrations
   - payant   -> POST /events/:id/pay puis redirection checkoutUrl
   - inscrit  -> DELETE /events/:id/registrations
*/

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
  } catch {
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

// Boot
onMounted(fetchEvents)

// Re-fetch à chaque modif d’URL (q/sort/page)
watch(
  () => route.query,
  () => {
    q.value = route.query.q?.toString() || ''
    sort.value = route.query.sort?.toString() || 'date_asc'
    page.value = Number(route.query.page) || 1
    fetchEvents()
  },
)

// Si l’auth change : recharge/rafraîchis les statuts
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
/* grille : 1 ligne en desktop */
.filters {
  display: grid;
  grid-template-columns: 1fr auto auto; /* search | select | actions */
  gap: 10px;
  align-items: center;
}

.select {
  min-width: 220px;
}
.search-btn {
  border: 0;
  background: transparent;
  color: var(--text);
  cursor: pointer;
}
.actions {
  display: flex;
  gap: 10px;
}

/* ===== Mobile ===== */
@media (max-width: 820px) {
  .filters {
    grid-template-columns: 1fr;
  }
  .filters .search,
  .filters .select,
  .filters .actions,
  .filters .btn {
    width: 100%;
  }
  .actions {
    flex-direction: column;
  }
}

/* Sécurise le composant .search du thème pour qu'il s'étire bien */
.search {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
}
.search input {
  width: 100%;
  background: transparent;
  border: none;
  color: var(--text);
  outline: none;
}
</style>
