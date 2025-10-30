<template>
  <header class="nav">
    <div class="container bar">
      <!-- Brand -->
      <router-link to="/" class="brand" aria-label="Accueil EvenMont">
        <img src="/logo-mark.svg" alt="EvenMont" class="logo" />
        <strong>EvenMont</strong>
      </router-link>

      <!-- Desktop: liens + actions -->
      <nav class="links desktop-only">
        <router-link to="/events" active-class="active">Événements</router-link>
        <router-link to="/organizers" active-class="active">Organisateurs</router-link>

        <router-link v-if="auth.user" to="/profile" active-class="active">Mon espace</router-link>
        <router-link v-else to="/login" active-class="active">Connexion</router-link>

        <router-link
          v-if="auth.user?.roles?.includes('ROLE_ORGANIZER')"
          to="/organizer/create"
          class="btn btn-ghost center"
          >Créer un événement</router-link
        >
        <button class="btn btn-ghost" @click="onLogout" v-if="auth.user">Déconnexion</button>
        <router-link to="/register" active-class="active" v-if="!auth.user"
          >Créer un compte</router-link
        >
      </nav>

      <!-- Desktop: search -->
      <form
        class="search desktop-only"
        @submit.prevent="$router.push({ name: 'events', query: { q: keyword } })"
      >
        <input v-model="keyword" type="search" placeholder="Rechercher…" />
        <button aria-label="Rechercher" class="ghost-btn">🔍</button>
      </form>

      <!-- Mobile: hamburger -->
      <button
        class="hamburger mobile-only"
        :aria-expanded="open ? 'true' : 'false'"
        aria-controls="mobile-menu"
        @click="toggle()"
      >
        <span class="bar1" :class="{ open }"></span>
        <span class="bar2" :class="{ open }"></span>
        <span class="bar3" :class="{ open }"></span>
        <span class="sr-only">Menu</span>
      </button>
    </div>

    <!-- Mobile drawer + backdrop -->
    <transition name="fade">
      <div v-show="open" class="backdrop" @click="close()" />
    </transition>

    <transition name="slide">
      <div
        v-show="open"
        id="mobile-menu"
        class="drawer"
        role="dialog"
        aria-modal="true"
        @keydown.esc="close"
      >
        <div class="drawer-head">
          <span class="title">Menu</span>
          <button class="ghost-btn" @click="close" aria-label="Fermer">✕</button>
        </div>

        <form class="search" @submit.prevent="submitMobileSearch">
          <input v-model="keyword" type="search" placeholder="Rechercher…" />
          <button class="ghost-btn" aria-label="Rechercher">🔍</button>
        </form>

        <nav class="m-links">
          <RouterLink @click="close" to="/events">Événements</RouterLink>
          <RouterLink @click="close" to="/organizers">Organisateurs</RouterLink>

          <RouterLink v-if="auth.user" @click="close" to="/profile">Mon espace</RouterLink>
          <RouterLink v-else @click="close" to="/login">Connexion</RouterLink>

          <RouterLink
            v-if="auth.user?.roles?.includes('ROLE_ORGANIZER')"
            @click="close"
            to="/organizer/create"
            class="btn pill"
            >Créer un événement</RouterLink
          >

          <button v-if="auth.user" class="btn btn-ghost pill" @click="logoutMobile">
            Déconnexion
          </button>
          <RouterLink v-else @click="close" to="/register">Créer un compte</RouterLink>
        </nav>
      </div>
    </transition>
  </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watchEffect } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/stores/auth'

const auth = useAuth()
const router = useRouter()
const keyword = ref('')
const open = ref(false)

function toggle() {
  open.value = !open.value
}
function close() {
  open.value = false
}

async function onLogout() {
  await auth.logout()
  router.replace({ name: 'home' })
}

async function logoutMobile() {
  await onLogout()
  close()
}

function submitMobileSearch() {
  router.push({ name: 'events', query: { q: keyword.value } })
  close()
}

// fermer au changement de route
const stop = router.afterEach(() => {
  close()
})
onBeforeUnmount(() => stop())

// scroll lock body quand menu ouvert
function lockBodyScroll(lock) {
  document.documentElement.style.overflow = lock ? 'hidden' : ''
}
onMounted(() => {
  watchEffect(() => lockBodyScroll(open.value))
})
</script>

<style scoped>
/* Accessibilité */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

/* Layout de base (utilise .nav et .bar de theme.css) */
.logo {
  height: 28px;
  width: auto;
  display: block;
}
.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #fff;
  font-family: Poppins, Inter, system-ui;
  font-weight: 700;
}

/* Desktop only */
.desktop-only {
  display: flex;
}
.mobile-only {
  display: none !important;
}

/* Liens desktop */
.links {
  display: flex;
  gap: 20px;
  margin-left: 24px;
  align-items: center;
}
.links a {
  padding: 10px 0;
  color: var(--muted);
  font-weight: 600;
  opacity: 0.92;
}
.links a.active,
.links a:hover {
  color: #fff;
  opacity: 1;
}

/* Bouton “ghost” pour search & close */
.ghost-btn {
  border: 0;
  background: transparent;
  color: var(--text);
  cursor: pointer;
  font-size: 18px;
}

/* Hamburger */
.hamburger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: var(--card);
  border: 1px solid rgba(255, 255, 255, 0.14);
  cursor: pointer;
  box-shadow: var(--shadow);
}
.hamburger:hover {
  border-color: rgba(255, 255, 255, 0.22);
}
.hamburger span {
  display: block;
  width: 20px;
  height: 2px;
  background: #fff;
  margin: 3px 0;
  transition:
    transform 0.2s,
    opacity 0.2s;
}
.bar1.open {
  transform: translateY(5px) rotate(45deg);
}
.bar2.open {
  opacity: 0;
}
.bar3.open {
  transform: translateY(-5px) rotate(-45deg);
}

/* Backdrop + drawer */
.backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(2px);
}
.drawer {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  width: 82%;
  max-width: 360px;
  background: var(--card);
  border-left: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: var(--shadow);
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 14px 14px 18px;
}
.drawer-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.drawer .title {
  font-weight: 700;
  font-family: Poppins;
}

/* Liens mobile */
.m-links {
  display: grid;
  gap: 10px;
}
.m-links a {
  padding: 10px 8px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.06);
  color: var(--text);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.m-links a.router-link-active {
  background: rgba(35, 87, 137, 0.18);
  border-color: rgba(35, 87, 137, 0.35);
}

.pill {
  border-radius: 999px;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.12s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.18s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}

/* Responsive rules */
@media (max-width: 820px) {
  .nav {
    background: #0c2030; /* plein */
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: none; /* enlève le blur translucide */
  }

  .desktop-only {
    display: none !important;
  }
  .mobile-only {
    display: inline-flex !important;
  }
}
</style>
