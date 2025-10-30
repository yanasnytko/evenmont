<template>
  <AppLayout>
    <!-- Loading -->
    <section v-if="loading" class="container section">
      <div class="card" style="height: 180px; opacity: 0.6"></div>
      <div class="org-head-skel row" style="gap: 12px; margin-top: 14px">
        <div class="avatar-skel"></div>
        <div class="card" style="height: 60px; flex: 1; opacity: 0.6"></div>
      </div>
    </section>

    <!-- Erreur -->
    <section v-else-if="error" class="container section">
      <div class="alert alert--error">{{ error }}</div>
      <RouterLink class="btn btn--ghost pill mt-2" to="/organizers">← Retour</RouterLink>
    </section>

    <!-- 404 -->
    <section v-else-if="!org" class="container section">
      <h1 class="h1">Organisateur introuvable</h1>
      <p class="muted">Il a peut-être été supprimé.</p>
      <RouterLink class="btn pill mt-2" to="/organizers">Voir la liste</RouterLink>
    </section>

    <!-- Page -->
    <section v-else class="container section">
      <!-- Cover -->
      <div
        class="cover card"
        :style="{ backgroundImage: `url(${org.banner || fallbackCover})` }"
      ></div>

      <!-- Header -->
      <header class="org-head">
        <AvatarCircle :src="org.avatarUrl" :name="org.name" :size="72" />
        <div class="meta">
          <h1 class="h1" style="margin: 0">{{ org.name }}</h1>
          <p v-if="org.email" class="muted" style="margin: 0.2rem 0 0">{{ org.email }}</p>
          <div class="row" style="gap: 8px; margin-top: 8px">
            <span class="badge badge--blue">{{ org.eventsCount ?? 0 }} évènement(s)</span>
            <span v-if="org.city" class="badge badge--pink">{{ org.city }}</span>
          </div>
        </div>
        <div class="actions">
          <button class="btn pill btn-ghost" @click="share">Partager</button>
          <button class="btn pill">Suivre</button>
        </div>
      </header>

      <!-- Bio -->
      <section v-if="org.bio" class="paper" style="padding: 14px 16px; margin-top: 12px">
        <h3 class="h3" style="margin: 0 0 8px">À propos</h3>
        <p style="margin: 0; white-space: pre-line">{{ org.bio }}</p>
      </section>

      <!-- Events -->
      <section class="section" style="padding-top: 16px">
        <div class="row" style="justify-content: space-between; align-items: center; gap: 12px">
          <h2 class="h2" style="margin: 0">Événements</h2>
          <RouterLink
            class="btn btn--ghost pill"
            :to="{ name: 'events', query: { organizer: org.id } }"
          >
            Voir tout
          </RouterLink>
        </div>

        <div v-if="evError" class="alert alert--error" style="margin-top: 12px">{{ evError }}</div>
        <div v-else-if="evLoading" class="grid" style="margin-top: 12px">
          <div v-for="n in 6" :key="n" class="card" style="height: 240px; opacity: 0.6"></div>
        </div>
        <div v-else-if="!events.length" class="paper" style="padding: 14px 16px; margin-top: 12px">
          Aucun événement planifié.
        </div>
        <div v-else class="grid" style="margin-top: 12px">
          <EventCard
            v-for="e in events"
            :key="e.id"
            :event="e"
            @toggle-like="e.liked = !e.liked"
            @register="quickRegister(e)"
          />
        </div>
      </section>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import EventCard from '@/components/EventCard.vue'
import AvatarCircle from '@/components/AvatarCircle.vue'
import { api } from '@/services/api'
import { toAbsolute } from '@/services/url'

const route = useRoute()

const org = ref(null)
const loading = ref(true)
const error = ref('')
const events = ref([])
const evLoading = ref(false)
const evError = ref('')
const fallbackCover = '/img/demo.jpg'

function normalizeOrganizer(x) {
  return {
    id: x.id,
    name:
      x.name || [x.firstName, x.lastName].filter(Boolean).join(' ') || x.email || 'Organisateur',
    email: x.email || '',
    city: x.city || '',
    bio: x.bio || '',
    // avatar/banner en absolu pour éviter les soucis 5173↔8000
    avatarUrl: toAbsolute(x.avatarUrl || ''),
    banner: x.banner ? toAbsolute(x.banner) : '',
    eventsCount: x.eventsCount ?? x.stats?.eventsCount ?? 0,
  }
}

async function loadOrganizer() {
  loading.value = true
  error.value = ''
  org.value = null
  try {
    const { data } = await api.get(`/organizers/${route.params.id}`)
    org.value = normalizeOrganizer(data || {})
  } catch {
    error.value = 'Impossible de charger cet organisateur'
  } finally {
    loading.value = false
  }
}

function normalizeEvent(x) {
  return {
    ...x,
    image: x.image || x.coverUrl || `/img/demo${((x.id ?? 1) % 4) + 1}.jpg`,
    liked: false,
  }
}

async function loadEvents() {
  evLoading.value = true
  evError.value = ''
  events.value = []
  try {
    // 1) /api/organizers/{id}/events (si dispo) ; sinon 2) /api/events?organizer={id}
    let data
    try {
      ;({ data } = await api.get(`/organizers/${route.params.id}/events`, {
        params: { limit: 12 },
      }))
    } catch {
      ;({ data } = await api.get('/events', { params: { organizer: route.params.id, limit: 12 } }))
    }
    const list = Array.isArray(data?.items) ? data.items : Array.isArray(data) ? data : []
    events.value = list.map(normalizeEvent)
  } catch {
    evError.value = 'Impossible de charger les événements'
  } finally {
    evLoading.value = false
  }
}

async function quickRegister(e) {
  try {
    await api.post('/registrations', { eventId: e.id })
    alert('Inscription enregistrée')
  } catch {
    alert('Échec — connecte-toi pour t’inscrire.')
  }
}

function share() {
  const url = window.location.href
  navigator.clipboard?.writeText(url)
  alert('Lien copié')
}

onMounted(() => {
  loadOrganizer()
  loadEvents()
})
watch(
  () => route.params.id,
  () => {
    loadOrganizer()
    loadEvents()
  },
)
</script>

<style scoped>
.cover {
  height: 180px;
  background-size: cover;
  background-position: center;
}
.org-head {
  display: grid;
  gap: 12px;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  margin-top: 12px;
}
.meta .muted {
  margin-top: 2px;
}

.grid {
  display: grid;
  gap: 18px;
  grid-template-columns: repeat(3, 1fr);
}
@media (max-width: 1100px) {
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (max-width: 680px) {
  .org-head {
    grid-template-columns: auto 1fr;
  }
  .actions {
    grid-column: 1 / -1;
  }
  .grid {
    grid-template-columns: 1fr;
  }
}

/* skeleton */
.org-head-skel .avatar-skel {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
}
.mt-2 {
  margin-top: 0.75rem;
}
</style>
