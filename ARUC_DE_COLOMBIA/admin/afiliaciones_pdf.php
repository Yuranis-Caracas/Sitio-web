<?php
include 'includes/auth.php';
include 'includes/database.php';

// Verificar autenticación
if (!isset($_SESSION['admin_name'])) {
    header('Location: login.php');
    exit;
}

$id_afiliacion = $_GET['id'] ?? null;

if (!$id_afiliacion) {
    die('ID de afiliación no proporcionado');
}

// Obtener datos completos de la afiliación
$stmt = $pdo->prepare("SELECT * FROM afiliaciones WHERE id_afiliacion = ?");
$stmt->execute([$id_afiliacion]);
$afiliacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$afiliacion) {
    die('Afiliación no encontrada');
}

// Obtener grupo familiar
$stmt_fam = $pdo->prepare("SELECT * FROM grupo_familiar WHERE id_afiliacion = ?");
$stmt_fam->execute([$id_afiliacion]);
$familiares = $stmt_fam->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividades agrícolas
$stmt_agri = $pdo->prepare("SELECT * FROM actividades_agricolas WHERE id_afiliacion = ?");
$stmt_agri->execute([$id_afiliacion]);
$actividades_agricolas = $stmt_agri->fetchAll(PDO::FETCH_ASSOC);

// Obtener actividades pecuarias
$stmt_pec = $pdo->prepare("SELECT * FROM actividades_pecuarias WHERE id_afiliacion = ?");
$stmt_pec->execute([$id_afiliacion]);
$actividades_pecuarias = $stmt_pec->fetchAll(PDO::FETCH_ASSOC);


// Incluir DOMPDF 
require_once realpath(__DIR__ . '/../dompdf/autoload.inc.php');

use Dompdf\Dompdf;
use Dompdf\Options;

// Configurar opciones de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Función convertir imágenes a base64
function imageToBase64($path) {
    $realPath = realpath($path);
    if ($realPath && file_exists($realPath)) {
        $imageData = base64_encode(file_get_contents($realPath));
        $type = pathinfo($realPath, PATHINFO_EXTENSION);
        return 'data:image/' . $type . ';base64,' . $imageData;
    }
    return '';
}

// Convertir logos a base64
$logo_aruc_base64 = imageToBase64('../img/logo_aruc.jpeg');
$logo_anuc_base64 = imageToBase64('../img/logo_anuc.jpg');


// =============================
//   INICIO DEL DOCUMENTO HTML
// =============================

ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Afiliaciones PDF</title>
    <style>
        @page {
            margin: 50px 25px 80px 25px;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 0;
            margin: 0;
            position: relative;
            min-height: 100%;
        }
        
        .seccion-titulo {
            background: #d0d0d0;
            border: 1px solid #999;
            padding: 8px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0 10px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        th, td {
            border: 1px solid #666;
            padding: 6px;
            text-align: center;
        }
        
        th {
            background: #e8e8e8;
            font-weight: bold;
        }
        
        .pdf-footer {
            position: fixed;
            bottom: -40px; 
            left: 0;
            width: calc(100% - 50px);
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 0px;
            padding-bottom: 10px;
            background-color: white;
            margin: 0;
            padding-left: 25px;
            padding-right: 25px;
        }
        
        .content {
            margin-bottom: 50px; 
        }
    </style>
</head>
<body>

<!-- ENCABEZADO -->
<table width="100%" style="border-collapse: collapse; margin-bottom: 20px;">
    <tr>  
        <!-- LOGOS CENTRADOS UNO ABAJO DEL OTRO -->
        <td width="15%" style="text-align: center; vertical-align: middle; padding-right: 10px;">
            <div style="text-align: center;">
                <img src="<?= $logo_aruc_base64 ?>" style="height: 70px; display: block; margin: 0 auto 5px auto;">
                <img src="<?= $logo_anuc_base64 ?>" style="height: 65px; display: block; margin: 0 auto;">
            </div>
        </td>

        <td width="50%" style="text-align: center; vertical-align: middle;">
            <div style="font-size: 20px; font-weight: bold; color: #115500; line-height: 1.2;">
                FORMULARIO DE INSCRIPCIÓN
            </div>
            <div style="font-size: 15px; margin-top: 4px; font-weight: bold; color: #115500;">
                ASOCIACIÓN NACIONAL DE USUARIOS <br>CAMPESINOS –
                <?= htmlspecialchars($afiliacion["organizacion"] ?? '') ?>
            </div>
        </td>

        <td width="15%" style="text-align: center; vertical-align: middle;">
            <div style="
                width: 105px;
                height: 150px;
                border: 2px dashed #777;
                border-radius: 5px;
                background: #f9f9f9;
                margin: 0 auto;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
            </div>
        </td>
    </tr>
</table>


<div class="seccion-titulo">INFORMACIÓN BÁSICA DEL ASOCIADO</div>

<table>
    <tr>
        <th>Primer Apellido</th>
        <th>Segundo Apellido</th>
        <th>Nombres</th>
        <th>Cédula</th>
        <th>Fecha Expedición</th>
        <th>Lugar Expedición</th>
    </tr>
    <tr>
        <td><?= htmlspecialchars($afiliacion["primer_apellido"]); ?></td>
        <td><?= htmlspecialchars($afiliacion["segundo_apellido"] ?? ''); ?></td>
        <td><?= htmlspecialchars($afiliacion["nombres"]); ?></td>
        <td><?= htmlspecialchars($afiliacion["cedula"]); ?></td>
        <td><?= (!empty($afiliacion["fecha_expedicion"]) ? date("d/m/Y", strtotime($afiliacion["fecha_expedicion"])) : ""); ?></td>
        <td><?= htmlspecialchars($afiliacion["lugar_expedicion"] ?? ''); ?></td>
    </tr>

    <tr>
        <th>Edad</th>
        <th>Ocupación</th>
        <th>Departamento</th>
        <th>Municipio</th>
        <th>Dirección / Vereda</th>
        <th>EPS</th>
    </tr>
    <tr>
        <td><?= htmlspecialchars($afiliacion["edad"] ?? ''); ?></td>
        <td><?= htmlspecialchars($afiliacion["ocupacion"] ?? ''); ?></td>
        <td><?= htmlspecialchars($afiliacion["departamento"] ?? ''); ?></td>
        <td><?= htmlspecialchars($afiliacion["municipio"] ?? ''); ?></td>
        <td><?= htmlspecialchars($afiliacion["direccion"] ?? ''); ?></td>
        <td><?= htmlspecialchars($afiliacion["eps"] ?? ''); ?></td>
    </tr>

    <tr>
        <th>Puntaje SISBEN</th>
        <th colspan="5">Otra Organización</th>
    </tr>
    <tr>
        <td><?= htmlspecialchars($afiliacion["grupo_sisben"] ?? ''); ?></td>
        <td colspan="5"><?= htmlspecialchars($afiliacion["otra_organizacion"] ?? ''); ?></td>
    </tr>
</table>

<div class="seccion-titulo">GRUPO FAMILIAR</div>

<?php if (!empty($familiares)): ?>
<table>
    <tr>
        <th>Primer Apellido</th>
        <th>Segundo Apellido</th>
        <th>Nombres</th>
        <th>Edad</th>
        <th>Documento</th>
        <th>Parentesco</th>
        <th>Escolaridad</th>
        <th>Otra Organización</th>
    </tr>

    <?php foreach ($familiares as $f): ?>
    <tr>
        <td><?= htmlspecialchars($f["grupo_primer_apellido"] ?? '') ?></td>
        <td><?= htmlspecialchars($f["grupo_segundo_apellido"] ?? '') ?></td>
        <td><?= htmlspecialchars($f["grupo_nombres"] ?? '') ?></td>
        <td><?= htmlspecialchars($f["grupo_edad"] ?? '') ?></td>
        <td><?= htmlspecialchars($f["grupo_num_documento"] ?? '') ?></td>
        <td><?= htmlspecialchars($f["grupo_parentesco"] ?? '') ?></td>
        <td><?= htmlspecialchars($f["grupo_escolaridad"] ?? '') ?></td>
        <td><?= htmlspecialchars($f["grupo_cual_org"] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p style="text-align:center; font-style:italic;">No se registraron familiares.</p>
<?php endif; ?>


<div class="seccion-titulo">ACTIVIDADES AGRÍCOLAS</div>

<?php if (!empty($actividades_agricolas)): ?>
<table>
    <tr>
        <th>Producto</th>
        <th>Variedad</th>
        <th>Antigüedad</th>
        <th>Periodo</th>
        <th>Área</th>
        <th>Cantidad</th>
    </tr>

    <?php foreach ($actividades_agricolas as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a["producto_agricola"] ?? '') ?></td>
        <td><?= htmlspecialchars($a["variedad"] ?? '') ?></td>
        <td><?= htmlspecialchars($a["antiguedad"] ?? '') ?></td>
        <td><?= htmlspecialchars($a["periodo_produccion"] ?? '') ?></td>
        <td><?= htmlspecialchars($a["area_cultivo"] ?? '') ?></td>
        <td><?= htmlspecialchars($a["cantidad_produccion"] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p style="text-align:center; font-style:italic;">No se registraron actividades agrícolas.</p>
<?php endif; ?>


<div class="seccion-titulo">ACTIVIDADES PECUARIAS</div>

<?php if (!empty($actividades_pecuarias)): ?>
<table>
    <tr>
        <th>Producto</th>
        <th>Cantidad</th>
    </tr>

    <?php foreach ($actividades_pecuarias as $p): ?>
    <tr>
        <td><?= htmlspecialchars($p["producto_pecuaria"] ?? '') ?></td>
        <td><?= htmlspecialchars($p["cantidad_animales"] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p style="text-align:center; font-style:italic;">No se registraron actividades pecuarias.</p>
<?php endif; ?>

<div class="seccion-titulo">FIRMAS</div>

<p style="font-size:10px;">
    <strong>Fecha de diligenciamiento:</strong> 
    <?= date('d/m/Y', strtotime($afiliacion['fecha_registro'])) ?>
</p>

<p style="font-size:10px;"><strong>Paga inscripción:</strong> Sí ____ No ____</p>

<!-- DOS FIRMAS ARRIBA -->
<table width="100%" style="border:0; margin-top:15px; font-size:10px;">
    <tr style="border:0;">

        <!-- SOLICITANTE -->
        <td width="50%" style="border:0; text-align:left; padding:0; margin:0; vertical-align:top;">
            Firma del solicitante: __________________________<br><br>
            Nombres y apellidos: __________________________<br><br>
            Cédula: ________________
        </td>

        <!-- QUIEN DILIGENCIA -->
        <td width="50%" style="border:0; text-align:left; padding:0; margin:0; vertical-align:top;">

            Firma quien diligencia: ________________________<br><br>
            Nombres y apellidos: __________________________<br><br>
            Cédula: ________________
        </td>

    </tr>
</table>

<!-- UNA FIRMA ABAJO, A LA IZQUIERDA -->
<div style="width:50%; margin-top:25px; text-align:left; font-size:10px;">
    Firma representante legal: __________________________<br><br>
    Nombres y apellidos: __________________________<br><br>
    Cédula: ________________
</div>


<div class="pdf-footer">
    Documento generado automáticamente por el Sistema ARUC DE COLOMBIA
</div>

</body>
</html>

<?php
// FIN DEL HTML
$html = ob_get_clean();

// Enviar a DOMPDF
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Mostrar PDF
$dompdf->stream('Formulario_Afiliacion_ARUC_' . $afiliacion['id_afiliacion'] . '.pdf', array('Attachment' => 0));
?>