<?php
include 'includes/auth.php';
include 'includes/database.php';

try {
    // Obtener todas las afiliaciones
    $stmt = $pdo->query("SELECT * FROM afiliaciones ORDER BY fecha_registro DESC");
    $afiliaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determinar el máximo número de familiares, actividades agrícolas y pecuarias
    $max_familiares = 0;
    $max_agricolas = 0;
    $max_pecuarias = 0;
    
    foreach ($afiliaciones as $afiliacion) {
        // Contar familiares
        $stmtFam = $pdo->prepare("SELECT COUNT(*) as total FROM grupo_familiar WHERE id_afiliacion = ?");
        $stmtFam->execute([$afiliacion['id_afiliacion']]);
        $totalFam = $stmtFam->fetch(PDO::FETCH_ASSOC)['total'];
        $max_familiares = max($max_familiares, $totalFam);
        
        // Contar actividades agrícolas
        $stmtAgri = $pdo->prepare("SELECT COUNT(*) as total FROM actividades_agricolas WHERE id_afiliacion = ?");
        $stmtAgri->execute([$afiliacion['id_afiliacion']]);
        $totalAgri = $stmtAgri->fetch(PDO::FETCH_ASSOC)['total'];
        $max_agricolas = max($max_agricolas, $totalAgri);
        
        // Contar actividades pecuarias
        $stmtPecu = $pdo->prepare("SELECT COUNT(*) as total FROM actividades_pecuarias WHERE id_afiliacion = ?");
        $stmtPecu->execute([$afiliacion['id_afiliacion']]);
        $totalPecu = $stmtPecu->fetch(PDO::FETCH_ASSOC)['total'];
        $max_pecuarias = max($max_pecuarias, $totalPecu);
    }
    
    // Limitar a un máximo razonable para evitar demasiadas columnas
    $max_familiares = min($max_familiares, 10); // Máximo 10 familiares
    $max_agricolas = min($max_agricolas, 10); // Máximo 10 actividades agrícolas
    $max_pecuarias = min($max_pecuarias, 10); // Máximo 10 actividades pecuarias
    
    // Crear encabezados dinámicos
    $headers = array(
        // Información básica
        'ID AFILIACIÓN',
        'FECHA REGISTRO',
        'ESTADO AFILIACIÓN',
        
        // Información personal
        'PRIMER APELLIDO',
        'SEGUNDO APELLIDO',
        'NOMBRES',
        'CÉDULA',
        'FECHA EXPEDICIÓN',
        'LUGAR EXPEDICIÓN',
        'EDAD',
        'OCUPACIÓN',
        
        // Información organizacional
        'ORGANIZACIÓN',
        'OTRA ORGANIZACIÓN',
        
        // Información geográfica
        'DEPARTAMENTO',
        'MUNICIPIO',
        'DIRECCIÓN',
        'EPS',
        'GRUPO SISBÉN',
        'SE IDENTIFICA COMO CAMPESINO',
        
        // Información del predio
        'PREDIO',
        'NOMBRE PREDIO',
        'TITULARIDAD',
        'SANA POSESIÓN CON CERTIFICADO',
        'ÁREA PREDIO',
        'UBICACIÓN PREDIO'
    );
    
    // Agregar encabezados para familiares (solo los que existen)
    for ($i = 1; $i <= $max_familiares; $i++) {
        $headers[] = "FAMILIAR $i - NOMBRE";
        $headers[] = "FAMILIAR $i - EDAD";
        $headers[] = "FAMILIAR $i - DOCUMENTO";
        $headers[] = "FAMILIAR $i - NÚMERO";
        $headers[] = "FAMILIAR $i - PARENTESCO";
        $headers[] = "FAMILIAR $i - ESCOLARIDAD";
        $headers[] = "FAMILIAR $i - OTRA ORGANIZACIÓN";
        $headers[] = "FAMILIAR $i - CUÁL ORGANIZACIÓN";
    }
    
    // Agregar encabezados para actividades agrícolas (solo las que existen)
    for ($i = 1; $i <= $max_agricolas; $i++) {
        $headers[] = "ACTIVIDAD AGRÍCOLA $i - PRODUCTO";
        $headers[] = "ACTIVIDAD AGRÍCOLA $i - VARIEDAD";
        $headers[] = "ACTIVIDAD AGRÍCOLA $i - ANTIGÜEDAD";
        $headers[] = "ACTIVIDAD AGRÍCOLA $i - PERIODO PRODUCCIÓN";
        $headers[] = "ACTIVIDAD AGRÍCOLA $i - ÁREA CULTIVO";
        $headers[] = "ACTIVIDAD AGRÍCOLA $i - CANTIDAD PRODUCCIÓN";
    }
    
    // Agregar encabezados para actividades pecuarias (solo las que existen)
    for ($i = 1; $i <= $max_pecuarias; $i++) {
        $headers[] = "ACTIVIDAD PECUARIA $i - PRODUCTO";
        $headers[] = "ACTIVIDAD PECUARIA $i - CANTIDAD ANIMALES";
    }
    
    // Configurar headers para descarga CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=afiliaciones_' . date('Y-m-d') . '.csv');
    
    // Crear el output
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (ayuda con caracteres especiales en Excel)
    fputs($output, "\xEF\xBB\xBF");
    
    // Escribir encabezados
    fputcsv($output, $headers, ';');
    
    // Datos
    foreach ($afiliaciones as $afiliacion) {
        // Obtener grupo familiar
        $stmtFam = $pdo->prepare("SELECT * FROM grupo_familiar WHERE id_afiliacion = ? ORDER BY id_familiar");
        $stmtFam->execute([$afiliacion['id_afiliacion']]);
        $familiares = $stmtFam->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener actividades agrícolas
        $stmtAgri = $pdo->prepare("SELECT * FROM actividades_agricolas WHERE id_afiliacion = ? ORDER BY id_agricola");
        $stmtAgri->execute([$afiliacion['id_afiliacion']]);
        $agricolas = $stmtAgri->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener actividades pecuarias
        $stmtPecu = $pdo->prepare("SELECT * FROM actividades_pecuarias WHERE id_afiliacion = ? ORDER BY id_pecuaria");
        $stmtPecu->execute([$afiliacion['id_afiliacion']]);
        $pecuarias = $stmtPecu->fetchAll(PDO::FETCH_ASSOC);
        
        // Crear fila base
        $row = array(
            // Información básica
            $afiliacion['id_afiliacion'],
            !empty($afiliacion['fecha_registro']) ? date('Y-m-d', strtotime($afiliacion['fecha_registro'])) : 'N/A',
            $afiliacion['estado_afiliacion'],
            
            // Información personal
            $afiliacion['primer_apellido'] ?: 'N/A',
            $afiliacion['segundo_apellido'] ?: 'N/A',
            $afiliacion['nombres'] ?: 'N/A',
            $afiliacion['cedula'] ?: 'N/A',
            $afiliacion['fecha_expedicion'] ?: 'N/A',
            $afiliacion['lugar_expedicion'] ?: 'N/A',
            $afiliacion['edad'] ?: 'N/A',
            $afiliacion['ocupacion'] ?: 'N/A',
            
            // Información organizacional
            $afiliacion['organizacion'] ?: 'N/A',
            $afiliacion['otra_organizacion'] ?: 'N/A',
            
            // Información geográfica
            $afiliacion['departamento'] ?: 'N/A',
            $afiliacion['municipio'] ?: 'N/A',
            $afiliacion['direccion'] ?: 'N/A',
            $afiliacion['eps'] ?: 'N/A',
            $afiliacion['grupo_sisben'] ?: 'N/A',
            $afiliacion['campesino'] ?: 'N/A',
            
            // Información del predio
            $afiliacion['predio'] ?: 'N/A',
            $afiliacion['nombre_predio'] ?: 'N/A',
            $afiliacion['titularidad'] ?: 'N/A',
            $afiliacion['sana_posesion_cer_tradicion'] ?: 'N/A',
            $afiliacion['area_predio'] ?: 'N/A',
            $afiliacion['ubicacion_predio'] ?: 'N/A'
        );
        
        // Agregar datos del grupo familiar
        $fam_index = 0;
        foreach ($familiares as $familiar) {
            $row[] = trim($familiar['grupo_primer_apellido'] . ' ' . 
                        $familiar['grupo_segundo_apellido'] . ' ' . 
                        $familiar['grupo_nombres']) ?: 'N/A';
            $row[] = $familiar['grupo_edad'] ?: 'N/A';
            $row[] = $familiar['grupo_documento'] ?: 'N/A';
            $row[] = $familiar['grupo_num_documento'] ?: 'N/A';
            $row[] = $familiar['grupo_parentesco'] ?: 'N/A';
            $row[] = $familiar['grupo_escolaridad'] ?: 'N/A';
            $row[] = $familiar['grupo_otra_organizacion'] ?: 'N/A';
            $row[] = $familiar['grupo_cual_org'] ?: 'N/A';
            $fam_index++;
        }
        
        // Completar con 'N/A' para familiares faltantes
        for ($i = $fam_index; $i < $max_familiares; $i++) {
            for ($j = 0; $j < 8; $j++) {
                $row[] = 'N/A';
            }
        }
        
        // Agregar datos de actividades agrícolas
        $agri_index = 0;
        foreach ($agricolas as $agricola) {
            $row[] = $agricola['producto_agricola'] ?: 'N/A';
            $row[] = $agricola['variedad'] ?: 'N/A';
            $row[] = $agricola['antiguedad'] ?: 'N/A';
            $row[] = $agricola['periodo_produccion'] ?: 'N/A';
            $row[] = $agricola['area_cultivo'] ?: 'N/A';
            $row[] = $agricola['cantidad_produccion'] ?: 'N/A';
            $agri_index++;
        }
        
        // Completar con 'N/A' para actividades agrícolas faltantes
        for ($i = $agri_index; $i < $max_agricolas; $i++) {
            for ($j = 0; $j < 6; $j++) {
                $row[] = 'N/A';
            }
        }
        
        // Agregar datos de actividades pecuarias
        $pecu_index = 0;
        foreach ($pecuarias as $pecuaria) {
            $row[] = $pecuaria['producto_pecuaria'] ?: 'N/A';
            $row[] = $pecuaria['cantidad_animales'] ?: 'N/A';
            $pecu_index++;
        }
        
        // Completar con 'N/A' para actividades pecuarias faltantes
        for ($i = $pecu_index; $i < $max_pecuarias; $i++) {
            for ($j = 0; $j < 2; $j++) {
                $row[] = 'N/A';
            }
        }
        
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    
} catch (PDOException $e) {
    // En caso de error, redirigir con mensaje
    header('Location: afiliaciones.php?error=export&message=' . urlencode($e->getMessage()));
    exit();
}
?>