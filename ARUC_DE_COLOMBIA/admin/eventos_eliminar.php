<?php
include 'includes/auth.php';
include 'includes/database.php';
redirectIfNotLoggedIn();

$id = $_GET['id'] ?? null;

if ($id) {
    // Obtener el evento para eliminar la imagen
    $stmt = $pdo->prepare("SELECT imagen FROM eventos WHERE id_evento = ?");
    $stmt->execute([$id]);
    $evento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($evento) {
        // Eliminar la imagen si existe
        if ($evento['imagen'] && file_exists('../uploads/eventos/' . $evento['imagen'])) {
            unlink('../uploads/eventos/' . $evento['imagen']);
        }

        // Eliminar el evento
        $stmt = $pdo->prepare("DELETE FROM eventos WHERE id_evento = ?");
        $stmt->execute([$id]);
    }
}

header('Location: eventos.php');
exit;