<template>
  <article class="card event-card" @click="goToDetail" role="button">
    <!-- Cover -->
    <div class="cover">
      <img
        :src="event.image || (event.coverUrl ? toAbsolute(event.coverUrl) : fallback)"
        :alt="event.title || 'Couverture événement'"
      />
      <button
        class="like"
        :aria-pressed="!!event.liked"
        :title="event.liked ? 'Retirer des favoris' : 'Ajouter aux favoris'"
        @click.stop="$emit('toggle-like', event)"
      >
        <span :class="{ on: event.liked }">❤</span>
      </button>
      <span v-if="dateLabel" class="badge badge--blue date-badge">{{ dateLabel }}</span>
    </div>

    <!-- Body -->
    <div class="content" @click.stop>
      <div class="meta">
        <h3 class="title">{{ event.title }}</h3>
      </div>

      <ul v-if="hasCategories" class="tags">
        <li v-for="c in normCategories" :key="c.slug || c.name || c" class="tag">
          {{ c.name || c }}
        </li>
      </ul>

      <div class="city" v-if="event.city">{{ event.city }}</div>
      <p class="desc" v-if="event.description">{{ event.description }}</p>

      <div class="actions">
        <!-- Lien détail toujours présent -->
        <RouterLink
          v-if="event.id"
          class="link"
          :to="{ name: 'eventDetail', params: { id: event.id } }"
        >
          DÉCOUVRIR
        </RouterLink>

        <!-- CTA intelligent -->
        <template v-if="canRegister && !isPast">
          <!-- Si le parent ne fournit PAS registered -> CTA neutre (détail) -->
          <RouterLink
            v-if="registered === undefined"
            class="btn pill"
            :to="{ name: 'eventDetail', params: { id: event.id } }"
          >
            M’INSCRIRE
          </RouterLink>

          <!-- Sinon, on affiche le toggle réel -->
          <button
            v-else
            class="btn pill"
            :class="registered ? 'btn--secondary' : ''"
            :disabled="ctaDisabled"
            @click.stop="onCtaClick"
          >
            {{ ctaLabel }}
          </button>
        </template>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { toAbsolute } from '@/services/url'

const props = defineProps({
  event: { type: Object, required: true },
  canRegister: { type: Boolean, default: false }, // le parent contrôle l'affichage du CTA
  registered: {
    /* bool optionnelle; si absente -> CTA neutre */
  },
  price: { type: Number, default: 0 }, // € (>=0)
  isFull: { type: Boolean, default: false }, // places restantes = 0 ?
})
const emit = defineEmits(['toggle-like', 'register', 'unregister', 'pay'])

const router = useRouter()
const fallback = '/img/demo.jpg'

const dateLabel = computed(() => {
  const d = props.event.startAt ? new Date(props.event.startAt) : null
  return d
    ? d.toLocaleDateString(undefined, { day: '2-digit', month: '2-digit', year: '2-digit' })
    : ''
})

const isPast = computed(() => {
  const s = props.event?.startAt ? new Date(props.event.startAt) : null
  return !!(s && s.getTime() < Date.now())
})

/** Normalise event.categories : [{slug,name}] ou ['Brunch','Concert'] */
const normCategories = computed(() => {
  const cats = Array.isArray(props.event.categories) ? props.event.categories : []
  return cats.map((c) => (typeof c === 'string' ? { slug: c, name: c } : c))
})
const hasCategories = computed(() => normCategories.value.length > 0)

const isPaidEvent = computed(() => Number(props.price ?? 0) > 0)
const priceLabel = computed(() => {
  const p = Number(props.price ?? 0)
  return p > 0 ? p.toFixed(2).replace('.', ',') + ' €' : null
})

const ctaDisabled = computed(() => props.isFull)
const ctaLabel = computed(() => {
  if (isPast.value) return 'Événement passé'
  if (props.isFull && !props.registered) return 'Complet'
  if (props.registered) return 'Se désinscrire'
  if (isPaidEvent.value)
    return `Payer et m’inscrire${priceLabel.value ? ' (' + priceLabel.value + ')' : ''}`
  return 'M’INSCRIRE'
})

function onCtaClick() {
  if (props.registered) {
    emit('unregister', props.event)
  } else if (isPaidEvent.value) {
    emit('pay', props.event) // le parent déclenche /events/:id/pay puis redirige
  } else {
    emit('register', props.event) // le parent appelle l’inscription gratuite
  }
}

function goToDetail() {
  if (props.event.id) {
    router.push({ name: 'eventDetail', params: { id: props.event.id } })
  }
}
</script>

<style scoped>
/* (inchangé, juste un @click.stop sur .content et sur le CTA) */
.event-card {
  overflow: hidden;
  cursor: pointer;
  transition:
    transform 0.08s ease,
    box-shadow 0.2s;
}
.event-card:hover {
  transform: translateY(-2px);
}

/* Cover */
.cover {
  position: relative;
  height: 180px;
  overflow: hidden;
}
.cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.cover::after {
  content: '';
  position: absolute;
  inset: auto 0 0 0;
  height: 42%;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.45) 100%);
}

/* Like */
.like {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.2);
  cursor: pointer;
}
.like span {
  color: #fff;
  font-size: 18px;
  line-height: 1;
}
.like span.on {
  color: var(--accent);
}

/* Date badge */
.date-badge {
  position: absolute;
  left: 10px;
  bottom: 10px;
  z-index: 1;
}

/* Body */
.content {
  padding: 12px 14px 16px;
}
.title {
  font:
    700 1.15rem/1.2 Poppins,
    Inter,
    system-ui;
  color: #fff;
  margin: 0;
}
.city {
  color: var(--muted);
  margin-top: 2px;
}

/* Tags */
.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin: 6px 0 4px;
  padding: 0;
  list-style: none;
}
.tag {
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.14);
  font-size: 0.82rem;
  color: var(--text);
  white-space: nowrap;
}

/* Description clamp */
.desc {
  color: var(--muted);
  font-size: 0.95rem;
  line-height: 1.35;
  margin: 8px 0 12px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Actions */
.actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: 4px;
}
.actions .link {
  font-weight: 700;
  font-size: 0.8rem;
  color: var(--primary);
}
</style>
