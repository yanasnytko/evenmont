<template>
  <div class="avatar" :style="styleObj" :aria-label="alt">
    <img v-if="src" :src="safeSrc" :alt="alt" @error="onErr" />
    <span v-else>{{ initials }}</span>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
const props = defineProps({
  src: { type: String, default: '' },
  name: { type: String, default: '' },
  size: { type: Number, default: 72 },
  alt: { type: String, default: 'Avatar' },
})

const failed = ref(false)
const safeSrc = computed(() => (failed.value ? '' : props.src))
function onErr() {
  failed.value = true
}

const initials = computed(() => {
  const n = (props.name || '').trim()
  const p = n ? n.split(/\s+/) : []
  const a = (p[0]?.[0] || '').toUpperCase()
  const b = (p[1]?.[0] || '').toUpperCase()
  return a + b || n[0]?.toUpperCase() || 'U'
})

const palette = ['#235789', '#2b6777', '#7a9e9f', '#6c63ff', '#ff9fb2', '#ffbd69']
const bg = computed(() => palette[(props.name || '').length % palette.length])

const styleObj = computed(() => ({
  width: props.size + 'px',
  height: props.size + 'px',
  background: bg.value,
}))
</script>

<style scoped>
.avatar {
  border-radius: 50%;
  color: #fff;
  font-weight: 800;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: var(--shadow);
}
.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
</style>
