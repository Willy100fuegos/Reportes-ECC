<?php
// Configuración
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 600);

ob_start(); 
require_once 'config.php';

// Detectar librerías
$libDir = __DIR__ . '/libs/';
function buscarArchivo($d, $n) {
    if(!is_dir($d)) return null; $s=scandir($d);
    foreach($s as $f){ if($f=='.'||$f=='..')continue; $p=$d.'/'.$f;
    if(is_file($p)&&$f==$n)return $d.'/'; if(is_dir($p))if($r=buscarArchivo($p,$n))return $r; } return null;
}
$mailerPath = buscarArchivo($libDir, 'PHPMailer.php');
if ($mailerPath) { require_once $mailerPath.'Exception.php'; require_once $mailerPath.'PHPMailer.php'; require_once $mailerPath.'SMTP.php'; }

$fpdfPath = 'libs/fpdf/fpdf.php';
if (!file_exists($fpdfPath)) {
     if (file_exists('libs/fpdf186/fpdf.php')) $fpdfPath = 'libs/fpdf186/fpdf.php';
     elseif (file_exists('libs/FPDF/fpdf.php')) $fpdfPath = 'libs/FPDF/fpdf.php';
}
require_once $fpdfPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] != 'POST') die("Acceso denegado.");

// Datos Globales
$monitorista = $_POST['monitorista'];
$turno = $_POST['turno'];
$fecha = $_POST['fecha'];
$folio = $_POST['folio'];

// --- RED DE SEGURIDAD ---
if (empty($folio) || $folio == 0) {
    try {
        $stmtF = $pdo->query("SELECT ultimo_folio FROM config WHERE id = 1");
        $conf = $stmtF->fetch(PDO::FETCH_ASSOC);
        $folio = ($conf ? (int)$conf['ultimo_folio'] : 0) + 1;
    } catch (Exception $e) {
        $folio = 1;
    }
}

// AGREGADO: HUATUSCO
$bodegas = ['TUXTLA', 'HUIXTLA', 'YAJALON', 'COMALAPA', 'VILLAFLORES', 'FORTIN', 'ZACAPOAXTLA', 'JALTENANGO', 'HUATUSCO'];
$especiales = ['RUTA CRITICA', 'OFICINAS'];

$anio = date('Y'); $mes = date('m');
$uploadDir = "uploads/$anio/$mes/";
if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

// Función Procesar
function procesarImg($name, $dir) {
    if (!isset($_FILES[$name]) || $_FILES[$name]['error'] != 0) return null;
    $tmp = $_FILES[$name]['tmp_name'];
    $check = @getimagesize($tmp); if($check===false) return null;
    $path = $dir . uniqid() . '.jpg';
    list($w, $h, $t) = $check;
    switch($t){ case IMAGETYPE_JPEG: $s=imagecreatefromjpeg($tmp); break; case IMAGETYPE_PNG: $s=imagecreatefrompng($tmp); break; default: return null; }
    if(!$s) return null;
    $maxW = 1000;
    if ($w > $maxW) { $r = $maxW/$w; $nw = $maxW; $nh = $h*$r; } else { $nw=$w; $nh=$h; }
    $n = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($n, $s, 0,0,0,0, $nw, $nh, $w, $h);
    imagejpeg($n, $path, 65);
    imagedestroy($s); imagedestroy($n);
    return $path;
}

ob_end_clean(); 

class PDF extends FPDF {
    function Header() {
        $logos = ['../imagenes/imagenreporte1.png', '../imagenes/imagenreporte2.png', '../imagenes/imagenreporte3.png', '../imagenes/imagenreporte4.jpg'];
        $alturas = [16, 22, 22, 16]; $x = 10; $yBase = 8; $maxHeight = 22;
        foreach ($logos as $i => $path) {
            $hLogo = $alturas[$i];
            if(file_exists($path)) {
                list($w, $h) = getimagesize($path);
                $nw = $hLogo * ($w/$h);
                $yOffset = $yBase + (($maxHeight - $hLogo) / 2);
                $this->Image($path, $x, $yOffset, $nw, $hLogo);
                $x += ($nw + 5);
            } else { $x += 35; }
        }
        $this->Ln(25);
        $this->SetFillColor(30,30,30); $this->SetTextColor(255); $this->SetFont('Arial','B',12);
        $this->Cell(0, 7, 'REPORTE CONSOLIDADO DE MONITOREO CCTV', 0, 1, 'C', true);
        $this->SetTextColor(0); $this->Ln(2);
    }
    function Footer() {
        $this->SetY(-10); $this->SetFont('Arial','I',8); $this->SetTextColor(128);
        $this->Cell(0,10,utf8_decode('Goratrack Tecnologías - Pág ').$this->PageNo().'/{nb}',0,0,'C');
    }
}

// PDF 1: BODEGAS
$pdf1 = new PDF('P','mm','A4'); $pdf1->AliasNbPages();
foreach ($bodegas as $bodega) {
    $pdf1->AddPage();
    $id = str_replace(' ', '', $bodega);
    $obs = $_POST['obs_'.$id] ?? 'Sin observaciones.';
    
    $imgs = []; 
    for($k=1; $k<=4; $k++) {
        $key = "path_{$id}_{$k}";
        if (isset($_POST[$key]) && !empty($_POST[$key]) && file_exists($_POST[$key])) {
            $imgs[$k] = $_POST[$key];
        } else {
            $imgs[$k] = null;
        }
    }
    
    $pdf1->SetFillColor(240); $pdf1->SetFont('Arial','B',10);
    $pdf1->Cell(20,6,'BODEGA:',1,0,'L',true); $pdf1->SetFont('Arial','B',12); $pdf1->Cell(80,6,utf8_decode($bodega),1,0);
    $pdf1->SetFont('Arial','B',10); $pdf1->Cell(20,6,'FOLIO:',1,0,'L',true); $pdf1->Cell(0,6,$folio,1,1,'C');
    $pdf1->Cell(25,6,'FECHA:',1,0,'L',true); $pdf1->SetFont('Arial','',10); $pdf1->Cell(35,6,date('d/m/Y',strtotime($fecha)),1,0);
    $pdf1->SetFont('Arial','B',10); $pdf1->Cell(20,6,'TURNO:',1,0,'L',true); $pdf1->SetFont('Arial','',10); $pdf1->Cell(35,6,$turno,1,0);
    $pdf1->SetFont('Arial','B',10); $pdf1->Cell(25,6,'MONITOR:',1,0,'L',true); $pdf1->SetFont('Arial','',10); $pdf1->Cell(0,6,"M-$monitorista",1,1);
    $pdf1->Ln(3);
    $pdf1->SetFont('Arial','B',10); $pdf1->Cell(0,6,'EVIDENCIA VISUAL','B',1); $pdf1->Ln(2);
    $x=12; $y=$pdf1->GetY(); $w=88; $h=56;
    if(!empty($imgs[1])) $pdf1->Image($imgs[1], $x, $y, $w, $h); if(!empty($imgs[2])) $pdf1->Image($imgs[2], $x+92, $y, $w, $h);
    $y+=58;
    if(!empty($imgs[3])) $pdf1->Image($imgs[3], $x, $y, $w, $h); if(!empty($imgs[4])) $pdf1->Image($imgs[4], $x+92, $y, $w, $h);
    $pdf1->SetY($y+60);
    $pdf1->SetFont('Arial','B',10); $pdf1->Cell(0,6,'OBSERVACIONES:',0,1);
    $pdf1->SetFont('Arial','',9); $pdf1->MultiCell(0,5,utf8_decode($obs),1);
}

$fechaNombreArchivo = date('d-m-Y', strtotime($fecha)); 
$nombrePdf1 = "Bitácora diaria de CCTV - $fechaNombreArchivo.pdf";
$rutaPdf1 = $uploadDir . $nombrePdf1;
$pdf1->Output('F', $rutaPdf1);

// PDF 2: ESPECIALES
$pdf2 = new PDF('P','mm','A4'); $pdf2->AliasNbPages();
foreach ($especiales as $esp) {
    $pdf2->AddPage();
    $id = str_replace(' ', '', $esp);
    $obs = $_POST['obs_'.$id] ?? 'Sin observaciones.';
    
    $imgs = []; 
    for($k=1; $k<=4; $k++) {
        $key = "path_{$id}_{$k}";
        if (isset($_POST[$key]) && !empty($_POST[$key]) && file_exists($_POST[$key])) {
            $imgs[$k] = $_POST[$key];
        } else {
            $imgs[$k] = null;
        }
    }
    
    $pdf2->SetFillColor(255, 240, 200); $pdf2->SetFont('Arial','B',10);
    $pdf2->Cell(20,6,'AREA:',1,0,'L',true); $pdf2->SetFont('Arial','B',12); $pdf2->Cell(80,6,utf8_decode($esp),1,0);
    $pdf2->SetFont('Arial','B',10); $pdf2->Cell(20,6,'FOLIO:',1,0,'L',true); $pdf2->Cell(0,6,$folio,1,1,'C');
    $pdf2->Cell(25,6,'FECHA:',1,0,'L',true); $pdf2->SetFont('Arial','',10); $pdf2->Cell(35,6,date('d/m/Y',strtotime($fecha)),1,0);
    $pdf2->SetFont('Arial','B',10); $pdf2->Cell(20,6,'TURNO:',1,0,'L',true); $pdf2->SetFont('Arial','',10); $pdf2->Cell(35,6,$turno,1,0);
    $pdf2->SetFont('Arial','B',10); $pdf2->Cell(25,6,'MONITOR:',1,0,'L',true); $pdf2->SetFont('Arial','',10); $pdf2->Cell(0,6,"M-$monitorista",1,1);
    $pdf2->Ln(3);
    
    $pdf2->SetFont('Arial','B',10); $pdf2->Cell(0,6,'EVIDENCIA CRITICA','B',1); $pdf2->Ln(2);
    $x=12; $y=$pdf2->GetY(); $w=88; $h=56;
    if(!empty($imgs[1])) $pdf2->Image($imgs[1], $x, $y, $w, $h); if(!empty($imgs[2])) $pdf2->Image($imgs[2], $x+92, $y, $w, $h);
    $y+=58;
    if(!empty($imgs[3])) $pdf2->Image($imgs[3], $x, $y, $w, $h); if(!empty($imgs[4])) $pdf2->Image($imgs[4], $x+92, $y, $w, $h);
    $pdf2->SetY($y+60);
    
    $pdf2->SetFont('Arial','B',10); $pdf2->Cell(0,6,'OBSERVACIONES:',0,1);
    $pdf2->SetFont('Arial','',9); $pdf2->MultiCell(0,5,utf8_decode($obs),1);
}

$nombrePdf2 = "Bitácora diaria de CCTV - RC y Oficinas - $fechaNombreArchivo.pdf";
$rutaPdf2 = $uploadDir . $nombrePdf2;
$pdf2->Output('F', $rutaPdf2);

// Final
try {
    $stmtConfig = $pdo->prepare("UPDATE config SET ultimo_folio = ? WHERE id = 1");
    $stmtConfig->execute([$folio]);
    
    $sql = "INSERT INTO reportes (fecha_registro, monitorista, turno, bodega, folio, observaciones, pdf_path) VALUES (NOW(),?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$monitorista, $turno, 'CONSOLIDADO', $folio, 'Reporte Masivo', $rutaPdf1]);
} catch (PDOException $e) { }

if ($mailerPath) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP(); $mail->Host=SMTP_HOST; $mail->SMTPAuth=true; $mail->Username=SMTP_USER; $mail->Password=SMTP_PASS; $mail->SMTPSecure=SMTP_SECURE; $mail->Port=SMTP_PORT; $mail->CharSet='UTF-8';
        $mail->SMTPOptions = array('ssl'=>array('verify_peer'=>false, 'verify_peer_name'=>false, 'allow_self_signed'=>true));
        $mail->setFrom(SMTP_USER, 'Sistema de Reportes CCTV');
        foreach ($destinatarios as $email) $mail->addAddress($email);
        $mail->isHTML(true);
        
        $mail->Subject = "Reporte de Ruta crítica y Bodegas - (Folio No. $folio)";
        
        $cuerpo = "<b>Reporte Consolidado Generado.</b><br><br><b>Folio:</b> $folio<br><b>Turno:</b> $turno<br><br>Se adjuntan:<br>1. Bitácora de Bodegas (9 Páginas)<br>2. Bitácora RC y Oficinas (2 Páginas)";
        if (isset($alertaLimite) && $alertaLimite) {
            $cuerpo .= "<br><br><span style='color:red; font-weight:bold;'>$alertaLimite</span>";
        }
        $mail->Body = $cuerpo;
        
        $mail->addAttachment($rutaPdf1, $nombrePdf1);
        $mail->addAttachment($rutaPdf2, $nombrePdf2);
        
        $mail->send();
        echo "<script>alert('✅ Reporte CONSOLIDADO #$folio Enviado con éxito (2 PDFs).'); window.location.href='index.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('⚠️ PDFs generados pero falló el correo.'); window.location.href='index.php';</script>";
    }
}
?>