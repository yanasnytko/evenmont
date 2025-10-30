<template>
  <section class="hero card" :style="bgStyle" role="region" aria-label="En-tête EvenMont">
    <div class="hero-inner">
      <img v-if="logo" :src="logo" alt="EvenMont" class="hero-logo" />
      <h1 class="h1">{{ title }}</h1>
      <p class="subtitle">{{ subtitle }}</p>

      <div class="cta-wrap">
        <RouterLink :to="ctaTo" class="btn pill glow">{{ ctaLabel }}</RouterLink>
        <RouterLink v-if="secondaryTo" :to="secondaryTo" class="btn btn--ghost pill">
          {{ secondaryLabel }}
        </RouterLink>
      </div>

      <div v-if="stats?.length" class="stats">
        <div v-for="(s, i) in stats" :key="i" class="stat chip">
          <strong>{{ s.value }}</strong
          ><span>{{ s.label }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ name: 'HeroSection' })

const props = defineProps({
  image: { type: String, default: '/img/hero.jpg' },
  overlay: {
    type: String,
    default: 'linear-gradient(180deg, rgba(0,0,0,.35) 0%, rgba(0,0,0,.55) 60%)',
  },
  title: { type: String, default: 'EvenMont' },
  subtitle: { type: String, default: 'Des événements en altitude' },
  ctaTo: { type: [String, Object], default: '/events' },
  ctaLabel: { type: String, default: 'Explorer' },
  secondaryTo: { type: [String, Object], default: null },
  secondaryLabel: { type: String, default: 'Devenir organisateur' },
  logo: { type: String, default: '/logo-mark.svg' },
  stats: {
    type: Array,
    default: () => [
      { value: '120+', label: 'Événements' },
      { value: '35', label: 'Stations' },
      { value: '4.8★', label: 'Satisfaction' },
    ],
  },
})

const bgStyle = computed(() => ({
  backgroundImage: `${props.overlay}, url(${props.image})`,
  backgroundSize: 'cover',
  backgroundPosition: 'center',
}))
</script>

<style scoped>
.hero {
  min-height: 360px;
  display: grid;
  place-items: center;
  text-align: center;
  border-radius: 24px;
  overflow: hidden;
  position: relative;
}
.hero::after {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(120% 100% at 50% 0%, rgba(255, 255, 255, 0.06), transparent 60%);
}
.hero-inner {
  position: relative;
  z-index: 1;
  padding: 48px 16px;
  display: grid;
  gap: 10px;
  justify-items: center;
}
.hero-logo {
  height: 60px;
  width: auto;
}
.subtitle {
  opacity: 0.9;
  margin: 0;
}
.cta-wrap {
  display: flex;
  gap: 10px;
  margin-top: 8px;
  flex-wrap: wrap;
  justify-content: center;
}
.stats {
  display: flex;
  gap: 10px;
  margin-top: 14px;
  flex-wrap: wrap;
  justify-content: center;
}
.stat {
  gap: 0.5rem;
}
.stat strong {
  font-weight: 700;
  color: #fff;
}
@media (max-width: 560px) {
  .hero {
    min-height: 300px;
  }
  .hero-logo {
    height: 48px;
  }
}
</style>
