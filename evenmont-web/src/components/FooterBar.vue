<template>
  <footer class="footer">
    <div class="container foot-grid">
      <!-- Marque + réseaux -->
      <div class="brand">
        <strong style="color: #fff">By EvenMont</strong>
        <div class="icons">
          <a href="#" aria-label="LinkedIn" rel="noopener">
            <!-- petit rond avec “in” -->
            <span class="icon">in</span>
          </a>
          <a href="#" aria-label="Facebook" rel="noopener">
            <span class="icon">f</span>
          </a>
        </div>
      </div>

      <!-- Liens -->
      <nav class="links">
        <RouterLink to="/legal">Mentions légales</RouterLink>
        <RouterLink to="/gdpr">RGPD</RouterLink>
        <RouterLink to="/contact">Nous contacter</RouterLink>
      </nav>

      <!-- Recherche -->
      <form class="search foot-search" @submit.prevent>
        <input placeholder="Rechercher…" />
        <button
          aria-label="Rechercher"
          style="border: 0; background: transparent; color: var(--text)"
        >
          🔍
        </button>
      </form>
    </div>
  </footer>
</template>

<style scoped>
/* On s’appuie sur .footer du theme.css pour la base (bordure, couleur, padding) */

/* Grille */
.foot-grid {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 16px;
  align-items: center;
}

/* Marque + icônes */
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
}
.icons {
  display: flex;
  gap: 8px;
}
.icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: var(--text);
  font-weight: 700;
  line-height: 1;
}

/* Liens */
.links {
  display: flex;
  gap: 24px;
}
.links a {
  color: var(--muted);
  font-weight: 600;
}
.links a:hover {
  color: var(--text);
}

/* Recherche : on réutilise le style .search global et on ajuste la largeur */
.foot-search {
  justify-self: end;
}
.foot-search input {
  width: 180px;
}

/* Responsive */
@media (max-width: 820px) {
  .foot-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .links {
    flex-wrap: wrap;
    gap: 16px;
  }
  .foot-search {
    justify-self: start;
    width: 100%;
  }
  .foot-search input {
    width: 100%;
  }
}
</style>
