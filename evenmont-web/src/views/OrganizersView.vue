<template>
  <AppLayout>
    <!-- Header + recherche -->
    <section class="section">
      <div class="row" style="align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap">
        <h1 class="h1" style="margin:0">Organisateurs</h1>

        <form class="search" @submit.prevent="applyFilters" style="min-width:260px">
          <input v-model.trim="q" type="search" placeholder="Rechercher (nom, email…)" />
          <button aria-label="Rechercher" style="border:0;background:transparent;color:var(--text)">🔍</button>
        </form>
      </div>
    </section>

    <!-- Contenu -->
    <section class="section">
      <!-- erreurs -->
      <div v-if="error" class="alert alert--error">{{ error }}</div>

      <!-- loading -->
      <div v-else-if="loading" class="org-grid">
        <div v-for="n in limit" :key="n" class="card org-card" style="opacity:.6"></div>
      </div>

      <!-- vide -->
      <div v-else-if="!items.length" class="paper" style="padding:18px">
        Aucun organisateur trouvé.
      </div>

      <!-- liste -->
      <div v-else class="org-grid">
        <article v-for="o in items" :key="o.id" class="card org-card">
          <div class="org-cover" :style="{ backgroundImage: `url(${cover(o)})` }"></div>

          <div class="org-body">
            <div class="org-row">
              <AvatarCircle :src="o.avatarUrl" :name="o.name" :size="56" />
              <div class="meta">
                <h3 class="title">{{ o.name }}</h3>
                <p class="muted" v-if="o.email">{{ o.email }}</p>
              </div>
            </div>

            <div class="org-stats">
              <span class="badge badge--blue">{{ o.eventsCount ?? 0 }} évènement(s)</span>
              <RouterLink class="btn btn--ghost pill" :to="`/organizer/${o.id}`">Voir le profil</RouterLink>
            </div>
          </div>
        </article>
      </div>

      <!-- pagination -->
      <div v-if="pages>1" class="row" style="justify-content:center; gap:8px; margin-top:16px">
        <button class="btn btn--ghost pill" :disabled="page===1 || loading" @click="goPage(page-1)">Précédent</button>
        <span class="badge badge--blue">Page {{ page }} / {{ pages }}</span>
        <button class="btn pill" :disabled="page===pages || loading" @click="goPage(page+1)">Suivant</button>
      </div>
    </section>

  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import { api } from '@/services/api'
import AvatarCircle from '@/components/AvatarCircle.vue'
import { toAbsolute } from '@/services/url'

const route = useRoute()
const router = useRouter()

// filtres url-sync
const q = ref(route.query.q?.toString() || '')
const page = ref(Number(route.query.page) || 1)
const limit = 12

// data
const items = ref([])
const pages = ref(1)
const loading = ref(false)
const error = ref('')

// helpers UI
function cover(o){
  return o.banner || '/img/demo.jpg'
}

const params = computed(()=>({
  q: q.value || undefined,
  page: page.value,
  limit,
}))

async function load(){
  loading.value = true
  error.value = ''
  try{
    const { data } = await api.get('/organizers', { params: params.value })
    // supporte {items,total,page,pages}
    const list = Array.isArray(data?.items) ? data.items : (Array.isArray(data) ? data : [])
    items.value = list.map(normalizeOrganizer)
    pages.value = Number(data?.pages ?? 1)
  }catch{
    error.value = 'Impossible de charger les organisateurs'
  }finally{
    loading.value = false
  }
}

function normalizeOrganizer(o){
  // côté back, renvoie idéalement: {id, name, email, banner, eventsCount}
  const name = o.name || [o.firstName, o.lastName].filter(Boolean).join(' ') || (o.email ?? 'Organisateur')
  return {
    id: o.id,
    name,
    email: o.email ?? '',
    banner: o.banner ?? o.coverUrl ?? '',
    eventsCount: o.eventsCount ?? o.events_count ?? 0,
    avatarUrl: toAbsolute(o.avatarUrl || ''),
  }
}

function applyFilters(){
  page.value = 1
  router.replace({ query: { q: q.value || undefined, page: 1 } })
}
function goPage(p){
  page.value = Math.min(Math.max(1, p), pages.value)
  router.replace({ query: { ...route.query, page: page.value } })
}

onMounted(load)
watch(()=> route.query, ()=>{
  q.value = route.query.q?.toString() || ''
  page.value = Number(route.query.page) || 1
  load()
})
</script>

<style scoped>
.org-grid{
  display:grid; gap:18px; grid-template-columns: repeat(4, 1fr);
}
@media (max-width: 1100px){ .org-grid{ grid-template-columns: repeat(3, 1fr);} }
@media (max-width: 800px){ .org-grid{ grid-template-columns: repeat(2, 1fr);} }
@media (max-width: 520px){ .org-grid{ grid-template-columns: 1fr;} }

.org-card{
  overflow:hidden;
  display:flex; flex-direction:column;
}
.org-cover{
  height:120px; background-size:cover; background-position:center;
}
.org-body{ padding:14px 16px 16px; display:grid; gap:10px; }
.org-row{ display:flex; gap:12px; align-items:center; }
.avatar{
  width:44px; height:44px; border-radius:50%;
  color:#fff; font-weight:700; display:flex; align-items:center; justify-content:center;
  box-shadow: var(--shadow);
}
.title{ font-family:Poppins; margin:0; }
.org-stats{ display:flex; justify-content:space-between; align-items:center; gap:10px; margin-top:6px; }
.mt-2{ margin-top: .75rem; }
</style>
