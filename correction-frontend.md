# Correction pour l'import Excel

## Problème
L'erreur 405 Method Not Allowed indique que votre frontend fait une requête vers une mauvaise URL.

## Solution
Changez l'URL dans votre composant Angular :

**Avant :**
```typescript
// INCORRECT
this.http.post('http://127.0.0.1:8000/api/importer-stock-excel', formData)
```

**Après :**
```typescript
// CORRECT
this.http.post('http://127.0.0.1:8000/api/pharmacien/importer-stock-excel', formData)
```

## Routes disponibles
- `POST /api/pharmacien/importer-stock` - Import CSV
- `POST /api/pharmacien/importer-stock-excel` - Import Excel/CSV

## Headers requis
```typescript
const headers = new HttpHeaders({
  'Authorization': `Bearer ${token}`
});

this.http.post('http://127.0.0.1:8000/api/pharmacien/importer-stock-excel', formData, { headers })
```