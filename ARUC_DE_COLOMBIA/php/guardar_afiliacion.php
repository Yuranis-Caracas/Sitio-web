<?php
// Habilitar todos los errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: application/json');

// Incluir la conexión a la base de datos
include 'conexion.php';

// Respuesta JSON
$response = array('success' => false, 'message' => '');

// Definir las rutas específicas de las subcarpetas
$upload_base_dir = '../uploads/';
$fotos_dir = $upload_base_dir . 'fotos/';
$cedulas_dir = $upload_base_dir . 'cedulas/';

try {
    // Verificar si es una solicitud POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar campos obligatorios básicos
    $required_fields = [
        'organizacion', 'primer_apellido', 'nombres', 
        'cedula', 'fecha_expedicion', 'lugar_expedicion', 'edad', 
        'ocupacion', 'departamento', 'municipio', 'direccion', 'eps', 'campesino'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es obligatorio");
        }
    }

    // Validar checkboxes de normativas
    if (!isset($_POST['aceptoEstatutos']) || !isset($_POST['aceptoDatos'])) {
        throw new Exception("Debe aceptar los estatutos y el tratamiento de datos");
    }

    // Crear subcarpetas si no existen
    if (!is_dir($fotos_dir)) {
        mkdir($fotos_dir, 0755, true);
    }
    if (!is_dir($cedulas_dir)) {
        mkdir($cedulas_dir, 0755, true);
    }

    // Manejar archivos
    $foto_nombre = '';
    $cedula_pdf_nombre = '';

    // Procesar foto - GUARDAR EN CARPETA FOTOS
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_nombre = 'foto_' . $_POST['cedula'] . '.' . $foto_extension;
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $fotos_dir . $foto_nombre)) {
            throw new Exception("Error al subir la foto a la carpeta fotos");
        }
    } else {
        throw new Exception("La foto es obligatoria. Error: " . ($_FILES['foto']['error'] ?? 'Desconocido'));
    }

    // Procesar cédula PDF - GUARDAR EN CARPETA CEDULAS
    if (isset($_FILES['copia_cedula']) && $_FILES['copia_cedula']['error'] === UPLOAD_ERR_OK) {
        $cedula_extension = pathinfo($_FILES['copia_cedula']['name'], PATHINFO_EXTENSION);
        $cedula_pdf_nombre = 'cedula_' . $_POST['cedula'] . '.' . $cedula_extension;
        if (!move_uploaded_file($_FILES['copia_cedula']['tmp_name'], $cedulas_dir . $cedula_pdf_nombre)) {
            throw new Exception("Error al subir la copia de cédula a la carpeta cedulas");
        }
    } else {
        throw new Exception("La copia de cédula es obligatoria. Error: " . ($_FILES['copia_cedula']['error'] ?? 'Desconocido'));
    }

    // Iniciar transacción
    $conn->begin_transaction();

    // Obtener valores del predio
    $tiene_predio = $_POST['predio'] ?? 'Si';
    $nombre_predio = $tiene_predio === 'Si' ? ($_POST['nombre_predio'] ?? '') : '';
    $titularidad = $tiene_predio === 'Si' ? ($_POST['titularidad'] ?? '') : '';
    $sana_posesion = $tiene_predio === 'Si' ? ($_POST['sana_posesion_cer_tradicion'] ?? '') : '';
    $area_predio = $tiene_predio === 'Si' ? ($_POST['area_predio'] ?? '') : '';
    $ubicacion_predio = $tiene_predio === 'Si' ? ($_POST['ubicacion_predio'] ?? '') : '';

    // Crear variables para bind_param
    $organizacion = $_POST['organizacion'];
    $otra_organizacion = $_POST['otra_organizacion'] ?? '';
    $primer_apellido = $_POST['primer_apellido'];
    $segundo_apellido = $_POST['segundo_apellido'];
    $nombres = $_POST['nombres'];
    $cedula = $_POST['cedula'];
    $fecha_expedicion = $_POST['fecha_expedicion'];
    $lugar_expedicion = $_POST['lugar_expedicion'];
    $edad = $_POST['edad'];
    $ocupacion = $_POST['ocupacion'];
    $departamento = $_POST['departamento'];
    $municipio = $_POST['municipio'];
    $direccion = $_POST['direccion'];
    $eps = $_POST['eps'];
    $grupo_sisben = $_POST['grupo_sisben'] ?? '';
    $campesino = $_POST['campesino'];

    // Insertar datos principales en la tabla afiliaciones
    $query = "INSERT INTO afiliaciones (
        organizacion, otra_organizacion, primer_apellido, segundo_apellido, nombres,
        cedula, fecha_expedicion, lugar_expedicion, edad, ocupacion, departamento,
        municipio, direccion, eps, grupo_sisben, campesino, predio, nombre_predio,
        titularidad, sana_posesion_cer_tradicion, area_predio, ubicacion_predio,
        foto, copia_cedula
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }

    // Usar variables en lugar de referencias directas
    $stmt->bind_param(
        "ssssssssisssssssssssssss",
        $organizacion,
        $otra_organizacion,
        $primer_apellido,
        $segundo_apellido,
        $nombres,
        $cedula,
        $fecha_expedicion,
        $lugar_expedicion,
        $edad,
        $ocupacion,
        $departamento,
        $municipio,
        $direccion,
        $eps,
        $grupo_sisben,
        $campesino,
        $tiene_predio,
        $nombre_predio,
        $titularidad,
        $sana_posesion,
        $area_predio,
        $ubicacion_predio,
        $foto_nombre,
        $cedula_pdf_nombre
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar afiliación: " . $stmt->error);
    }

    $afiliado_id = $stmt->insert_id;
    $stmt->close();

    // Insertar familiares
    if (isset($_POST['grupo_nombres']) && is_array($_POST['grupo_nombres'])) {
        $fam_query = "INSERT INTO grupo_familiar (
            id_afiliacion, grupo_primer_apellido, grupo_segundo_apellido, grupo_nombres, 
            grupo_edad, grupo_documento, grupo_num_documento, grupo_parentesco, 
            grupo_escolaridad, grupo_otra_organizacion, grupo_cual_org
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $fam_stmt = $conn->prepare($fam_query);
        
        for ($i = 0; $i < count($_POST['grupo_nombres']); $i++) {
            if (!empty($_POST['grupo_nombres'][$i])) {
                // Crear variables para cada familiar
                $grupo_otra_organizacion = $_POST["grupo_otra_organizacion"][$i] ?? 'No';
                $grupo_cual_org = $_POST['grupo_cual_org'][$i] ?? '';
                $grupo_primer_apellido = $_POST['grupo_primer_apellido'][$i] ?? '';
                $grupo_segundo_apellido = $_POST['grupo_segundo_apellido'][$i] ?? '';
                $grupo_nombres_val = $_POST['grupo_nombres'][$i];
                $grupo_edad = $_POST['grupo_edad'][$i] ?? 0;
                $grupo_documento = $_POST['grupo_documento'][$i] ?? '';
                $grupo_num_documento = $_POST['grupo_num_documento'][$i] ?? '';
                $grupo_parentesco = $_POST['grupo_parentesco'][$i] ?? '';
                $grupo_escolaridad = $_POST['grupo_escolaridad'][$i] ?? '';
                
                $fam_stmt->bind_param(
                    "isssissssss",
                    $afiliado_id,
                    $grupo_primer_apellido,
                    $grupo_segundo_apellido,
                    $grupo_nombres_val,
                    $grupo_edad,
                    $grupo_documento,
                    $grupo_num_documento,
                    $grupo_parentesco,
                    $grupo_escolaridad,
                    $grupo_otra_organizacion,
                    $grupo_cual_org
                );
                
                if (!$fam_stmt->execute()) {
                    throw new Exception("Error al insertar familiar: " . $fam_stmt->error);
                }
            }
        }
        $fam_stmt->close();
    }

    // Insertar actividades agrícolas
    if (isset($_POST['producto_agricola']) && is_array($_POST['producto_agricola'])) {
        $agr_query = "INSERT INTO actividades_agricolas (
            id_afiliacion, producto_agricola, variedad, antiguedad, 
            periodo_produccion, area_cultivo, cantidad_produccion
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $agr_stmt = $conn->prepare($agr_query);
        
        for ($i = 0; $i < count($_POST['producto_agricola']); $i++) {
            if (!empty($_POST['producto_agricola'][$i])) {
                // Crear variables para cada actividad agrícola
                $producto_agricola_val = $_POST['producto_agricola'][$i];
                $variedad = $_POST['variedad'][$i] ?? '';
                $antiguedad = $_POST['antiguedad'][$i] ?? '';
                $periodo_produccion = $_POST['periodo_produccion'][$i] ?? '';
                $area_cultivo = $_POST['area_cultivo'][$i] ?? '';
                $cantidad_produccion = $_POST['cantidad_produccion'][$i] ?? '';
                
                $agr_stmt->bind_param(
                    "issssss",
                    $afiliado_id,
                    $producto_agricola_val,
                    $variedad,
                    $antiguedad,
                    $periodo_produccion,
                    $area_cultivo,
                    $cantidad_produccion
                );
                
                if (!$agr_stmt->execute()) {
                    throw new Exception("Error al insertar actividad agrícola: " . $agr_stmt->error);
                }
            }
        }
        $agr_stmt->close();
    }

    // Insertar actividades pecuarias
    if (isset($_POST['producto_pecuaria']) && is_array($_POST['producto_pecuaria'])) {
        $pec_query = "INSERT INTO actividades_pecuarias (
            id_afiliacion, producto_pecuaria, cantidad_animales
        ) VALUES (?, ?, ?)";
        
        $pec_stmt = $conn->prepare($pec_query);
        
        for ($i = 0; $i < count($_POST['producto_pecuaria']); $i++) {
            if (!empty($_POST['producto_pecuaria'][$i])) {
                // Crear variables para cada actividad pecuaria
                $producto_pecuaria_val = $_POST['producto_pecuaria'][$i];
                $cantidad_animales = $_POST['cantidad_animales'][$i] ?? '';
                
                $pec_stmt->bind_param(
                    "iss",
                    $afiliado_id,
                    $producto_pecuaria_val,
                    $cantidad_animales
                );
                
                if (!$pec_stmt->execute()) {
                    throw new Exception("Error al insertar actividad pecuaria: " . $pec_stmt->error);
                }
            }
        }
        $pec_stmt->close();
    }

    // Confirmar transacción
    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Afiliación registrada exitosamente';

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($conn)) {
        $conn->rollback();
    }
    $response['message'] = 'Error: ' . $e->getMessage();
    
    // Limpiar archivos subidos en caso de error (usando las rutas correctas)
    if (!empty($foto_nombre) && file_exists($fotos_dir . $foto_nombre)) {
        unlink($fotos_dir . $foto_nombre);
    }
    if (!empty($cedula_pdf_nombre) && file_exists($cedulas_dir . $cedula_pdf_nombre)) {
        unlink($cedulas_dir . $cedula_pdf_nombre);
    }
}

// Cerrar conexión
if (isset($conn)) {
    $conn->close();
}

echo json_encode($response);
?>