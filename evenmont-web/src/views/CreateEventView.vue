<template>
  <AppLayout>
    <section class="container section">
      <header class="row" style="justify-content: space-between; align-items: center; gap: 12px">
        <h1 class="h1" style="margin: 0">Créer un événement</h1>
        <RouterLink class="btn btn--ghost pill" :to="{ name: 'events' }">← Retour</RouterLink>
      </header>

      <div class="grid">
        <!-- Colonne gauche : formulaire -->
        <form class="card form" @submit.prevent="submit">
          <div class="field">
            <label>Titre *</label>
            <input
              v-model.trim="form.title"
              class="input"
              placeholder="Ex: Sunrise & Coffee à la Croix de Fer"
              required
            />
          </div>

          <div class="field two">
            <div>
              <label>Début *</label>
              <input v-model="form.startAt" type="datetime-local" class="input" required />
            </div>
            <div>
              <label>Fin</label>
              <input v-model="form.endAt" type="datetime-local" class="input" />
            </div>
          </div>

          <div class="field two">
            <div>
              <label>Ville *</label>
              <input v-model.trim="form.city" class="input" placeholder="Ex: Chamonix" required />
            </div>
            <div>
              <label>Catégorie</label>
              <select v-model="form.category" class="input">
                <option value="">—</option>
                <option value="conference">Conférence</option>
                <option value="atelier">Atelier</option>
                <option value="concert">Concert</option>
                <option value="brunch">Brunch</option>
                <option value="sortie">Sortie</option>
              </select>
            </div>
          </div>

          <div class="field">
            <label>Description</label>
            <textarea
              v-model.trim="form.description"
              class="textarea"
              placeholder="Quelques lignes pour donner envie…"
            ></textarea>
          </div>

          <div class="field two">
            <div>
              <label>Places</label>
              <input
                v-model.number="form.capacity"
                type="number"
                min="1"
                class="input"
                placeholder="Ex: 30"
              />
            </div>
            <div>
              <label>Tarif (EUR)</label>
              <input
                v-model.trim="form.price"
                type="number"
                min="0"
                step="0.01"
                class="input"
                placeholder="Ex: 15.00"
              />
            </div>
          </div>

          <div class="row" style="gap: 10px; margin-top: 10px">
            <button class="btn pill" :disabled="loading">Publier</button>
            <button
              type="button"
              class="btn btn--ghost pill"
              :disabled="loading"
              @click="saveDraft"
            >
              Enregistrer brouillon
            </button>
          </div>

          <p v-if="error" class="alert alert--error" style="margin-top: 10px">{{ error }}</p>
          <p v-if="msg" class="alert alert--success" style="margin-top: 10px">{{ msg }}</p>
        </form>

        <!-- Colonne droite : visuel -->
        <aside class="card side">
          <h3 class="h3" style="margin: 0 0 8px">Image de couverture</h3>
          <UploadField
            id="event-cover"
            label="Choisir une image"
            hint="JPEG/PNG/WEBP — 5 Mo max"
            v-model="form.coverUrl"
            @uploaded="onCoverUploaded"
          />
          <div class="preview card" v-if="preview">
            <img :src="preview" alt="Prévisualisation couverture" />
          </div>
          <p class="muted" v-else>Pas d’image sélectionnée</p>
        </aside>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import UploadField from '@/components/UploadField.vue'
import { api } from '@/services/api'
import { toAbsolute, stripOrigin } from '@/services/url' // ⬅️ voir helper ci-dessous

const router = useRouter()

const form = ref({
  title: '',
  startAt: '',
  endAt: '',
  city: '',
  category: '',
  description: '',
  capacity: null,
  price: '',
  coverUrl: '', // on stocke l'URL (souvent absolue) renvoyée par l'upload
})

const loading = ref(false)
const error = ref('')
const msg = ref('')

// Prévisualisation : utilise la cover si présente
const preview = computed(() => (form.value.coverUrl ? toAbsolute(form.value.coverUrl) : ''))

function basicValidate() {
  if (!form.value.title?.trim()) return 'Le titre est obligatoire'
  if (!form.value.startAt) return 'La date de début est obligatoire'
  if (!form.value.city?.trim()) return 'La ville est obligatoire'
  if (form.value.endAt && form.value.startAt > form.value.endAt)
    return 'La date de fin doit être après le début'
  return ''
}

function onCoverUploaded(url) {
  // url peut être absolue → on l’affiche telle quelle,
  // mais on enverra une version relative vers l’API (stripOrigin)
  form.value.coverUrl = url
}

async function submit() {
  error.value = ''
  msg.value = ''

  const v = basicValidate()
  if (v) {
    error.value = v
    return
  }

  loading.value = true
  try {
    // on envoie coverUrl RELATIF (plus propre si tu changes de domaine)
    const payload = {
      title: form.value.title,
      startAt: form.value.startAt ? new Date(form.value.startAt).toISOString() : null,
      endAt: form.value.endAt ? new Date(form.value.endAt).toISOString() : null,
      city: form.value.city,
      description: form.value.description || null,
      category: form.value.category || null,
      capacity: form.value.capacity || null,
      price: form.value.price || null,
      coverUrl: stripOrigin(form.value.coverUrl ? toAbsolute(form.value.coverUrl) : '') || null,
      status: 'published',
    }

    const { data } = await api.post('/events', payload)
    msg.value = 'Événement publié'
    // redirection vers la fiche
    router.push({ name: 'eventDetail', params: { id: data.id } })
  } catch {
    error.value = 'Impossible de créer l’événement'
  } finally {
    loading.value = false
  }
}

async function saveDraft() {
  error.value = ''
  msg.value = ''
  loading.value = true
  try {
    const payload = {
      ...form.value,
      startAt: form.value.startAt ? new Date(form.value.startAt).toISOString() : null,
      endAt: form.value.endAt ? new Date(form.value.endAt).toISOString() : null,
      coverUrl: stripOrigin(form.value.coverUrl ? toAbsolute(form.value.coverUrl) : '') || null,
      status: 'draft',
    }
    const { data } = await api.post('/events', payload)
    msg.value = 'Brouillon enregistré'
    router.push({ name: 'eventDetail', params: { id: data.id } })
  } catch {
    error.value = 'Échec de l’enregistrement du brouillon'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ====== Layout ====== */
.grid {
  display: grid;
  gap: 18px;
  grid-template-columns: 1.1fr 0.9fr;
  margin-top: 14px;
}
@media (max-width: 900px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

.form {
  padding: 16px;
  display: grid;
  gap: 12px;
}
.field {
  display: grid;
  gap: 6px;
}
.field.two {
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

label {
  font-weight: 600;
  color: var(--muted);
}

/* ====== Inputs locaux (moins arrondis) ====== */
.form .input {
  border-radius: 14px;
}
.form select.input {
  border-radius: 14px;
}

/* Textarea non-pill (deux variantes pour couvrir les deux cas) */
.form .textarea {
  width: 100%;
  padding: 0.9rem 1.1rem;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.08);
  color: var(--text);
  border: 1px solid rgba(255, 255, 255, 0.12);
  min-height: 140px;
  resize: vertical;
}
.form .textarea::placeholder {
  color: var(--muted);
}
/* si tu as laissé class="input" sur le textarea, on force ici */
textarea.input {
  border-radius: 16px !important;
  min-height: 140px;
  resize: vertical;
}

/* ====== Colonne visuel ====== */
.side {
  padding: 16px;
  display: grid;
  gap: 10px;
}
.preview {
  overflow: hidden;
  border-radius: var(--radius);
  padding: 0;
}
.preview img {
  width: 100%;
  height: 240px;
  object-fit: cover;
  display: block;
}

/* ====== Alertes ====== */
.alert {
  padding: 0.75rem 1rem;
  border-radius: 12px;
}
.alert--error {
  background: rgba(255, 90, 122, 0.1);
  color: #ff90a5;
  border: 1px solid rgba(255, 90, 122, 0.25);
}
.alert--success {
  background: rgba(95, 230, 166, 0.08);
  color: #b8ffd8;
  border: 1px solid rgba(95, 230, 166, 0.25);
}

/* ====== Mobile ====== */
@media (max-width: 720px) {
  .field.two {
    grid-template-columns: 1fr;
  }
  .form {
    padding: 12px;
    gap: 10px;
  }
  .side {
    padding: 12px;
    gap: 8px;
  }
  .preview img {
    height: 200px;
  }
  /* boutons plein largeur */
  .form .row .btn {
    width: 100%;
  }
}
</style>
