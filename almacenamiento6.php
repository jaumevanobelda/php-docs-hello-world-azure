<?php

// require 'vendor/autoload.php';

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

// Descargar archivo si se solicita
if (isset($_GET['download_blob'])) {
    $blobName = $_GET['download_blob'];
    try {
        $blob = $blobClient->getBlob($containerName, $blobName);
        $content = stream_get_contents($blob->getContentStream());

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($blobName) . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo "Error al descargar el archivo: " . $e->getMessage();
        exit;
    }
}

// Eliminar archivo si se envió solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_blob'])) {
    try {
        $blobClient->deleteBlob($containerName, $_POST['delete_blob']);
        echo "<p style='color:green;'>Archivo eliminado: {$_POST['delete_blob']}</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error al eliminar: {$e->getMessage()}</p>";
    }
}

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

// Listar blobs
try {
    $blobList = $blobClient->listBlobs($containerName, new ListBlobsOptions());
    $blobs = $blobList->getBlobs();
} catch (Exception $e) {
    die("Error al listar blobs: " . $e->getMessage());
}