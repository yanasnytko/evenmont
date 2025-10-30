<!-- src/components/AvatarUploader.vue -->
<template>
  <div class="row" style="gap: 12px; align-items: center">
    <AvatarCircle :src="preview || toAbsolute(user?.avatarUrl)" :name="displayName" :size="72" />
    <div class="col">
      <label class="btn pill" :for="id" :class="{ 'btn-ghost': loading }">
        {{ loading ? 'Envoi…' : 'Choisir un avatar' }}
      </label>
      <input
        :id="id"
        type="file"
        accept="image/jpeg,image/png,image/webp"
        hidden
        @change="onFile"
      />
      <p class="muted" style="margin: 0.3rem 0 0">JPEG/PNG/WebP — max 5 Mo</p>
      <p v-if="error" class="error">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import AvatarCircle from '@/components/AvatarCircle.vue'
import { api } from '@/services/api'
import { useAuth } from '@/stores/auth'
import { toAbsolute } from '@/services/url'

const { id } = defineProps({ id: { type: String, default: 'avatar-input' } })
const auth = useAuth()
const user = computed(() => auth.user)
const displayName = computed(() =>
  user.value?.firstName
    ? `${user.value.firstName} ${user.value.lastName || ''}`.trim()
    : user.value?.email || '',
)

const loading = ref(false)
const error = ref('')
const preview = ref('')

const MAX = 5 * 1024 * 1024
const ALLOWED = ['image/jpeg', 'image/png', 'image/webp']

async function onFile(e) {
  error.value = ''
  preview.value = ''
  const f = e.target.files?.[0]
  if (!f) return
  if (!ALLOWED.includes(f.type)) {
    error.value = 'Formats autorisés : JPEG/PNG/WEBP'
    return
  }
  if (f.size > MAX) {
    error.value = 'Fichier trop volumineux (max 5 Mo)'
    return
  }

  preview.value = URL.createObjectURL(f)
  loading.value = true
  try {
    const fd = new FormData()
    fd.append('file', f)
    await api.post('/me/avatar', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    // recharger /me pour mettre à jour le store
    await auth.me()
  } catch (err) {
    error.value = err?.response?.data?.error || 'Échec de l’upload'
    preview.value = ''
  } finally {
    loading.value = false
    e.target.value = ''
  }
}
</script>

<style scoped>
.error {
  color: #ff5a7a;
}
.col {
  display: flex;
  flex-direction: column;
}
</style>
