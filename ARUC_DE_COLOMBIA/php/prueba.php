<?php
include "conexion.php"; // Esto importa la conexión

if ($conn) {
    echo "¡Conexión exitosa a la base de datos ARUC!";
} else {
    echo "Error en la conexión.";
}
?>
