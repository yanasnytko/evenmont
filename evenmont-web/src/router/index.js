import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import EventsView from '@/views/EventsView.vue'
import { useAuth } from '@/stores/auth'

const routes = [
  { path: '/', name: 'home', component: HomeView },

  { path: '/events', name: 'events', component: EventsView },
  {
    path: '/event/:id',
    name: 'eventDetail',
    component: () => import('@/views/EventDetailView.vue'),
    props: true,
  },

  { path: '/login', name: 'login', component: () => import('@/views/LoginView.vue') },

  // protégées
  {
    path: '/organizer/create',
    name: 'createEvent',
    component: () => import('@/views/CreateEventView.vue'),
    meta: { requiresAuth: true, roles: ['ROLE_ORGANIZER'] },
  },
  {
    path: '/admin',
    name: 'admin',
    component: () => import('@/views/AdminDashboardView.vue'),
    meta: { requiresAuth: true, roles: ['ROLE_ADMIN'] },
  },
  {
    path: '/admin/moderation',
    name: 'moderation',
    component: () => import('@/views/ModerationView.vue'),
    meta: { requiresAuth: true, roles: ['ROLE_ADMIN'] },
  },

  // publiques
  { path: '/user/:id', name: 'user', component: () => import('@/views/UserView.vue'), props: true },
  {
    path: '/organizer/:id',
    name: 'organizer',
    component: () => import('@/views/OrganizerView.vue'),
    props: true,
  },
  {
    path: '/organizers',
    name: 'organizers',
    component: () => import('@/views/OrganizersView.vue'),
  },

  // 404
  {
    path: '/:pathMatch(.*)*',
    name: 'notfound',
    component: () => import('@/views/NotFoundView.vue'),
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/views/ProfileView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/views/ProfileView.vue'),
    meta: { requiresAuth: true },
  },
  { path: '/register', name: 'register', component: () => import('@/views/RegisterView.vue') },
  { path: '/payments/return', name: 'paymentReturn', component: () => import('@/views/PaymentReturnView.vue'), meta:{ public:true } },
  {
    path: '/events/:id/edit',
    name: 'editEvent',
    component: () => import('@/views/EventEditView.vue'),
    meta: { requiresAuth: true }, // optionnel
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, saved) {
    if (saved) return saved
    return { top: 0 }
  },
})

let bootstrapped = false

function hasRequiredRole(user, meta) {
  const roles = meta?.roles || (meta?.role ? [meta.role] : [])
  if (!roles.length) return true
  const userRoles = user?.roles || []
  return roles.some((r) => userRoles.includes(r))
}

router.beforeEach(async (to) => {
  const auth = useAuth()

  // 1er passage : hydrate la session seulement si on a déjà un token
  if (!bootstrapped) {
    const hasToken = !!sessionStorage.getItem('evenmont_at')
    if (hasToken && !auth.user) {
      try {
        await auth.me()
      } catch {
        /* 401 → laissé au guard de page */
      }
    }
    bootstrapped = true
  }

  // Routes protégées
  if (to.meta?.requiresAuth) {
    if (!auth.user) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }
    if (!hasRequiredRole(auth.user, to.meta)) {
      return { name: 'home' }
    }
  }

  // sinon continue
  return true
})

export default router
