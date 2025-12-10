<?php
include 'conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['cedula'])) {
    echo json_encode(['existe' => false]);
    exit;
}

$cedula = trim($_GET['cedula']);

$stmt = $conn->prepare("SELECT id_afiliacion FROM afiliaciones WHERE cedula = ?");
$stmt->bind_param("s", $cedula);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode([
    'existe' => $result->num_rows > 0
]);

$stmt->close();
$conn->close();
?>
