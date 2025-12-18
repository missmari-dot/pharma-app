# 🚂 Déploiement Railway - PharmaMobile

## 🚀 Étapes de Déploiement

### 1. Préparation du Projet
```bash
# Générer une nouvelle clé d'application
php artisan key:generate --show
# Copier la clé générée
```

### 2. Connexion à Railway
1. Aller sur [railway.app](https://railway.app)
2. Se connecter avec GitHub
3. Cliquer sur "New Project"
4. Sélectionner "Deploy from GitHub repo"
5. Choisir votre repository pharma-app

### 3. Configuration Base de Données
1. Dans Railway, cliquer sur "+ New"
2. Sélectionner "Database" → "MySQL"
3. Attendre que la DB soit créée
4. Noter les variables d'environnement générées

### 4. Configuration Variables d'Environnement
Dans Railway, aller dans l'onglet "Variables" et ajouter :

```env
APP_NAME=PharmaMobile
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_GENEREE_ETAPE_1
APP_DEBUG=false
APP_URL=https://votre-app.up.railway.app

# Les variables DB sont automatiques avec MySQL Railway
# Ajouter manuellement :
FCM_SERVER_KEY=votre_server_key_firebase
```

### 5. Déploiement
1. Railway détecte automatiquement le Dockerfile
2. Le build commence automatiquement
3. Attendre la fin du déploiement (5-10 minutes)

### 6. Migration Base de Données
Une fois déployé, dans Railway :
1. Aller dans l'onglet "Deploy"
2. Cliquer sur le dernier déploiement
3. Ouvrir le terminal et exécuter :
```bash
php artisan migrate --force
php artisan db:seed --force
```

## 🔧 Commandes Utiles Railway

### Logs en temps réel
```bash
# Installer Railway CLI
npm install -g @railway/cli

# Se connecter
railway login

# Voir les logs
railway logs
```

### Accès Terminal
```bash
railway shell
```

### Variables d'environnement
```bash
railway variables
```

## 🌐 URLs Importantes

- **Application :** `https://votre-app.up.railway.app`
- **API :** `https://votre-app.up.railway.app/api`
- **Health Check :** `https://votre-app.up.railway.app/api/health`

## 🔍 Vérifications Post-Déploiement

1. **Health Check :** Vérifier `/api/health`
2. **API Test :** Tester `/api/pharmacies`
3. **Base de Données :** Vérifier les tables créées
4. **Firebase :** Tester les notifications

## 🐛 Dépannage

### Erreur 500
- Vérifier les logs : `railway logs`
- Vérifier APP_KEY définie
- Vérifier connexion DB

### Migration échoue
```bash
railway shell
php artisan migrate:fresh --force
php artisan db:seed --force
```

### Permissions fichiers
```bash
railway shell
chmod -R 775 storage bootstrap/cache
```

## 💰 Coûts Railway

- **Hobby Plan :** Gratuit (500h/mois)
- **Pro Plan :** $5/mois (illimité)
- **Base de données :** $5/mois

## ⚡ Avantages Railway

- ✅ Déploiement automatique
- ✅ Base de données intégrée
- ✅ SSL automatique
- ✅ Monitoring inclus
- ✅ Scaling automatique

**Temps total de déploiement : 15-20 minutes** 🚀