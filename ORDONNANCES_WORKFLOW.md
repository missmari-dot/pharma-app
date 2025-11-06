# Workflow des Ordonnances - PharmaMobile

## Vue d'ensemble

Le système gère maintenant deux types d'ordonnances :
1. **Ordonnances avec médicaments pré-sélectionnés** (workflow existant)
2. **Ordonnances sans médicaments** (nouveau workflow)

## Nouveau Workflow : Ordonnances sans Médicaments

### 1. Envoi par le Client

**Endpoint :** `POST /api/ordonnances/envoyer-sans-medicaments`

**Paramètres :**
```json
{
  "pharmacie_id": 1,
  "photo_ordonnance": "base64_image_or_file",
  "commentaire": "Ordonnance du Dr. Diallo pour traitement hypertension"
}
```

**Réponse :**
```json
{
  "message": "Ordonnance envoyée avec succès. Le pharmacien va analyser et sélectionner les médicaments.",
  "ordonnance": {
    "id": 15,
    "client_id": 2,
    "pharmacie_id": 1,
    "photo_url": "ordonnances/ordonnance_1640995200.jpg",
    "statut": "en_attente",
    "date_envoi": "2024-01-01",
    "commentaire": "Ordonnance du Dr. Diallo pour traitement hypertension"
  }
}
```

### 2. Traitement par le Pharmacien

**Endpoint :** `PATCH /api/pharmacien/ordonnances/{ordonnance}/traiter`

**Paramètres :**
```json
{
  "medicaments": [
    {
      "produit_id": 5,
      "quantite_prescrite": 2,
      "dosage": "10mg",
      "instructions": "1 comprimé matin et soir"
    },
    {
      "produit_id": 12,
      "quantite_prescrite": 1,
      "dosage": "5mg",
      "instructions": "1 comprimé le soir"
    }
  ],
  "commentaire": "Ordonnance validée. Médicaments disponibles en stock."
}
```

**Réponse :**
```json
{
  "message": "Ordonnance traitée avec succès",
  "ordonnance": {
    "id": 15,
    "statut": "validee",
    "lignesOrdonnance": [
      {
        "produit_id": 5,
        "quantite_prescrite": 2,
        "dosage": "10mg",
        "instructions": "1 comprimé matin et soir",
        "produit": {
          "nom_produit": "Amlodipine 10mg",
          "prix": 2500
        }
      }
    ]
  },
  "reservation": {
    "id": 25,
    "code_retrait": "RET-A1B2C3",
    "montant_total": 7500,
    "statut": "en_attente"
  },
  "code_retrait": "RET-A1B2C3"
}
```

### 3. Réservation sans Médicaments Pré-sélectionnés

**Endpoint :** `POST /api/reservations`

**Paramètres :**
```json
{
  "pharmacie_id": 1,
  "ordonnance_image": "file_or_base64"
}
```

**Réponse :**
```json
{
  "message": "Réservation créée. Le pharmacien va analyser votre ordonnance et sélectionner les médicaments.",
  "reservation": {
    "id": 26,
    "statut": "en_attente_validation",
    "code_retrait": "RET-D4E5F6",
    "ordonnance": {
      "id": 16,
      "statut": "en_attente"
    }
  }
}
```

## Statuts des Ordonnances

- **`en_attente`** : Ordonnance envoyée, en attente de traitement par le pharmacien
- **`validee`** : Ordonnance traitée et médicaments sélectionnés par le pharmacien
- **`rejetee`** : Ordonnance rejetée par le pharmacien

## Statuts des Réservations

- **`en_attente_validation`** : Réservation créée sans médicaments, en attente de traitement de l'ordonnance
- **`en_attente`** : Réservation prête, en attente de retrait par le client
- **`confirmee`** : Médicaments retirés par le client

## Avantages du Nouveau Workflow

1. **Simplicité pour le client** : Plus besoin de connaître les médicaments exacts
2. **Expertise du pharmacien** : Le pharmacien analyse l'ordonnance et sélectionne les médicaments appropriés
3. **Vérification du stock** : Le pharmacien peut proposer des alternatives si certains médicaments ne sont pas disponibles
4. **Conformité réglementaire** : Respect des prescriptions médicales par un professionnel de santé

## Endpoints Existants (Inchangés)

- `POST /api/ordonnances` : Envoi d'ordonnance classique
- `POST /api/reservations` : Réservation avec médicaments pré-sélectionnés
- `PATCH /api/pharmacien/ordonnances/{ordonnance}/valider` : Validation d'ordonnance classique
- `PATCH /api/pharmacien/ordonnances/{ordonnance}/rejeter` : Rejet d'ordonnance

## Migration des Données

Aucune migration nécessaire. Les nouvelles fonctionnalités utilisent les tables existantes :
- `ordonnances`
- `ligne_ordonnances`
- `reservations`
- `ligne_reservations`