# Dashboards Personnalisés par Rôle

## 🎯 Route Intelligente
```http
GET /api/dashboard
Authorization: Bearer {token}
```
**Cette route unique retourne automatiquement le dashboard approprié selon le rôle de l'utilisateur connecté.**

---

## 👤 Dashboard CLIENT

### Exemple de réponse :
```json
{
  "role": "client",
  "utilisateur": {
    "nom": "Fatou Sall",
    "email": "client@pharma.sn",
    "membre_depuis": "il y a 2 mois"
  },
  "ordonnances": {
    "total": 15,
    "en_attente": 2,
    "validees": 12,
    "rejetees": 1
  },
  "reservations": {
    "actives": 1,
    "confirmees": 8,
    "total": 9
  },
  "activites_recentes": {
    "ordonnances_recentes": [...],
    "reservations_recentes": [...]
  },
  "pharmacies_proches": [...],
  "notifications_non_lues": 3
}
```

### Fonctionnalités Client :
- ✅ Suivi des ordonnances en temps réel
- ✅ Historique des réservations
- ✅ Pharmacies de garde à proximité
- ✅ Notifications personnalisées

---

## 👨⚕️ Dashboard PHARMACIEN

### Exemple de réponse :
```json
{
  "role": "pharmacien",
  "utilisateur": {
    "nom": "Dr. Amadou Diallo",
    "email": "pharmacien@pharma.sn",
    "pharmacie": "Pharmacie du Plateau"
  },
  "ordonnances_aujourd_hui": {
    "recues": 25,
    "en_attente": 5,
    "traitees": 20
  },
  "reservations": {
    "en_attente": 8,
    "retirees_aujourd_hui": 12
  },
  "produits_stock_faible": [...],
  "activites_recentes": {
    "ordonnances_recentes": [...],
    "reservations_recentes": [...]
  },
  "pharmacie_info": {
    "est_de_garde": true,
    "horaires": "08:00 - 20:00"
  },
  "notifications_non_lues": 7
}
```

### Fonctionnalités Pharmacien :
- ✅ Gestion des ordonnances en attente
- ✅ Suivi des stocks en temps réel
- ✅ Alertes de rupture de stock
- ✅ Statistiques de dispensation

---

## 👨💼 Dashboard ADMINISTRATEUR

### Exemple de réponse :
```json
{
  "role": "admin",
  "utilisateur": {
    "nom": "Admin Système",
    "email": "admin@pharma.sn",
    "privileges": "Administrateur Système"
  },
  "statistiques_globales": {
    "utilisateurs_total": 1250,
    "clients": 1180,
    "pharmaciens": 65,
    "pharmacies": 45,
    "nouveaux_utilisateurs_semaine": 23
  },
  "activite_systeme": {
    "ordonnances_aujourd_hui": 156,
    "reservations_aujourd_hui": 89,
    "connexions_actives": 34
  },
  "alertes_systeme": {
    "utilisateurs_suspendus": 2,
    "ordonnances_en_attente": 12,
    "erreurs_systeme": 0
  },
  "pharmacies_actives": 42,
  "notifications_non_lues": 5
}
```

### Fonctionnalités Admin :
- ✅ Vue d'ensemble du système
- ✅ Gestion des utilisateurs
- ✅ Monitoring en temps réel
- ✅ Alertes système critiques

---

## 🏛️ Dashboard AUTORITÉ DE SANTÉ

### Exemple de réponse :
```json
{
  "role": "autorite_sante",
  "utilisateur": {
    "nom": "Inspecteur Santé",
    "email": "autorite@sante.sn",
    "organisme": "Autorité de Santé du Sénégal"
  },
  "surveillance_reglementaire": {
    "ordonnances_controlees": 2450,
    "prescriptions_suspectes": 15,
    "pharmacies_surveillees": 45,
    "controles_effectues": 8
  },
  "statistiques_sante_publique": {
    "medicaments_dispenses": 1890,
    "ordonnances_ce_mois": 456,
    "pharmacies_conformes": 43,
    "alertes_pharmacovigilance": 2
  },
  "rapports_disponibles": {
    "dispensation_mensuelle": true,
    "audit_pharmacies": true,
    "consommation_medicaments": true,
    "prescriptions_analysees": true
  },
  "notifications_non_lues": 12
}
```

### Fonctionnalités Autorité :
- ✅ Surveillance réglementaire
- ✅ Rapports de conformité
- ✅ Détection d'anomalies
- ✅ Statistiques de santé publique

---

## 🔄 Logique de Routage Automatique

Le système détecte automatiquement le rôle via le token JWT et retourne :

1. **Client** → Dashboard avec ordonnances et réservations personnelles
2. **Pharmacien** → Dashboard avec gestion pharmacie et stocks
3. **Admin** → Dashboard avec statistiques système globales
4. **Autorité** → Dashboard avec surveillance réglementaire

### Avantages :
- ✅ **Une seule route** pour tous les rôles
- ✅ **Sécurité** : Chaque utilisateur ne voit que ses données
- ✅ **Personnalisation** : Interface adaptée aux besoins métier
- ✅ **Évolutif** : Facile d'ajouter de nouveaux rôles

### Utilisation Frontend :
```javascript
// Après connexion, un seul appel suffit
const dashboard = await fetch('/api/dashboard', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// Le frontend s'adapte automatiquement selon le rôle retourné
if (dashboard.role === 'client') {
  // Afficher interface client
} else if (dashboard.role === 'pharmacien') {
  // Afficher interface pharmacien
}
```