<template>
  <AppLayout>
    <section class="container section">
      <h1 class="h1">Mon espace</h1>

      <!-- Banner email non vérifié -->
      <div
        v-if="auth.user && auth.user.emailVerified === false"
        class="alert alert--warn"
        style="margin-top: 10px"
      >
        Ton email n’est pas vérifié. Clique sur le lien reçu (ou recrée ton compte si le lien a
        expiré).
      </div>

      <div v-if="!auth.user" class="alert alert--error" style="margin-top: 12px">
        Tu dois être connecté pour accéder à cette page.
      </div>

      <div v-else class="grid-2" style="margin-top: 14px">
        <!-- Col gauche : Avatar -->
        <div class="card" style="padding: 16px">
          <h3 class="h3" style="margin-top: 0">Avatar</h3>
          <div class="row" style="align-items: center; gap: 12px; flex-wrap: wrap">
            <img
              :src="avatarPreview || auth.user.avatarUrl || '/img/avatar-placeholder.png'"
              alt="Avatar"
              class="avatar"
            />
            <AvatarUploader id="avatar-upload" @uploaded="onAvatarUploaded" />
          </div>
          <p v-if="saveMsg" class="muted" style="margin-top: 8px">{{ saveMsg }}</p>
        </div>

        <!-- Col droite : Infos -->
        <form class="card" style="padding: 16px" @submit.prevent="save">
          <h3 class="h3" style="margin-top: 0">Informations</h3>

          <div class="row" style="gap: 10px; flex-wrap: wrap">
            <div style="flex: 1; min-width: 220px">
              <label class="muted">Prénom</label>
              <input v-model.trim="form.firstName" type="text" placeholder="Prénom" />
            </div>
            <div style="flex: 1; min-width: 220px">
              <label class="muted">Nom</label>
              <input v-model.trim="form.lastName" type="text" placeholder="Nom" />
            </div>
          </div>

          <div class="row" style="gap: 10px; margin-top: 10px; flex-wrap: wrap">
            <div style="flex: 1; min-width: 220px">
              <label class="muted">Email</label>
              <input :value="auth.user.email" type="email" disabled />
            </div>
            <div style="flex: 1; min-width: 220px">
              <label class="muted">Ville</label>
              <input v-model.trim="form.city" type="text" placeholder="Ville" />
            </div>
          </div>

          <div style="margin-top: 10px">
            <label class="muted">Bio</label>
            <textarea v-model.trim="form.bio" placeholder="Quelques mots sur toi…"></textarea>
          </div>

          <div class="row" style="gap: 10px; align-items: center; margin-top: 12px">
            <button class="btn pill" :disabled="saving">
              {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
            <span v-if="msg" class="badge badge--blue">{{ msg }}</span>
            <span v-if="err" class="badge badge--pink">{{ err }}</span>
          </div>
        </form>
      </div>
    </section>

    <!-- Mes événements (uniquement organisateur) -->
    <section v-if="isOrganizer" class="section">
      <div class="container">
        <div class="row" style="justify-content: space-between; align-items: center; gap: 12px">
          <h3 class="h3" style="margin: 0">Mes événements</h3>
          <router-link class="btn pill" to="/organizer/create">Créer un événement</router-link>
        </div>

        <div v-if="myEvError" class="alert alert--error" style="margin-top: 12px">
          {{ myEvError }}
        </div>

        <div v-else-if="myEvLoading" class="grid" style="margin-top: 12px">
          <div
            v-for="n in myEvLimit"
            :key="n"
            class="card"
            style="height: 240px; opacity: 0.6"
          ></div>
        </div>

        <div
          v-else-if="!myEvents.length"
          class="paper"
          style="padding: 14px 16px; margin-top: 12px"
        >
          Tu n’as pas encore d’événement.
          <router-link to="/organizer/create" class="link">Créer le premier</router-link>.
        </div>

        <div v-else class="event-grid" style="margin-top: 12px">
          <EventCard
            v-for="e in myEvents"
            :key="e.id"
            :event="e"
            @toggle-like="e.liked = !e.liked"
            @register="$router.push({ name: 'eventDetail', params: { id: e.id } })"
          />
        </div>

        <div
          v-if="myEvPages > 1"
          class="row"
          style="justify-content: center; gap: 8px; margin-top: 12px"
        >
          <button
            class="btn btn--ghost pill"
            :disabled="myEvPage === 1 || myEvLoading"
            @click="prevMyEv"
          >
            Précédent
          </button>
          <span class="badge badge--blue">Page {{ myEvPage }} / {{ myEvPages }}</span>
          <button
            class="btn pill"
            :disabled="myEvPage === myEvPages || myEvLoading"
            @click="nextMyEv"
          >
            Suivant
          </button>
        </div>
      </div>
    </section>

    <!-- Mes inscriptions -->
    <section class="section">
      <div class="container">
        <div class="row" style="justify-content: space-between; align-items: center; gap: 12px">
          <h3 class="h3" style="margin: 0">Mes inscriptions</h3>
        </div>

        <div v-if="regError" class="alert alert--error" style="margin-top: 12px">
          {{ regError }}
        </div>

        <div v-else-if="regLoading" class="grid" style="margin-top: 12px">
          <div
            v-for="n in regLimit"
            :key="n"
            class="card"
            style="height: 240px; opacity: 0.6"
          ></div>
        </div>

        <div
          v-else-if="!registrations.length"
          class="paper"
          style="padding: 14px 16px; margin-top: 12px"
        >
          Tu n’es inscrit à aucun événement pour l’instant.
        </div>

        <div v-else class="event-grid" style="margin-top: 12px">
          <EventCard
            v-for="e in registrations"
            :key="e.id"
            :event="e"
            @toggle-like="e.liked = !e.liked"
            @register="$router.push({ name: 'eventDetail', params: { id: e.id } })"
          />
        </div>

        <div
          v-if="regPages > 1"
          class="row"
          style="justify-content: center; gap: 8px; margin-top: 12px"
        >
          <button
            class="btn btn--ghost pill"
            :disabled="regPage === 1 || regLoading"
            @click="prevReg"
          >
            Précédent
          </button>
          <span class="badge badge--blue">Page {{ regPage }} / {{ regPages }}</span>
          <button class="btn pill" :disabled="regPage === regPages || regLoading" @click="nextReg">
            Suivant
          </button>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import AvatarUploader from '@/components/AvatarUploader.vue'
import EventCard from '@/components/EventCard.vue'
import { useAuth } from '@/stores/auth'
import { api } from '@/services/api'
import { toAbsolute, stripOrigin } from '@/services/url'

const auth = useAuth()
const isOrganizer = computed(() => !!auth.user?.roles?.includes('ROLE_ORGANIZER'))

// ----- Profil
const form = reactive({ firstName: '', lastName: '', city: '', bio: '' })
const saving = ref(false)
const msg = ref('')
const err = ref('')
const saveMsg = ref('')
const avatarPreview = ref('')

function fillFromAuth() {
  if (!auth.user) return
  form.firstName = auth.user.firstName || ''
  form.lastName = auth.user.lastName || ''
  form.city = auth.user.city || ''
  form.bio = auth.user.bio || ''
  avatarPreview.value = auth.user.avatarUrl ? toAbsolute(auth.user.avatarUrl) : ''
}

async function onAvatarUploaded(url) {
  // url peut être absolue → stocke en relatif côté API
  const rel = stripOrigin(url ? toAbsolute(url) : '')
  try {
    saveMsg.value = ''
    await api.put('/me', { avatarUrl: rel })
    await auth.me()
    fillFromAuth()
    avatarPreview.value = url
    saveMsg.value = 'Avatar mis à jour ✅'
  } catch {
    saveMsg.value = 'Échec de la mise à jour de l’avatar'
  }
}

async function save() {
  msg.value = ''
  err.value = ''
  saving.value = true
  try {
    await api.put('/me', {
      firstName: form.firstName || null,
      lastName: form.lastName || null,
      city: form.city || null,
      bio: form.bio || null,
    })
    await auth.me()
    fillFromAuth()
    msg.value = 'Enregistré'
  } catch {
    err.value = 'Échec de l’enregistrement'
  } finally {
    saving.value = false
  }
}

// ----- Mes événements (orga)
const myEvents = ref([])
const myEvLoading = ref(false)
const myEvError = ref('')
const myEvPage = ref(1)
const myEvPages = ref(1)
const myEvLimit = 6

async function loadMyEvents() {
  if (!isOrganizer.value) return
  myEvLoading.value = true
  myEvError.value = ''
  try {
    const { data } = await api.get('/my/events', {
      params: { page: myEvPage.value, limit: myEvLimit },
    })
    const list = Array.isArray(data?.items) ? data.items : []
    myEvents.value = list.map((x) => ({
      ...x,
      image:
        x.image || (x.coverUrl ? toAbsolute(x.coverUrl) : `/img/demo${((x.id ?? 1) % 4) + 1}.jpg`),
      liked: false,
    }))
    myEvPages.value = Number(data?.pages ?? 1)
  } catch {
    myEvError.value = 'Impossible de charger tes événements'
  } finally {
    myEvLoading.value = false
  }
}
function prevMyEv() {
  if (myEvPage.value > 1) {
    myEvPage.value--
    loadMyEvents()
  }
}
function nextMyEv() {
  if (myEvPage.value < myEvPages.value) {
    myEvPage.value++
    loadMyEvents()
  }
}

// ----- Mes inscriptions (tous)
const registrations = ref([])
const regLoading = ref(false)
const regError = ref('')
const regPage = ref(1)
const regPages = ref(1)
const regLimit = 6

async function loadRegistrations() {
  regLoading.value = true
  regError.value = ''
  try {
    const { data } = await api.get('/my/registrations', {
      params: { page: regPage.value, limit: regLimit },
    })
    const list = Array.isArray(data?.items) ? data.items : []

    registrations.value = list.map((r) => {
      const e = r.event || {}
      return {
        // ce que veut EventCard :
        id: e.id,
        title: e.title ?? 'Sans titre',
        city: e.city ?? '',
        description: e.description ?? '',
        startAt: e.startAt ?? null,
        endAt: e.endAt ?? null,
        image: e.image ?? '/img/demo.jpg',
        organizerId: e.organizerId ?? null,

        // bonus: garde la méta d’inscription si tu veux un badge
        registration: {
          id: r.registrationId,
          status: r.status,
          createdAt: r.createdAt,
        },
      }
    })

    regPages.value = Number(data?.pages ?? 1)
  } catch {
    regError.value = 'Impossible de charger tes inscriptions'
  } finally {
    regLoading.value = false
  }
}
function prevReg() {
  if (regPage.value > 1) {
    regPage.value--
    loadRegistrations()
  }
}
function nextReg() {
  if (regPage.value < regPages.value) {
    regPage.value++
    loadRegistrations()
  }
}

// ----- Boot
onMounted(async () => {
  if (!auth.user) {
    try {
      await auth.me()
    } catch {
      //
    }
  }
  fillFromAuth()
  await Promise.all([loadMyEvents(), loadRegistrations()])
})
</script>

<style scoped>
.grid-2 {
  display: grid;
  gap: 16px;
  grid-template-columns: 1fr 1.2fr;
}
@media (max-width: 900px) {
  .grid-2 {
    grid-template-columns: 1fr;
  }
}

label.muted {
  display: block;
  margin: 0 0 6px;
}

.avatar {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  object-fit: cover;
  display: block;
  box-shadow: var(--shadow);
}

/* Alerte warning */
.alert--warn {
  background: rgba(255, 200, 50, 0.08);
  color: #ffd36a;
  border: 1px solid rgba(255, 200, 50, 0.25);
}
</style>
