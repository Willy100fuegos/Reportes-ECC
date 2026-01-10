<?php
require_once 'config.php';

// Consulta últimos 50
$sql = "SELECT * FROM reportes ORDER BY id DESC LIMIT 50";
$stmt = $pdo->query($sql);
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Reportes CCTV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>📂 Historial Reciente (Últimos 50)</h3>
            <a href="index.php" class="btn btn-primary">➕ Nuevo Reporte</a>
        </div>
        
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Bodega</th>
                            <th>Turno</th>
                            <th>Monitor</th>
                            <th>Fallas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportes as $r): ?>
                        <tr>
                            <td class="fw-bold text-primary">#<?= $r['folio'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($r['fecha_registro'])) ?></td>
                            <td><?= $r['bodega'] ?></td>
                            <td>
                                <span class="badge bg-<?= $r['turno'] == 'Diurno' ? 'warning text-dark' : 'info text-dark' ?>">
                                    <?= $r['turno'] ?>
                                </span>
                            </td>
                            <td>M<?= $r['monitorista'] ?></td>
                            <td class="small text-danger"><?= substr($r['fallas'], 0, 30) ?>...</td>
                            <td>
                                <?php if($r['pdf_path']): ?>
                                    <a href="<?= $r['pdf_path'] ?>" target="_blank" class="btn btn-sm btn-danger">📄 PDF</a>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="verFotos('<?= $r['img1_path'] ?>','<?= $r['img2_path'] ?>')">📷 Fotos</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Simple para fotos -->
    <script>
        function verFotos(img1, img2) {
            // Lógica simple para abrir en pestaña nueva por rapidez
            window.open(img1, '_blank');
        }
    </script>
</body>
</html>