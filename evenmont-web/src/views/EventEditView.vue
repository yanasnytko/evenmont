<template>
  <AppLayout>
    <section class="container section">
      <header class="row" style="justify-content: space-between; align-items: center; gap: 12px">
        <h1 class="h1" style="margin: 0">Modifier l’événement</h1>
        <RouterLink class="btn btn--ghost pill" :to="{ name: 'eventDetail', params: { id: id } }"
          >← Retour</RouterLink
        >
      </header>

      <div class="grid">
        <!-- Form -->
        <form class="card form" @submit.prevent="submit">
          <div class="field">
            <label>Titre *</label>
            <input v-model.trim="form.title" class="input" required />
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
              <input v-model.trim="form.city" class="input" required />
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
              placeholder="Description…"
            ></textarea>
          </div>

          <div class="field two">
            <div>
              <label>Places</label>
              <input v-model.number="form.capacity" type="number" min="1" class="input" />
            </div>
            <div>
              <label>Tarif (EUR)</label>
              <input v-model.trim="form.price" type="number" min="0" step="0.01" class="input" />
            </div>
          </div>

          <div class="row" style="gap: 10px; margin-top: 10px">
            <button class="btn pill" :disabled="loading">Enregistrer</button>
            <RouterLink class="btn btn--ghost pill" :to="{ name: 'eventDetail', params: { id } }"
              >Annuler</RouterLink
            >
          </div>

          <p v-if="error" class="alert alert--error" style="margin-top: 10px">{{ error }}</p>
          <p v-if="msg" class="alert alert--success" style="margin-top: 10px">{{ msg }}</p>
        </form>

        <!-- Visuel -->
        <aside class="card side">
          <h3 class="h3" style="margin: 0 0 8px">Image de couverture</h3>
          <UploadField
            id="event-cover-edit"
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
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import UploadField from '@/components/UploadField.vue'
import { api } from '@/services/api'
import { toAbsolute, stripOrigin } from '@/services/url'

const route = useRoute()
const router = useRouter()
const id = Number(route.params.id)

const form = ref({
  title: '',
  startAt: '',
  endAt: '',
  city: '',
  category: '',
  description: '',
  capacity: null,
  price: '',
  coverUrl: '',
})

const loading = ref(false)
const error = ref('')
const msg = ref('')

const preview = computed(() => (form.value.coverUrl ? toAbsolute(form.value.coverUrl) : ''))

function onCoverUploaded(url) {
  form.value.coverUrl = url
}

function toLocalInput(dtStr) {
  // convertit "2025-03-15T08:30:00+01:00" -> "2025-03-15T08:30"
  if (!dtStr) return ''
  const d = new Date(dtStr)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/events/${id}`)
    form.value = {
      title: data.title ?? '',
      startAt: toLocalInput(data.startAt),
      endAt: toLocalInput(data.endAt),
      city: data.city ?? '',
      category: '', // adapte si tu as un champ catégorie à part
      description: data.description ?? '',
      capacity: typeof data.capacity === 'number' ? data.capacity : null,
      price: typeof data.price === 'number' ? data.price.toFixed(2) : '',
      coverUrl: data.coverUrl ? toAbsolute(data.coverUrl) : data.image || '',
    }
  } catch {
    error.value = 'Impossible de charger l’événement'
  } finally {
    loading.value = false
  }
}

async function submit() {
  error.value = ''
  msg.value = ''
  loading.value = true
  try {
    const payload = {
      title: form.value.title,
      startAt: form.value.startAt ? new Date(form.value.startAt).toISOString() : null,
      endAt: form.value.endAt ? new Date(form.value.endAt).toISOString() : null,
      city: form.value.city || null,
      description: form.value.description || null,
      capacity: form.value.capacity ?? null,
      price: form.value.price === '' ? null : form.value.price, // le back fait number_format
      coverUrl: stripOrigin(form.value.coverUrl ? toAbsolute(form.value.coverUrl) : '') || null,
    }
    await api.patch(`/events/${id}`, payload)
    msg.value = 'Modifications enregistrées ✅'
    // retour sur la fiche
    router.push({ name: 'eventDetail', params: { id } })
  } catch {
    error.value = 'Échec de la mise à jour'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<style scoped>
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
.form .input {
  border-radius: 14px;
}
.form select.input {
  border-radius: 14px;
}
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
textarea.input {
  border-radius: 16px !important;
  min-height: 140px;
  resize: vertical;
}
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
  .form .row .btn {
    width: 100%;
  }
}
</style>
