<template>
  <div class="verify-banner" role="status" aria-live="polite">
    <span>✉️ Vérifie ton email pour activer ton compte. Le lien expire sous 24h.</span>
    <div class="actions">
      <!-- Optionnel : si tu ajoutes un endpoint de renvoi -->
      <!-- <button class="btn btn--ghost pill" @click="resend" :disabled="loading">Renvoyer le mail</button> -->
      <button class="btn pill" @click="$emit('close')">J’ai compris</button>
    </div>
  </div>
</template>

<script setup>
// import { api } from '@/services/api'
</script>

<style scoped>
.verify-banner {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  justify-content: space-between;
  color: var(--text);
  box-shadow: var(--shadow);
}
.actions {
  display: flex;
  gap: 8px;
}
</style>
