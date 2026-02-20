<h1> ayuda</h1>

<?php
echo"<h1> ayuda2</h1>"
require 'vendor/autoload.php';
echo"<h1> ayuda3</h1>"
// use MicrosoftAzure\Storage\Blob\BlobRestProxy;
// use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;

ini_set('display_errors', 1);
error_reporting(E_ALL);
echo"<h1> ayuda4</h1>"
// Configuración
$connectionString = getenv("AZURE_STORAGE_CONNECTION_STRING");
$containerName = "comprimidos";
echo"<h1> ayuda6</h1>"
if (!$connectionString) {
    die("La variable AZURE_STORAGE_CONNECTION_STRING no está configurada.");
}
echo"<h1> ayuda7</h1>"
$blobClient = BlobRestProxy::createBlobService($connectionString);
echo"<h1> ayuda8</h1>"
// Descargar archivo si se solicita
if (isset($_GET['download_blob'])) {
    $blobName = $_GET['download_blob'];
    echo"<h1> ayuda9</h1>"
    try {
        $blob = $blobClient->getBlob($containerName, $blobName);
        echo"<h1> ayuda10</h1>"
        $content = stream_get_contents($blob->getContentStream());
        echo"<h1> ayuda11</h1>"
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($blobName) . '"');
        header('Content-Length: ' . strlen($content));
        echo"<h1> ayuda12 antes de echo content</h1>"
        echo $content;
        exit;
    } catch (Exception $e) {
        echo"<h1> ayuda MAL 1</h1>"
        http_response_code(500);
        echo "Error al descargar el archivo: " . $e->getMessage();
        exit;
    }
}
echo"<h1> ayuda13</h1>"
// Eliminar archivo si se envió solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_blob'])) {
    try {
        $blobClient->deleteBlob($containerName, $_POST['delete_blob']);
        echo"<h1> ayuda14</h1>"
        echo "<p style='color:green;'>Archivo eliminado: {$_POST['delete_blob']}</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error al eliminar: {$e->getMessage()}</p>";
    }
}
echo"<h1> ayuda15</h1>"
// Subir archivo nuevo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zipfile'])) {
    $file = $_FILES['zipfile'];
    if ($file['error'] === UPLOAD_ERR_OK && mime_content_type($file['tmp_name']) === 'application/zip') {
        $blobName = basename($file['name']);
        try {
            $content = fopen($file['tmp_name'], 'r');
            $blobClient->createBlockBlob($containerName, $blobName, $content);
            echo "<p style='color:green;'>Archivo subido: {$blobName}</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>Error al subir: {$e->getMessage()}</p>";
        }
    } else {
        echo "<p style='color:red;'>Solo se permiten archivos .zip válidos.</p>";
    }
}
echo"<h1> ayuda16</h1>"
// Listar blobs
try {
    $blobList = $blobClient->listBlobs($containerName, new ListBlobsOptions());
    $blobs = $blobList->getBlobs();
} catch (Exception $e) {
    die("Error al listar blobs: " . $e->getMessage());
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