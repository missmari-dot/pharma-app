# Carte Interactive OpenStreetMap - PharmaMobile

## API Endpoints

### 1. Pharmacies Proches
**Endpoint :** `POST /api/map/pharmacies-proches`

**Paramètres :**
```json
{
  "latitude": 14.6928,
  "longitude": -17.4467,
  "rayon": 5,
  "de_garde": true,
  "produit_id": 15
}
```

**Réponse :**
```json
[
  {
    "id": 1,
    "nom_pharmacie": "Pharmacie Centrale",
    "adresse_pharmacie": "Avenue Bourguiba, Dakar",
    "telephone_pharmacie": "+221 33 123 45 67",
    "latitude": 14.6937,
    "longitude": -17.4441,
    "est_de_garde": true,
    "heure_ouverture": "08:00",
    "heure_fermeture": "20:00",
    "distance_km": 0.8,
    "statut": "garde"
  }
]
```

### 2. Itinéraire
**Endpoint :** `POST /api/map/itineraire`

**Paramètres :**
```json
{
  "depart_lat": 14.6928,
  "depart_lng": -17.4467,
  "arrivee_lat": 14.6937,
  "arrivee_lng": -17.4441
}
```

**Réponse :**
```json
{
  "type": "LineString",
  "coordinates": [
    [-17.4467, 14.6928],
    [-17.4441, 14.6937]
  ],
  "distance": 850,
  "duration": 180,
  "fallback": false
}
```

## Fonctionnalités de la Carte

### ✅ Position du Client
- Marqueur bleu pour la position actuelle
- Géolocalisation automatique
- Centrage de la carte sur la position

### ✅ Pharmacies Proches
- **Marqueurs verts** : Pharmacies normales
- **Marqueurs rouges** : Pharmacies de garde
- Rayon de recherche configurable (1-50km)

### ✅ Filtres Disponibles
- **De garde** : `de_garde: true`
- **Médicament disponible** : `produit_id: 15`
- **Rayon** : `rayon: 5` (en km)

### ✅ Info-bulles Cliquables
Chaque marqueur affiche :
- Nom de la pharmacie
- Adresse complète
- Téléphone
- Horaires d'ouverture
- Distance en km
- Statut (garde/normale)

### ✅ Itinéraire
- Calcul d'itinéraire avec OpenRouteService
- Affichage du tracé sur la carte
- Distance et durée estimée
- Fallback en cas d'erreur API

## Configuration Requise

### Variables d'environnement
```env
OPENROUTE_API_KEY=your_openrouteservice_api_key
```

### Obtenir une clé API OpenRouteService
1. Aller sur https://openrouteservice.org/
2. Créer un compte gratuit
3. Générer une clé API
4. Ajouter la clé dans `.env`

## Utilisation Côté Mobile

### 1. Initialiser la carte
```dart
// Centrer sur la position du client
final userPosition = LatLng(14.6928, -17.4467);
```

### 2. Charger les pharmacies
```dart
final response = await http.post(
  Uri.parse('$baseUrl/api/map/pharmacies-proches'),
  body: {
    'latitude': userPosition.latitude.toString(),
    'longitude': userPosition.longitude.toString(),
    'rayon': '5',
    'de_garde': 'true'
  }
);
```

### 3. Afficher les marqueurs
```dart
// Marqueur client (bleu)
Marker(
  markerId: MarkerId('user'),
  position: userPosition,
  icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue)
)

// Marqueurs pharmacies
for (var pharmacie in pharmacies) {
  Marker(
    markerId: MarkerId('pharmacie_${pharmacie.id}'),
    position: LatLng(pharmacie.latitude, pharmacie.longitude),
    icon: BitmapDescriptor.defaultMarkerWithHue(
      pharmacie.est_de_garde ? BitmapDescriptor.hueRed : BitmapDescriptor.hueGreen
    ),
    infoWindow: InfoWindow(
      title: pharmacie.nom_pharmacie,
      snippet: '${pharmacie.distance_km}km - ${pharmacie.telephone_pharmacie}'
    )
  )
}
```

### 4. Calculer un itinéraire
```dart
final response = await http.post(
  Uri.parse('$baseUrl/api/map/itineraire'),
  body: {
    'depart_lat': userPosition.latitude.toString(),
    'depart_lng': userPosition.longitude.toString(),
    'arrivee_lat': pharmacie.latitude.toString(),
    'arrivee_lng': pharmacie.longitude.toString()
  }
);

// Dessiner la polyline sur la carte
final coordinates = response.data['coordinates'];
final polyline = Polyline(
  polylineId: PolylineId('route'),
  points: coordinates.map((coord) => LatLng(coord[1], coord[0])).toList(),
  color: Colors.blue,
  width: 3
);
```

## Avantages

- **Gratuit** : OpenStreetMap et OpenRouteService
- **Performant** : Recherche par rayon avec calcul de distance
- **Flexible** : Filtres multiples (garde, produit, distance)
- **Complet** : Position, pharmacies, itinéraires, info-bulles
- **Fallback** : Ligne droite si API indisponible