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
        var map = L.map('map').setView([14.6928, -17.4467], 12);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        fetch('/api/pharmacies')
            .then(response => response.json())
            .then(pharmacies => {
                pharmacies.forEach(pharmacie => {
                    if (pharmacie.latitude && pharmacie.longitude) {
                        L.marker([pharmacie.latitude, pharmacie.longitude])
                            .addTo(map)
                            .bindPopup(`
                                <b>${pharmacie.nom_pharmacie}</b><br>
                                ${pharmacie.adresse_pharmacie}<br>
                                Tel: ${pharmacie.telephone_pharmacie}
                                ${pharmacie.est_de_garde ? '<br><span style="color: red;">🚨 DE GARDE</span>' : ''}
                            `);
                    }
                });
            });
    </script>
</body>
</html>