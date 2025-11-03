# 🎟️ EvenMont

EvenMont est une **plateforme web moderne** permettant de créer, gérer et rejoindre des événements.  
Le projet est conçu avec une **architecture découplée** : une API backend en Symfony et un frontend en Vue.js.

---

## 🧭 Structure générale du projet

EvenMont est composé de deux parties distinctes :

- **evenmont-api** : API backend en **PHP/Symfony**
- **evenmont-web** : Frontend web en **Vue.js/Vite**

```
evenmont-api/   # Backend Symfony
  src/           # Code source PHP
  config/        # Configuration (JWT, sécurité, routes...)
  public/        # Point d’entrée API
  var/, vendor/  # Dossiers ignorés

evenmont-web/    # Frontend Vue.js
  src/           # Code source Vue
  public/        # Fichiers statiques
  node_modules/, dist/ # Dossiers ignorés
```

---

## ⚙️ Technologies principales

- **Backend :** Symfony 6.4 (PHP 8.1), Doctrine ORM, LexikJWTAuthenticationBundle, GesdinetJWTRefreshTokenBundle  
- **Frontend :** Vue 3, Vite, Pinia, Axios  
- **Base de données :** MySQL  
- **Autres :** Symfony Mailer, Mollie (paiements), Upload d’images (validation MIME / 5 Mo)  
- **Hébergement :** OVH (sous-domaines séparés, HTTPS actif)

---

## 🚀 Fonctionnalités principales

- Création, gestion et inscription à des événements  
- Authentification sécurisée par **JWT**  
- Gestion des utilisateurs et rôles (`ROLE_USER`, `ROLE_ORGANIZER`, `ROLE_ADMIN`)  
- Système de commentaires et rapports  
- Envoi d’e-mails automatiques (confirmation, rappel, etc.)  
- Téléversement d’images avec validation  
- API RESTful complète  

---

## 🔐 Sécurité

- Authentification **JWT** via **LexikJWTAuthenticationBundle**  
- Tokens de rafraîchissement avec **GesdinetJWTRefreshTokenBundle**  
- Clés RSA (`private.pem`, `public.pem`) non versionnées  
- Routes sécurisées définies dans `security.yaml`  
- Vérification d’accès par **EventVoter** (propriétaire / rôle)  
- Validation stricte sur les fichiers uploadés (MIME + taille ≤ 5 Mo)

---

## 🧱 Installation

### 🔧 Backend (API Symfony)
```bash
cd evenmont-api
composer install
cp .env.example .env  # Adapter la configuration (DB, JWT, etc.)
php bin/console doctrine:migrations:migrate
```

### 💻 Frontend (Vue.js)
```bash
cd evenmont-web
npm install
cp .env.example .env  # Adapter la configuration API
npm run dev
```

---

## ▶️ Lancement du projet

- **API Symfony** :  
  `symfony server:start` ou  
  `php -S localhost:8000 -t public`

- **Frontend Vue** :  
  `npm run dev` → http://localhost:5173

---

## 🌐 Démo en ligne

- **Frontend :** [https://isl.yanasnytko.com](https://isl.yanasnytko.com)  
- **API :** [https://api.isl.yanasnytko.com](https://api.isl.yanasnytko.com)

---

## 🧩 Architecture générale

```
Frontend (Vue 3 / Pinia)  ⇄  API Symfony (JWT, REST)  ⇄  MySQL
           ↳ Uploads / Emails / Paiements (Mollie)
```

---

## 🔮 Évolutions prévues

- Notifications temps réel (WebSocket / Pusher)
- Application mobile Flutter connectée à la même API
- Tableau de bord administrateur avancé
- Multilingue complet (FR/EN + autres langues)

---

## 💡 Bonnes pratiques

- Les fichiers `.env` et les clés JWT **ne doivent jamais être versionnés**
- Respect du RGPD (données minimales, consentement newsletter)
- Code commenté et structuré pour faciliter la maintenance

---

## 👩‍💻 Contribution

1. Forker le projet  
2. Créer une branche (`feature/ma-nouvelle-fonctionnalite`)  
3. Commit + push  
4. Ouvrir une **Pull Request**

---

## 📜 Licence

Projet sous licence **MIT**  
Libre d’utilisation, de modification et de distribution.

---

## 🧾 Informations

Développé par **Yana Snytko**  
Travail de fin d’études – **Institut Saint-Laurent, Liège (2025)**  

---
