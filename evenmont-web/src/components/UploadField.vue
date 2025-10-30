<template>
  <div
    class="uploader"
    :class="{ 'is-loading': loading, 'is-drag': dragover }"
    @dragover.prevent="onDragOver"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <div class="row" style="gap: 0.6rem; align-items: center">
      <input
        :id="id"
        type="file"
        :accept="accept"
        :disabled="loading"
        @change="onFile"
        style="display: none"
      />
      <label class="btn pill" :for="id">{{ label }}</label>

      <button v-if="url" class="btn btn--ghost pill" @click="clear">Retirer</button>

      <span v-if="hint" class="muted">{{ hint }}</span>
    </div>

    <div v-if="preview" class="preview-wrap">
      <img :src="preview" alt="Aperçu" class="preview" />
      <div v-if="loading" class="bar">
        <div class="bar-fill" :style="{ width: progress + '%' }" />
      </div>
    </div>

    <p v-if="error" class="error">{{ error }}</p>
    <p v-if="url" class="muted">URL : {{ url }}</p>
  </div>
</template>

<script setup>
import { ref, onBeforeUnmount, computed } from 'vue'
import { api } from '@/services/api'

const emit = defineEmits(['uploaded', 'error', 'clear', 'update:modelValue'])

const props = defineProps({
  modelValue: { type: String, default: '' }, // v-model:url
  label: { type: String, default: 'Choisir une image' },
  hint: { type: String, default: 'JPEG, PNG ou WEBP (max 5 Mo)' },
  id: { type: String, default: 'upload-field' },
  accept: { type: String, default: 'image/jpeg,image/png,image/webp' },
  max: { type: Number, default: 5 * 1024 * 1024 },
})

const ALLOWED = ['image/jpeg', 'image/png', 'image/webp']

const preview = ref('')
const url = ref(props.modelValue)
const error = ref('')
const loading = ref(false)
const progress = ref(0)
const dragover = ref(false)
let abortCtrl = null

// garder la preview si on a déjà une URL
const existingIsHttp = computed(() => url.value && /^https?:\/\//.test(url.value))
if (url.value && existingIsHttp.value) preview.value = url.value

function clearPreview() {
  if (preview.value && !existingIsHttp.value) URL.revokeObjectURL(preview.value)
  preview.value = ''
}

onBeforeUnmount(() => {
  clearPreview()
  abortCtrl?.abort()
})

function clear() {
  error.value = ''
  url.value = ''
  emit('update:modelValue', '')
  emit('clear')
  clearPreview()
}

function validateFile(f) {
  if (!ALLOWED.includes(f.type)) {
    error.value = 'Formats autorisés : JPEG, PNG, WEBP'
    return false
  }
  if (f.size > props.max) {
    error.value = 'Fichier trop volumineux (max 5 Mo)'
    return false
  }
  return true
}

async function uploadFile(f) {
  loading.value = true
  progress.value = 0
  abortCtrl = new AbortController()

  try {
    const fd = new FormData()
    fd.append('file', f)

    const { data } = await api.post('/upload', fd, {
      signal: abortCtrl.signal,
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (evt) => {
        if (evt.total) progress.value = Math.round((evt.loaded / evt.total) * 100)
      },
    })

    url.value = data.absoluteUrl || data.url
    emit('update:modelValue', url.value)
    emit('uploaded', url.value)
  } catch (e) {
    const res = e?.response
    const status = res?.status
    const msg = (res?.data?.message || res?.data?.error || e?.message || '').toString()

    // limites serveur (PHP) : upload_max_filesize / post_max_size
    const tooBig =
      status === 413 ||
      /upload_max_filesize|post_max_size/i.test(msg) ||
      /max.*file.*size/i.test(msg)

    if (tooBig) {
      error.value = 'Fichier trop volumineux — la limite du serveur est dépassée.'
    } else if (status === 415) {
      error.value = 'Type de fichier non autorisé (JPEG/PNG/WEBP).'
    } else if (status === 401) {
      error.value = 'Session expirée — reconnecte-toi.'
    } else {
      error.value = 'Échec de l’upload — ' + (msg || 'réessaie')
    }

    emit('error', error.value)
  } finally {
    loading.value = false
    abortCtrl = null
  }
}

async function onFile(e) {
  error.value = ''
  clearPreview()
  const f = e.target.files?.[0]
  if (!f) return
  if (!validateFile(f)) {
    e.target.value = ''
    return
  }
  preview.value = URL.createObjectURL(f)
  await uploadFile(f)
  e.target.value = '' // pour pouvoir reselectionner le même fichier
}

function onDragOver() {
  dragover.value = true
}
function onDragLeave() {
  dragover.value = false
}
async function onDrop(e) {
  dragover.value = false
  const f = e.dataTransfer?.files?.[0]
  if (!f) return
  if (!validateFile(f)) return
  clearPreview()
  preview.value = URL.createObjectURL(f)
  await uploadFile(f)
}
</script>

<style scoped>
.uploader {
  display: grid;
  gap: 0.5rem;
}
.uploader.is-drag {
  outline: 2px dashed rgba(255, 255, 255, 0.25);
  outline-offset: 6px;
  border-radius: 12px;
  padding: 8px;
}
.preview-wrap {
  max-width: 420px;
}
.preview {
  max-width: 100%;
  border-radius: 12px;
  display: block;
}

/* barre de progression aux couleurs du thème */
.bar {
  height: 6px;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 6px;
  overflow: hidden;
  margin-top: 0.5rem;
}
.bar-fill {
  height: 100%;
  background: var(--primary);
  transition: width 0.2s ease;
}

.error {
  color: #ff5a7a;
}
.muted {
  opacity: 0.7;
  font-size: 0.9rem;
  word-break: break-all;
}
</style>
