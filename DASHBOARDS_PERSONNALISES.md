# Dashboards Personnalisés par Rôle - Pharma App

## Vue d'ensemble

Le système de dashboards personnalisés permet à chaque type d'utilisateur d'avoir une interface adaptée à ses besoins spécifiques et ses responsabilités.

## Architecture

### Héritage des Fonctionnalités

L'**Autorité de Santé** hérite de toutes les fonctionnalités de base de `User` :
- Authentification
- Notifications
- Profil utilisateur
- Historique d'activités

### Modèles Spécialisés

#### AutoriteSante extends User
```php
class AutoriteSante extends User
{
    // Hérite de toutes les fonctionnalités User
    // + Fonctionnalités spécifiques de surveillance
}
```

## Dashboards par Rôle

### 1. Dashboard Client (`/api/dashboard/client`)

**Fonctionnalités spécifiques :**
- Suivi santé personnel
- Historique des ordonnances
- Médicaments réguliers
- Pharmacies favorites
- Conseils santé personnalisés

**Données affichées :**
```json
{
  "sante_personnelle": {
    "ordonnances_actives": 3,
    "medicaments_reguliers": [...],
    "prochains_renouvellements": [...]
  },
  "pharmacies_favorites": [...],
  "conseils_sante": [...]
}
```

### 2. Dashboard Pharmacien (`/api/dashboard/pharmacien`)

**Fonctionnalités spécifiques :**
- Gestion quotidienne de la pharmacie
- Suivi des ordonnances
- Gestion des stocks
- Performance et revenus
- Clients réguliers

**Données affichées :**
```json
{
  "activite_quotidienne": {
    "ordonnances_recues_aujourd_hui": 15,
    "chiffre_affaires_estime": 125000
  },
  "gestion_stocks": {
    "produits_stock_faible": 8,
    "valeur_stock_total": 2500000
  },
  "performance_pharmacie": {
    "taux_validation": 95.2,
    "satisfaction_client": 92
  }
}
```

### 3. Dashboard Autorité de Santé (`/api/dashboard/autorite`)

**Fonctionnalités spécifiques :**
- Surveillance réglementaire
- Statistiques de santé publique
- Contrôles qualité
- Alertes prioritaires
- Rapports disponibles

**Données affichées :**
```json
{
  "surveillance_reglementaire": {
    "ordonnances_controlees": 1250,
    "prescriptions_suspectes": 12,
    "taux_conformite_global": 94.8
  },
  "controles_qualite": {
    "pharmacies_auditees": 45,
    "non_conformites_detectees": 3
  },
  "alertes_prioritaires": {
    "prescriptions_anormales": 2,
    "stocks_critiques": 15,
    "pharmacies_non_conformes": 1
  }
}
```

## Contrôleurs Spécialisés

### Structure des Contrôleurs

```
DashboardController (principal)
├── ClientDashboardController
├── PharmacienDashboardController
└── AutoriteSanteDashboardController
```

### Méthodes Communes

Chaque contrôleur spécialisé implémente :
- `dashboard(Request $request)` - Point d'entrée principal
- Méthodes privées pour chaque section de données
- Gestion des notifications non lues

## Endpoints API

### Dashboards Génériques
- `GET /api/dashboard` - Dashboard intelligent selon le rôle

### Dashboards Spécialisés
- `GET /api/dashboard/client` - Dashboard client détaillé
- `GET /api/dashboard/pharmacien` - Dashboard pharmacien détaillé  
- `GET /api/dashboard/autorite` - Dashboard autorité de santé détaillé

## Fonctionnalités Avancées

### Autorité de Santé

#### Surveillance Réglementaire
- Contrôle des prescriptions
- Audit des pharmacies
- Suivi de conformité
- Détection d'anomalies

#### Rapports Disponibles
- Dispensation mensuelle
- Audit pharmacies
- Consommation médicaments
- Pharmacovigilance

#### Alertes Prioritaires
- Prescriptions anormales
- Stocks critiques
- Pharmacies non conformes
- Médicaments expirés

### Pharmacien

#### Gestion Avancée
- Rotation des stocks
- Temps de traitement moyen
- Évolution de l'activité
- Clients réguliers

#### Performance
- Taux de validation
- Satisfaction client
- Revenus mensuels
- Chiffre d'affaires quotidien

### Client

#### Suivi Personnel
- Médicaments réguliers
- Historique médical
- Prochains renouvellements
- Pharmacies visitées

## Base de Données

### Tables Ajoutées

#### controles_autorite
```sql
- id
- autorite_id (FK users)
- pharmacie_id (FK pharmacies)
- type_controle (ROUTINE, ALERTE, PLAINTE)
- resultat (CONFORME, NON_CONFORME, EN_COURS)
- observations
- criteres_evalues (JSON)
- date_controle
```

#### Champs ajoutés à users
```sql
- code_autorisation
- type_controle  
- organisme
```

## Utilisation

### Authentification
Tous les dashboards nécessitent une authentification via Bearer Token.

### Exemple d'utilisation
```javascript
// Dashboard spécialisé autorité de santé
const response = await fetch('/api/dashboard/autorite', {
  headers: {
    'Authorization': 'Bearer ' + token,
    'Accept': 'application/json'
  }
});

const dashboard = await response.json();
console.log(dashboard.surveillance_reglementaire);
```

### Comptes de Test

#### Autorité de Santé
- Email: `autorite@sante.sn`
- Password: `password`
- Code: `AS-SN-2024-001`

#### Contrôle Pharmacovigilance  
- Email: `controle@sante.sn`
- Password: `password`
- Code: `AS-SN-2024-002`

## Migration et Déploiement

### Commandes à exécuter
```bash
# Migrer les nouvelles tables
php artisan migrate

# Seeder les données de test
php artisan db:seed --class=AutoriteSanteSeeder
```

### Vérification
```bash
# Tester les endpoints
curl -H "Authorization: Bearer {token}" http://localhost:8000/api/dashboard/autorite
```

## Sécurité

- Middleware de rôle pour chaque endpoint
- Validation des permissions par contrôleur
- Isolation des données par rôle
- Logs des actions sensibles (contrôles, audits)