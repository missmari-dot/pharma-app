<!DOCTYPE html>
<html>
<head>
    <title>Carte des Pharmacies</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { height: 400px; width: 100%; }
    </style>
</head>
<body>
    <div id="map"></div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialiser la carte avec position par défaut (Dakar)
        var map = L.map('map').setView([14.6928, -17.4467], 12);
        var userMarker = null;
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Géolocalisation de l'utilisateur
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;
                
                // Centrer la carte sur la position de l'utilisateur
                map.setView([userLat, userLng], 14);
                
                // Point A - Position du client
                userMarker = L.marker([userLat, userLng], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map).bindPopup('🅰️ Ma position (Point A)');
                
                // Trouver la pharmacie la plus proche
                trouverPharmacieLaPlusProche(userLat, userLng);
                
            }, function(error) {
                console.log('Erreur de géolocalisation:', error);
                // Garder la position par défaut (Dakar)
            });
        }
        
        function trouverPharmacieLaPlusProche(userLat, userLng) {
            fetch('/api/navigation/pharmacie-proche', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitude: userLat,
                    longitude: userLng
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.pharmacie) {
                    var pharmacie = data.pharmacie;
                    var nav = data.navigation;
                    
                    // Point B - Pharmacie la plus proche
                    var pharmacieMarker = L.marker([parseFloat(pharmacie.latitude), parseFloat(pharmacie.longitude)], {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    }).addTo(map).bindPopup(`
                        🅱️ <b>${pharmacie.nom_pharmacie}</b> (Point B)<br>
                        📍 ${pharmacie.adresse_pharmacie}<br>
                        📞 ${pharmacie.telephone_pharmacie}<br>
                        📏 Distance: ${nav.distance_km} km<br>
                        ⏱️ Temps: ${nav.temps_estime_minutes} min<br>
                        🕐 ${pharmacie.heure_ouverture || 'N/A'} - ${pharmacie.heure_fermeture || 'N/A'}
                        ${pharmacie.est_de_garde ? '<br><span style="color: red; font-weight: bold;">🚨 DE GARDE</span>' : ''}
                    `);
                    
                    // Tracer une ligne entre A et B
                    var latlngs = [
                        [userLat, userLng],
                        [parseFloat(pharmacie.latitude), parseFloat(pharmacie.longitude)]
                    ];
                    
                    var polyline = L.polyline(latlngs, {
                        color: 'blue',
                        weight: 4,
                        opacity: 0.7,
                        dashArray: '10, 10'
                    }).addTo(map);
                    
                    // Ajuster la vue pour voir les deux points
                    var group = new L.featureGroup([userMarker, pharmacieMarker]);
                    map.fitBounds(group.getBounds().pad(0.1));
                    
                    // Afficher les infos de navigation
                    var infoDiv = document.createElement('div');
                    infoDiv.style.cssText = 'position: absolute; top: 10px; right: 10px; background: white; padding: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 1000;';
                    infoDiv.innerHTML = `
                        <h4>🗺️ Navigation</h4>
                        <p><strong>Pharmacie la plus proche:</strong><br>${pharmacie.nom_pharmacie}</p>
                        <p><strong>Distance:</strong> ${nav.distance_km} km</p>
                        <p><strong>Temps estimé:</strong> ${nav.temps_estime_minutes} min</p>
                        <a href="${nav.google_maps_url}" target="_blank" style="color: blue;">📱 Ouvrir dans Google Maps</a>
                    `;
                    document.body.appendChild(infoDiv);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
        
        // Charger toutes les pharmacies (en gris, pour référence)
        fetch('/api/pharmacies/coordonnees')
            .then(response => response.json())
            .then(pharmacies => {
                pharmacies.forEach(pharmacie => {
                    if (pharmacie.latitude && pharmacie.longitude) {
                        L.marker([parseFloat(pharmacie.latitude), parseFloat(pharmacie.longitude)], {
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-grey.png',
                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                iconSize: [20, 32],
                                iconAnchor: [10, 32],
                                popupAnchor: [1, -28],
                                shadowSize: [32, 32]
                            })
                        })
                            .addTo(map)
                            .bindPopup(`
                                <b>💊 ${pharmacie.nom_pharmacie}</b><br>
                                📍 ${pharmacie.adresse_pharmacie}<br>
                                📞 ${pharmacie.telephone_pharmacie}<br>
                                🕐 ${pharmacie.heure_ouverture || 'N/A'} - ${pharmacie.heure_fermeture || 'N/A'}
                                ${pharmacie.est_de_garde ? '<br><span style="color: red; font-weight: bold;">🚨 DE GARDE</span>' : ''}
                            `);
                    }
                });
            });
    </script>
</body>
</html>