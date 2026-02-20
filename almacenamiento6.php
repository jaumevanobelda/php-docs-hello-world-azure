<?php

require 'vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuración
$connectionString = getenv("AZURE_STORAGE_CONNECTION_STRING");
$containerName = "comprimidos";

if (!$connectionString) {
    die("La variable AZURE_STORAGE_CONNECTION_STRING no está configurada.");
}

$blobClient = BlobRestProxy::createBlobService($connectionString);

// Descargar archivo si se solicita (se procesa antes de cualquier salida HTML)
if (isset($_GET['download_blob'])) {
    $blobName = $_GET['download_blob'];
    try {
        $blob = $blobClient->getBlob($containerName, $blobName);
        $content = stream_get_contents($blob->getContentStream());
        $filename = basename($blobName);
        // Sanitizar nombre de archivo para la cabecera
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error al descargar el archivo: " . htmlspecialchars($e->getMessage());
        exit;
    }
}

$messages = [];

// Eliminar archivo si se envió solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_blob'])) {
    $deleteBlobName = $_POST['delete_blob'];
    try {
        $blobClient->deleteBlob($containerName, $deleteBlobName);
        $messages[] = "<p style='color:green;'>Archivo eliminado: " . htmlspecialchars($deleteBlobName) . "</p>";
    } catch (Exception $e) {
        $messages[] = "<p style='color:red;'>Error al eliminar: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Subir archivo nuevo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zipfile'])) {
    $file = $_FILES['zipfile'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file['error'] === UPLOAD_ERR_OK && $extension === 'zip' && mime_content_type($file['tmp_name']) === 'application/zip') {
        $blobName = basename($file['name']);
        try {
            $content = fopen($file['tmp_name'], 'rb');
            $blobClient->createBlockBlob($containerName, $blobName, $content);
            if (is_resource($content)) {
                fclose($content);
            }
            $messages[] = "<p style='color:green;'>Archivo subido: " . htmlspecialchars($blobName) . "</p>";
        } catch (Exception $e) {
            if (isset($content) && is_resource($content)) {
                fclose($content);
            }
            $messages[] = "<p style='color:red;'>Error al subir: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        $messages[] = "<p style='color:red;'>Solo se permiten archivos .zip válidos.</p>";
    }
}

// Listar blobs
try {
    $blobList = $blobClient->listBlobs($containerName, new ListBlobsOptions());
    $blobs = $blobList->getBlobs();
} catch (Exception $e) {
    die("Error al listar blobs: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor de archivos ZIP en Azure Blob</title>
</head>
<body>
    <h1>Archivos ZIP en '<?= htmlspecialchars($containerName) ?>'</h1>

    <?php foreach ($messages as $msg): ?>
        <?= $msg ?>
    <?php endforeach; ?>

    <ul>
    <?php if (empty($blobs)): ?>
        <li>No hay archivos ZIP.</li>
    <?php else: ?>
        <?php foreach ($blobs as $blob): ?>
            <li>
                <a href="?download_blob=<?= urlencode($blob->getName()) ?>" target="_blank">
                    <?= htmlspecialchars($blob->getName()) ?>
                </a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar ' + this.delete_blob.value + '?')">
                    <input type="hidden" name="delete_blob" value="<?= htmlspecialchars($blob->getName(), ENT_QUOTES, 'UTF-8') ?>">
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