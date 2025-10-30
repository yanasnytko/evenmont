<!-- src/views/NotFoundView.vue -->
<template>
  <AppLayout>
    <section class="section" style="text-align: center">
      <h1 class="h1">404 — Page introuvable</h1>
      <p class="muted">Désolé, cette page n’existe pas ou a été déplacée.</p>

      <div class="row" style="justify-content: center; gap: 10px; margin-top: 14px">
        <RouterLink to="/" class="btn pill">Revenir à l’accueil</RouterLink>
        <RouterLink to="/events" class="btn btn--ghost pill">Voir les événements</RouterLink>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
</script>
