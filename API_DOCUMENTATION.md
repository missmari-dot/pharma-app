# API Documentation - Pharma App

## Base URL
```
http://localhost:8000/api
```

## Authentification
Utilise Laravel Sanctum avec Bearer Token dans le header :
```
Authorization: Bearer {token}
```

## Endpoints

### 🔐 Authentification

#### Inscription
```http
POST /register
Content-Type: application/json

{
    "nom": "Fatou Sall",
    "email": "fatou@example.com",
    "password": "password123",
    "telephone": "221771234567",
    "adresse": "Dakar, Sénégal",
    "date_naissance": "1990-01-01",
    "role": "client"
}
```

#### Connexion
```http
POST /login
Content-Type: application/json

{
    "email": "fatou@example.com",
    "password": "password123"
}
```

#### Déconnexion
```http
POST /logout
Authorization: Bearer {token}
```

### 🏥 Pharmacies

#### Lister toutes les pharmacies
```http
GET /pharmacies
```

#### Pharmacies proches
```http
POST /pharmacies/proches
Content-Type: application/json

{
    "latitude": 14.6937,
    "longitude": -17.4441,
    "radius": 5
}
```

#### Pharmacies de garde
```http
GET /pharmacies/garde
```

### 💊 Produits

#### Lister les produits
```http
GET /produits?search=paracetamol&categorie=MEDICAMENT
```

#### Rechercher des produits
```http
POST /produits/rechercher
Content-Type: application/json

{
    "terme": "paracetamol"
}
```

### 📋 Ordonnances (Authentifié)

#### Envoyer une ordonnance
```http
POST /ordonnances
Authorization: Bearer {token}
Content-Type: multipart/form-data

pharmacie_id: 1
photo_ordonnance: [fichier image]
```

#### Lister mes ordonnances
```http
GET /ordonnances
Authorization: Bearer {token}
```

#### Valider une ordonnance (Pharmacien)
```http
PATCH /ordonnances/{id}/valider
Authorization: Bearer {token_pharmacien}
```

### 📅 Réservations (Authentifié)

#### Créer une réservation
```http
POST /reservations
Authorization: Bearer {token}
Content-Type: application/json

{
    "ordonnance_id": 1
}
```

#### Lister mes réservations
```http
GET /reservations
Authorization: Bearer {token}
```

#### Confirmer retrait (Pharmacien)
```http
PATCH /reservations/{id}/confirmer
Authorization: Bearer {token_pharmacien}
```

### 🔔 Notifications (Authentifié)

#### Lister mes notifications
```http
GET /notifications
Authorization: Bearer {token}
```

#### Marquer comme lue
```http
PATCH /notifications/{id}/lire
Authorization: Bearer {token}
```

### 💊 Gestion des Stocks (Pharmacien)

#### Consulter les stocks d'une pharmacie
```http
GET /pharmacies/{pharmacie_id}/stocks
Authorization: Bearer {token_pharmacien}
```

#### Mettre à jour le stock d'un produit
```http
PATCH /pharmacies/{pharmacie_id}/stocks/{produit_id}
Authorization: Bearer {token_pharmacien}
Content-Type: application/json

{
    "quantite_disponible": 50,
    "quantite_reservee": 5
}
```

#### Incrémenter le stock
```http
POST /pharmacies/{pharmacie_id}/stocks/{produit_id}/incrementer
Authorization: Bearer {token_pharmacien}
Content-Type: application/json

{
    "quantite": 10
}
```

### 🏥 Conseils Santé

#### Lister les conseils santé (Public)
```http
GET /conseils-sante?search=diabète&categorie=Prévention
```

#### Publier un conseil santé (Pharmacien)
```http
POST /conseils-sante
Authorization: Bearer {token_pharmacien}
Content-Type: application/json

{
    "titre": "Prévention du diabète",
    "contenu": "Le diabète est une maladie chronique...",
    "categorie": "Prévention"
}
```

#### Modifier un conseil santé
```http
PATCH /conseils-sante/{id}
Authorization: Bearer {token_pharmacien}
Content-Type: application/json

{
    "titre": "Nouveau titre",
    "contenu": "Contenu mis à jour"
}
```

### 🏛️ Autorité de Santé (Rôle spécial)

#### Rapport de dispensation
```http
GET /autorite/rapport-dispensation?date_debut=2024-01-01&date_fin=2024-12-31
Authorization: Bearer {token_autorite}
```

#### Audit des pharmacies
```http
GET /autorite/audit-pharmacies
Authorization: Bearer {token_autorite}
```

#### Statistiques de consommation
```http
GET /autorite/statistiques-consommation
Authorization: Bearer {token_autorite}
```

### 👨‍💼 Administration (Admin)

#### Dashboard administrateur
```http
GET /admin/dashboard
Authorization: Bearer {token_admin}
```

#### Gérer un utilisateur
```http
PATCH /admin/utilisateurs/{id}
Authorization: Bearer {token_admin}
Content-Type: application/json

{
    "action": "suspendre"
}
```

#### Statistiques d'utilisation
```http
GET /admin/statistiques
Authorization: Bearer {token_admin}
```

## Comptes de test

### Client
- Email: `client@pharma.sn`
- Password: `password`
- Rôle: `client`
- Nom: `Fatou Sall`

### Pharmacien
- Email: `pharmacien@pharma.sn`
- Password: `password`
- Rôle: `pharmacien`
- Nom: `Dr. Amadou Diallo`

### Administrateur
- Email: `admin@pharma.sn`
- Password: `password`
- Rôle: `admin`
- Nom: `Admin Pharma`

### 📊 Dashboards Personnalisés (Authentifié)

#### Dashboard intelligent par rôle
```http
GET /dashboard
Authorization: Bearer {token}
```

#### Dashboard Client spécialisé
```http
GET /dashboard/client
Authorization: Bearer {token_client}
```

#### Dashboard Pharmacien spécialisé
```http
GET /dashboard/pharmacien
Authorization: Bearer {token_pharmacien}
```

#### Dashboard Autorité de Santé spécialisé
```http
GET /dashboard/autorite
Authorization: Bearer {token_autorite}
```

### Autorité de Santé
- Email: `autorite@sante.sn`
- Password: `password`
- Rôle: `autorite_sante`
- Code: `AS-SN-2024-001`
- Nom: `Dr. Amadou Diallo`

### Contrôle Pharmacovigilance
- Email: `controle@sante.sn`
- Password: `password`
- Rôle: `autorite_sante`
- Code: `AS-SN-2024-002`
- Nom: `Dr. Fatou Ndiaye`

## Rôles disponibles
- `client` - Utilisateur final
- `pharmacien` - Professionnel de santé
- `admin` - Administrateur système
- `autorite_sante` - Contrôle réglementaire (hérite de User)
