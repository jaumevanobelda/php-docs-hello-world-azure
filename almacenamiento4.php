<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor de archivos ZIP en Azure Blob</title>
</head>
<body>
    <h1>Archivos ZIP en '<?= htmlspecialchars($containerName) ?>'</h1>

    <ul>
    <?php if (empty($blobs)): ?>
        <li>No hay archivos ZIP.</li>
    <?php else: ?>
        <?php foreach ($blobs as $blob): ?>
            <li>
                <a href="?download_blob=<?= urlencode($blob->getName()) ?>" target="_blank">
                    <?= htmlspecialchars($blob->getName()) ?>
                </a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar <?= htmlspecialchars($blob->getName()) ?>?')">
                    <input type="hidden" name="delete_blob" value="<?= htmlspecialchars($blob->getName()) ?>">
                    <button type="submit" style="color:red;">Eliminar</button>
                </form>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
    </ul>

    <h2>Subir nuevo archivo ZIP</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="zipfile" accept=".zip" required>
        <button type="submit">Subir</button>
    </form>
</body>
</html>