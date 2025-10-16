<!DOCTYPE html>
<html>
<head>
    <title>Upload Ordonnance</title>
</head>
<body>
    <form action="/api/ordonnances" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="client_id" value="1">
        <input type="file" name="fichier_ordonnance" accept=".pdf,.jpg,.jpeg,.png" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>