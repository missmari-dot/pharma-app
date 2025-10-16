# 🗺️ Configuration Google Maps - Pharma App

## 🚀 **Étapes de Configuration**

### 1️⃣ **Créer un Projet Google Cloud**
1. Aller sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créer un nouveau projet : "Pharma App"
3. Activer la facturation (carte bancaire requise mais pas de charge)

### 2️⃣ **Activer les APIs Nécessaires**
Dans Google Cloud Console, activer :
- ✅ **Geocoding API** - Convertir adresses → coordonnées
- ✅ **Distance Matrix API** - Calculer distances/temps de trajet
- ✅ **Maps JavaScript API** - Afficher cartes (pour frontend)
- ✅ **Places API** - Recherche de lieux (optionnel)

### 3️⃣ **Créer une Clé API**
1. Aller dans "APIs & Services" → "Credentials"
2. Cliquer "Create Credentials" → "API Key"
3. Copier la clé générée

### 4️⃣ **Sécuriser la Clé API**
1. Cliquer sur la clé créée
2. Dans "API restrictions" → Sélectionner les APIs activées
3. Dans "Application restrictions" → "HTTP referrers" pour web OU "IP addresses" pour serveur

### 5️⃣ **Configurer Laravel**
```bash
# Dans votre fichier .env
GOOGLE_MAPS_API_KEY=AIzaSyBvOkBwGyOnOiKagvGmN0WT4WzlzOiTPHo
```

## 📋 **Routes API Mises à Jour**

### **Pharmacies Proches (avec Google Maps)**
```bash
GET /api/pharmacies-proches?latitude=14.6928&longitude=-17.4467&radius=5&use_google=true
```

**Réponse avec Google Maps :**
```json
[
  {
    "id": 1,
    "nom_pharmacie": "Pharmacie Lotty",
    "distance": 0.3,
    "duration": "2 mins",
    "latitude": "14.69370000",
    "longitude": "-17.44410000"
  }
]
```

### **Géocodage Google Maps**
```bash
POST /api/geocode
Content-Type: application/json

{
  "address": "Place de l'Indépendance"
}
```

**Réponse :**
```json
{
  "latitude": 14.6928,
  "longitude": -17.4467,
  "formatted_address": "Place de l'Indépendance, Dakar, Sénégal"
}
```

## 💰 **Coûts et Quotas**

### **Gratuit Mensuel :**
- **Geocoding** : 40,000 requêtes
- **Distance Matrix** : 40,000 éléments  
- **Maps JavaScript** : 28,000 chargements

### **Après Quota Gratuit :**
- **Geocoding** : 5$ / 1,000 requêtes
- **Distance Matrix** : 5$ / 1,000 éléments
- **Maps JavaScript** : 7$ / 1,000 chargements

## 🔄 **Système Hybride Implémenté**

### **Avec Google Maps (use_google=true)**
- ✅ Précision maximale
- ✅ Temps de trajet réel
- ✅ Adresses formatées

### **Sans Google Maps (fallback)**
- ✅ Calcul local (formule Haversine)
- ✅ Gratuit illimité
- ✅ Fonctionne sans clé API

## 🧪 **Tests**

### **Test avec Google Maps :**
```bash
curl -X GET "http://localhost:8000/api/pharmacies-proches?latitude=14.6928&longitude=-17.4467&use_google=true" -H "Accept: application/json"
```

### **Test sans Google Maps :**
```bash
curl -X GET "http://localhost:8000/api/pharmacies-proches?latitude=14.6928&longitude=-17.4467" -H "Accept: application/json"
```

### **Test géocodage :**
```bash
curl -X POST http://localhost:8000/api/geocode -H "Content-Type: application/json" -d '{"address":"Plateau, Dakar"}'
```

## 🎯 **Recommandations**

### **Phase 1 : Développement**
- Utilisez le **fallback local** (gratuit)
- Testez sans clé Google Maps

### **Phase 2 : Production**
- Ajoutez la **clé Google Maps**
- Activez `use_google=true` dans vos apps
- Meilleure expérience utilisateur

### **Monitoring**
- Surveillez l'usage dans Google Cloud Console
- Alertes avant d'atteindre les quotas
- Optimisez les requêtes si nécessaire

## ✅ **Avantages de Cette Approche**

- 🆓 **Démarrage gratuit** - Fonctionne sans Google Maps
- 🔄 **Migration progressive** - Ajoutez Google Maps quand prêt
- 💰 **Contrôle des coûts** - Fallback automatique
- 🚀 **Meilleure UX** - Précision Google quand disponible

Votre app est maintenant prête pour Google Maps avec fallback intelligent ! 🎉