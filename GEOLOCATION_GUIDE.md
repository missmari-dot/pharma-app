# 🌍 Guide de Géolocalisation - Pharma App

## ✅ **Fonctionnalités Implémentées**

### 🎯 **Service de Géolocalisation**
- **OpenStreetMap + Nominatim** (Gratuit)
- **Calcul de distance** (Formule Haversine)
- **Recherche de pharmacies proches**
- **Géocodage d'adresses**

### 📍 **Routes API Disponibles**

#### **Pharmacies Proches**
```bash
GET /api/pharmacies-proches?latitude=14.6928&longitude=-17.4467&radius=5
```
**Réponse :**
```json
[
  {
    "id": 2,
    "nom_pharmacie": "Pharmacie Test",
    "distance": 0,
    "latitude": "14.69280000",
    "longitude": "-17.44670000"
  },
  {
    "id": 1,
    "nom_pharmacie": "Pharmacie Lotty", 
    "distance": 0.3
  }
]
```

#### **Géocodage d'Adresse**
```bash
POST /api/geocode
Content-Type: application/json

{
  "address": "Avenue Léopold Sédar Senghor"
}
```

## 🚀 **Utilisation dans les Frontends**

### **Angular (Web)**
```typescript
// Service de géolocalisation
@Injectable()
export class GeolocationService {
  
  getNearbyPharmacies(lat: number, lng: number, radius = 5) {
    return this.http.get(`/api/pharmacies-proches`, {
      params: { latitude: lat, longitude: lng, radius }
    });
  }
  
  geocodeAddress(address: string) {
    return this.http.post('/api/geocode', { address });
  }
  
  getCurrentPosition(): Promise<{lat: number, lng: number}> {
    return new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(
        position => resolve({
          lat: position.coords.latitude,
          lng: position.coords.longitude
        }),
        error => reject(error)
      );
    });
  }
}
```

### **Flutter (Mobile)**
```dart
// Service de géolocalisation
class GeolocationService {
  static const String baseUrl = 'http://your-api.com/api';
  
  Future<List<Pharmacy>> getNearbyPharmacies(
    double lat, double lng, {double radius = 5}
  ) async {
    final response = await http.get(
      Uri.parse('$baseUrl/pharmacies-proches')
        .replace(queryParameters: {
          'latitude': lat.toString(),
          'longitude': lng.toString(),
          'radius': radius.toString(),
        })
    );
    
    if (response.statusCode == 200) {
      List data = json.decode(response.body);
      return data.map((json) => Pharmacy.fromJson(json)).toList();
    }
    throw Exception('Erreur lors de la récupération');
  }
  
  Future<Position> getCurrentPosition() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      throw Exception('Service de localisation désactivé');
    }
    
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    
    return await Geolocator.getCurrentPosition();
  }
}
```

## 📱 **Intégration Recommandée**

### **Phase 1 : OpenStreetMap (Actuel)**
- ✅ **Gratuit et fonctionnel**
- ✅ **Pas de clé API requise**
- ✅ **Suffisant pour Dakar**

### **Phase 2 : Migration Google Maps (Optionnelle)**
```php
// Service Google Maps (pour plus tard)
class GoogleMapsService {
    private $apiKey;
    
    public function geocodeAddress($address) {
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address . ', Dakar, Senegal',
            'key' => $this->apiKey
        ]);
        
        $data = $response->json();
        
        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $location = $data['results'][0]['geometry']['location'];
            return [
                'latitude' => $location['lat'],
                'longitude' => $location['lng']
            ];
        }
        
        return null;
    }
}
```

## 🎯 **Coordonnées de Référence - Dakar**

```php
// Points d'intérêt à Dakar
$dakarLocations = [
    'Place de l\'Indépendance' => ['lat' => 14.6928, 'lng' => -17.4467],
    'Plateau' => ['lat' => 14.6937, 'lng' => -17.4441],
    'Médina' => ['lat' => 14.6892, 'lng' => -17.4581],
    'Parcelles Assainies' => ['lat' => 14.7644, 'lng' => -17.4138],
    'Guédiawaye' => ['lat' => 14.7697, 'lng' => -17.4081]
];
```

## ✅ **Fonctionnalités Prêtes**

- ✅ **Recherche par proximité** - Rayon configurable
- ✅ **Calcul de distance précis** - Formule Haversine
- ✅ **Tri par distance** - Pharmacies les plus proches en premier
- ✅ **Géocodage d'adresses** - Conversion adresse → coordonnées
- ✅ **API RESTful** - Prête pour Angular et Flutter

Votre système de géolocalisation est **opérationnel** et prêt pour les applications frontend ! 🎉