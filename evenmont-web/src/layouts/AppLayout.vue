<template>
  <div class="app-layout" v-bind="$attrs">
    <NavBar />
    <div class="container" style="margin-top: 12px" v-if="showBanner">
      <VerifyBanner @close="onCloseBanner" />
    </div>
    <main class="container" style="padding: 24px 0">
      <slot />
    </main>
    <FooterBar />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuth } from '@/stores/auth'
import NavBar from '@/components/NavBar.vue'
import FooterBar from '@/components/FooterBar.vue'
import VerifyBanner from '@/components/VerifyBanner.vue'

const auth = useAuth()
const showBanner = computed(
  () =>
    !!auth.user && auth.user.email && auth.user.emailVerified === false && !auth.hideVerifyBanner,
)
function onCloseBanner() {
  auth.dismissVerifyBanner()
}
</script>
