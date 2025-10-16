<!DOCTYPE html>
<html>
<head>
    <title>Upload Produit</title>
</head>
<body>
    <form action="/api/produits" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="text" name="nom_produit" placeholder="Nom produit" required>
        <input type="number" name="prix" placeholder="Prix" step="0.01" required>
        <input type="text" name="categorie" placeholder="Catégorie" required>
        <input type="number" name="stock" placeholder="Stock" required>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
        <button type="submit">Créer</button>
    </form>
</body>
</html>