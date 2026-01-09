<?php
// Configuración de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

if (!file_exists('config.php')) { die("Error: Falta config.php"); }
require_once 'config.php';

$folioSugerido = sugerirFolio($pdo);
$turnoActual = getTurnoActual();

// AGREGADO: HUATUSCO a la lista de bodegas
$bodegas = ['TUXTLA', 'HUIXTLA', 'YAJALON', 'COMALAPA', 'VILLAFLORES', 'FORTIN', 'ZACAPOAXTLA', 'JALTENANGO', 'HUATUSCO'];
$especiales = ['RUTA CRITICA', 'OFICINAS'];

$todasLasSecciones = [];
foreach($bodegas as $b) $todasLasSecciones[] = str_replace(' ', '', $b);
foreach($especiales as $e) $todasLasSecciones[] = str_replace(' ', '', $e);
$jsonSecciones = json_encode($todasLasSecciones);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporteador CCTV Consolidado | Goratrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .drop-zone {
            width: 100%; height: 120px;
            border: 2px dashed #adb5bd; border-radius: 8px;
            background: #ffffff; display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #6c757d; cursor: pointer; transition: all .2s ease; position: relative; overflow: hidden;
        }
        .drop-zone:hover { background: #f8f9fa; border-color: #0d6efd; }
        .drop-zone.dragover { background: #e7f5ff; border-color: #0d6efd; transform: scale(1.02); z-index: 10; }
        .drop-zone input[type="file"] { display: none; }
        
        /* IMAGEN EN EL CUADRO */
        .drop-zone img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            position: absolute; 
            top: 0; left: 0; 
            z-index: 5; 
            cursor: zoom-in; 
            transition: transform 0.3s;
        }
        .drop-zone img:hover { transform: scale(1.05); }
        
        .drop-text { font-size: 0.75rem; text-align: center; pointer-events: none; }
        .img-spinner { display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 6; }
        .uploaded-success { border: 2px solid #198754 !important; }
        
        .nav-pills .nav-link.active { background-color: #0d6efd; }
        .nav-pills .nav-link { color: #495057; font-weight: 500; }
        
        /* Pantalla de carga global */
        #loader-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85); z-index: 9999; flex-direction: column;
            align-items: center; justify-content: center; color: white; text-align: center;
        }
        .loader-spinner { width: 4rem; height: 4rem; border-width: 0.3em; }
        
        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 10000; }

        /* Estilo para imagen en Modal */
        #modalImagePreview {
            width: 100%;
            height: auto;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 5px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="bg-light">

<!-- PANTALLA DE CARGA -->
<div id="loader-overlay">
    <div class="spinner-border text-primary loader-spinner mb-3" role="status"></div>
    <h3 class="fw-bold">Generando PDFs...</h3>
    <p class="text-light opacity-75">Uniendo reportes y enviando correo.</p>
</div>

<!-- TOAST -->
<div id="toast-container"></div>

<!-- MODAL VISOR DE IMAGEN -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark">
      <div class="modal-header border-0">
        <h5 class="modal-title text-white"><i class="bi bi-eye"></i> Vista Previa de Evidencia</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-0 bg-secondary">
        <img src="" id="modalImagePreview" alt="Vista Previa">
      </div>
      <div class="modal-footer border-0 d-flex justify-content-between">
        <span class="text-white-50 small">Verifique fecha y hora en la imagen.</span>
        <div>
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-warning fw-bold" onclick="reemplazarDesdeModal()">
                <i class="bi bi-arrow-repeat"></i> Reemplazar Imagen
            </button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid py-4" style="margin-bottom: 80px;">
    <!-- Onsubmit llama a la validación -->
    <form action="procesar.php" method="POST" id="reportForm" onsubmit="return confirmarEnvio()">
        
        <!-- HEADER -->
        <div class="card shadow border-0 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center text-md-start">
                        <div class="bg-white p-2 rounded d-inline-block">
                            <img src="http://imgfz.com/i/kzQmwDJ.png" alt="Goratrack" height="40">
                        </div>
                    </div>
                    <div class="col-md-8 text-center">
                        <h4 class="mb-0 text-uppercase fw-bold">Reporte Consolidado CCTV</h4>
                        <small class="opacity-75">Sistema de Gestión de Evidencia Digital</small>
                    </div>
                    <div class="col-md-2 text-end">
                        <span class="badge bg-warning text-dark fs-6">Folio #<?= $folioSugerido ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card-body bg-white">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Monitorista</label>
                        <select name="monitorista" class="form-select form-select-sm" required>
                            <option value="">Seleccione...</option>
                            <option value="1">Monitorista 1</option>
                            <option value="2">Monitorista 2</option>
                            <option value="3">Monitorista 3</option>
                            <option value="4">Monitorista 4</option>
                            <option value="5">Monitorista 5</option>
                            <option value="6">Monitorista 6</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Turno</label>
                        <select name="turno" id="selectTurno" class="form-select form-select-sm" required onchange="cargarBorrador()">
                            <option value="Diurno" <?= $turnoActual == 'Diurno' ? 'selected' : '' ?>>Diurno (07-19h)</option>
                            <option value="Nocturno" <?= $turnoActual == 'Nocturno' ? 'selected' : '' ?>>Nocturno (19-07h)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Fecha</label>
                        <input type="date" name="fecha" id="inputFecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required onchange="cargarBorrador()">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Estado de Sincronización</label>
                        <div class="d-flex align-items-center mt-1">
                            <div class="spinner-grow spinner-grow-sm text-success me-2" role="status" id="liveIndicator"></div>
                            <span class="text-muted small" id="lastSaved">Sistema en línea</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- NAVEGACIÓN -->
            <div class="col-md-2 mb-3">
                <div class="nav flex-column nav-pills me-3 sticky-top" style="top: 20px;" id="v-pills-tab" role="tablist">
                    <div class="small text-muted fw-bold mb-2 ms-2">BODEGAS (PDF 1)</div>
                    <?php foreach ($bodegas as $i => $b): $id = str_replace(' ', '', $b); ?>
                        <button class="nav-link text-start <?= $i===0?'active':'' ?>" id="v-pills-<?= $id ?>-tab" data-bs-toggle="pill" data-bs-target="#v-pills-<?= $id ?>" type="button" role="tab">
                            <i class="bi bi-building me-2"></i> <?= $b ?>
                        </button>
                    <?php endforeach; ?>
                    
                    <div class="small text-muted fw-bold mt-3 mb-2 ms-2">ESPECIALES (PDF 2)</div>
                    <?php foreach ($especiales as $e): $id = str_replace(' ', '', $e); ?>
                        <button class="nav-link text-start" id="v-pills-<?= $id ?>-tab" data-bs-toggle="pill" data-bs-target="#v-pills-<?= $id ?>" type="button" role="tab">
                            <i class="bi bi-star-fill me-2 text-warning"></i> <?= $e ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CONTENIDO PESTAÑAS -->
            <div class="col-md-10">
                <div class="tab-content" id="v-pills-tabContent">
                    
                    <!-- BODEGAS -->
                    <?php foreach ($bodegas as $i => $b): $id = str_replace(' ', '', $b); ?>
                        <div class="tab-pane fade <?= $i===0?'show active':'' ?>" id="v-pills-<?= $id ?>" role="tabpanel">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white py-3">
                                    <h5 class="mb-0 text-primary fw-bold"><?= $b ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold small">Observaciones</label>
                                            <div class="input-group">
                                                <textarea name="obs_<?= $id ?>" id="txt_<?= $id ?>" class="form-control" rows="3" placeholder="Novedades en <?= $b ?>..." onchange="autoGuardar()"></textarea>
                                                <button type="button" class="btn btn-outline-warning btn-ia" id="btn_ia_<?= $id ?>" onclick="mejorarIA('<?= $id ?>')" title="Mejorar con IA">
                                                    <i class="bi bi-magic"></i>
                                                </button>
                                            </div>
                                            <div id="status_<?= $id ?>" class="small mt-1"></div>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <?php for($k=1; $k<=4; $k++): ?>
                                        <div class="col-md-3">
                                            <div class="card h-100 bg-light border-0">
                                                <div class="card-body p-1 text-center">
                                                    <small class="d-block mb-1">Cám <?= $k ?></small>
                                                    <div class="drop-zone" id="dz_<?= $id ?>_<?= $k ?>" onclick="triggerFile('<?= $id ?>', <?= $k ?>)">
                                                        <span class="drop-text"><i class="bi bi-cloud-upload fs-3 d-block"></i>Subir Foto</span>
                                                        <div class="spinner-border text-primary img-spinner" id="spin_<?= $id ?>_<?= $k ?>" role="status"></div>
                                                        <input type="hidden" name="path_<?= $id ?>_<?= $k ?>" id="path_<?= $id ?>_<?= $k ?>" class="path-input">
                                                        <input type="file" id="f_<?= $id ?>_<?= $k ?>" accept="image/*" onchange="uploadFile('<?= $id ?>', <?= $k ?>)">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- ESPECIALES -->
                    <?php foreach ($especiales as $e): $id = str_replace(' ', '', $e); ?>
                        <div class="tab-pane fade" id="v-pills-<?= $id ?>" role="tabpanel">
                            <div class="card shadow-sm border-0 border-top border-warning border-3">
                                <div class="card-header bg-white py-3">
                                    <h5 class="mb-0 text-warning fw-bold"><?= $e ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold small">Reporte de <?= $e ?></label>
                                            <div class="input-group">
                                                <textarea name="obs_<?= $id ?>" id="txt_<?= $id ?>" class="form-control" rows="3" onchange="autoGuardar()"></textarea>
                                                <button type="button" class="btn btn-outline-warning btn-ia" id="btn_ia_<?= $id ?>" onclick="mejorarIA('<?= $id ?>')">
                                                    <i class="bi bi-magic"></i>
                                                </button>
                                            </div>
                                            <div id="status_<?= $id ?>" class="small mt-1"></div>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <?php for($k=1; $k<=4; $k++): ?>
                                        <div class="col-md-3">
                                            <div class="drop-zone" id="dz_<?= $id ?>_<?= $k ?>" onclick="triggerFile('<?= $id ?>', <?= $k ?>)">
                                                <span class="drop-text"><i class="bi bi-cloud-upload fs-3 d-block"></i>Subir Foto</span>
                                                <div class="spinner-border text-primary img-spinner" id="spin_<?= $id ?>_<?= $k ?>" role="status"></div>
                                                <input type="hidden" name="path_<?= $id ?>_<?= $k ?>" id="path_<?= $id ?>_<?= $k ?>" class="path-input">
                                                <input type="file" id="f_<?= $id ?>_<?= $k ?>" accept="image/*" onchange="uploadFile('<?= $id ?>', <?= $k ?>)">
                                            </div>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        </div>

        <div class="fixed-bottom bg-white border-top p-3 shadow-lg">
            <div class="container d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-success fw-bold small"><i class="bi bi-check-circle-fill"></i> Guardado Automático Activo</span>
                </div>
                <button type="submit" class="btn btn-primary px-5 fw-bold btn-lg shadow">
                    <i class="bi bi-send-fill me-2"></i> Generar Reporte Completo
                </button>
            </div>
        </div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const secciones = <?php echo $jsonSecciones; ?>;
    let activePreview = { id: null, k: null };

    // --- FUNCIÓN CANDADO ---
    function confirmarEnvio() {
        let confirmacion = confirm("⚠️ ATENCIÓN ⚠️\n\n¿Estás SEGURO de que deseas finalizar y ENVIAR el reporte?\n\nAl dar clic en 'Aceptar', el sistema cerrará el folio actual y enviará el correo.\n\nSi solo querías subir fotos, presiona 'Cancelar'.");
        
        if (!confirmacion) {
            return false; 
        }
        
        document.getElementById('loader-overlay').style.display = 'flex';
        return true; 
    }

    // --- MANEJO DEL MODAL ---
    function abrirModal(src, id, k) {
        activePreview.id = id;
        activePreview.k = k;
        document.getElementById('modalImagePreview').src = src;
        const myModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        myModal.show();
    }

    function reemplazarDesdeModal() {
        if(activePreview.id && activePreview.k) {
            const modalEl = document.getElementById('imagePreviewModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            setTimeout(() => {
                document.getElementById(`f_${activePreview.id}_${activePreview.k}`).click();
            }, 300);
        }
    }

    function mostrarCargador() {
        // Compatibilidad
        document.getElementById('loader-overlay').style.display = 'flex';
        return true;
    }

    function mostrarToast(mensaje, tipo = 'success') {
        const container = document.getElementById('toast-container');
        const bgClass = tipo === 'success' ? 'bg-success' : 'bg-danger';
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white ${bgClass} border-0 show mb-2`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${mensaje}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // --- GUARDAR BORRADOR (AJAX) ---
    async function guardarBorrador(silencioso = false) {
        const form = document.getElementById('reportForm');
        const formData = new FormData(form);
        const lastSavedEl = document.getElementById('lastSaved');
        const indicator = document.getElementById('liveIndicator');

        indicator.className = "spinner-grow spinner-grow-sm text-warning me-2";
        if(!silencioso) lastSavedEl.innerText = "Guardando...";

        try {
            const response = await fetch('api_borrador.php?accion=guardar', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                const ahora = new Date();
                lastSavedEl.innerText = 'Guardado: ' + ahora.toLocaleTimeString();
                indicator.className = "spinner-grow spinner-grow-sm text-success me-2";
                if(!silencioso) mostrarToast('✅ Guardado en la nube.');
            } else {
                if(!silencioso) mostrarToast('⚠️ No se pudo guardar: ' + (data.error || ''), 'error');
                indicator.className = "spinner-grow spinner-grow-sm text-danger me-2";
            }
        } catch (e) {
            console.error(e);
            if(!silencioso) mostrarToast('❌ Error de conexión.', 'error');
        }
    }

    // --- CARGAR BORRADOR (AJAX) ---
    async function cargarBorrador() {
        const fecha = document.getElementById('inputFecha').value;
        const turno = document.getElementById('selectTurno').value;
        console.log(`Buscando borrador...`);

        try {
            const response = await fetch(`api_borrador.php?accion=cargar&fecha=${fecha}&turno=${turno}`);
            const data = await response.json();
            
            if (data.success && data.datos) {
                const datos = data.datos;
                for (const key in datos) {
                    let el = document.getElementById('txt_' + key.replace('obs_', ''));
                    if (!el) {
                        const els = document.getElementsByName(key);
                        if (els.length > 0) el = els[0];
                    }
                    if (el) el.value = datos[key];

                    if (key.startsWith('path_')) {
                        const pathInput = document.getElementById(key); 
                        if (pathInput && datos[key]) {
                            pathInput.value = datos[key];
                            restaurarImagen(key, datos[key]);
                        }
                    }
                }
                mostrarToast('📂 Datos recuperados.');
            }
        } catch (e) {
            console.error(e);
        }
    }

    function autoGuardar() {
        setTimeout(() => guardarBorrador(true), 1000); 
    }

    function restaurarImagen(inputId, path) {
        const parts = inputId.split('_'); 
        const idSeccion = parts[1];
        const k = parts[2];
        const zoneId = `dz_${idSeccion}_${k}`;
        const zone = document.getElementById(zoneId);
        
        if (zone && path) {
            const oldImg = zone.querySelector('img'); if(oldImg) oldImg.remove();
            
            const img = document.createElement('img');
            img.src = path;
            img.onclick = function(e) {
                e.stopPropagation();
                abrirModal(path, idSeccion, k);
            };
            
            zone.appendChild(img);
            zone.classList.add('uploaded-success');
            const txt = zone.querySelector('.drop-text');
            if(txt) txt.style.display = 'none';
        }
    }

    function triggerFile(id, k) { document.getElementById(`f_${id}_${k}`).click(); }

    async function uploadFile(id, k) {
        const input = document.getElementById(`f_${id}_${k}`);
        const zone = document.getElementById(`dz_${id}_${k}`);
        const spinner = document.getElementById(`spin_${id}_${k}`);
        const hiddenInput = document.getElementById(`path_${id}_${k}`);
        const textSpan = zone.querySelector('.drop-text');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            spinner.style.display = 'block';
            textSpan.style.display = 'none';
            const oldImg = zone.querySelector('img'); if(oldImg) oldImg.remove();

            const formData = new FormData();
            formData.append('file', file);

            try {
                const response = await fetch('upload_ajax.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    hiddenInput.value = data.path;
                    const img = document.createElement('img');
                    img.src = data.path;
                    img.onclick = function(e) {
                        e.stopPropagation();
                        abrirModal(data.path, id, k);
                    };

                    zone.appendChild(img);
                    zone.classList.add('uploaded-success');
                    guardarBorrador(true); 
                } else {
                    alert('Error: ' + data.error);
                    textSpan.style.display = 'block';
                }
            } catch (e) {
                console.error(e);
                alert('Error de conexión.');
                textSpan.style.display = 'block';
            } finally {
                spinner.style.display = 'none';
            }
        }
    }

    function initDragAndDrop() {
        secciones.forEach(id => {
            for (let k = 1; k <= 4; k++) {
                const zone = document.getElementById(`dz_${id}_${k}`);
                const input = document.getElementById(`f_${id}_${k}`);
                if(!zone) continue;
                zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
                zone.addEventListener('dragleave', (e) => { e.preventDefault(); zone.classList.remove('dragover'); });
                zone.addEventListener('drop', (e) => {
                    e.preventDefault(); zone.classList.remove('dragover');
                    if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; uploadFile(id, k); }
                });
            }
        });
    }

    let isWaitingIA = false;
    async function mejorarIA(id) {
        if (isWaitingIA) { alert("⏳ Espera un momento..."); return; }
        const txt = document.getElementById(`txt_${id}`);
        const status = document.getElementById(`status_${id}`);
        const btn = document.getElementById(`btn_ia_${id}`);
        const original = txt.value.trim();

        if (original.length < 5) { alert("Escribe más detalle."); return; }

        const btnIconOriginal = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled = true;
        status.innerHTML = '<span class="text-warning"><i class="bi bi-hourglass-split"></i> Procesando...</span>';
        
        try {
            const response = await fetch('ia_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ texto: original })
            });
            const data = await response.json();
            if (data.texto_mejorado) {
                txt.value = data.texto_mejorado;
                status.innerHTML = '<span class="text-success fw-bold">✓ Mejorado</span>';
                activarPausaGlobal(5);
                guardarBorrador(true); 
            } else { throw new Error(data.error || 'Error'); }
        } catch (e) {
            alert(e.message); status.innerHTML = '<span class="text-danger">Error</span>';
        } finally {
            btn.innerHTML = btnIconOriginal;
            if (!isWaitingIA) btn.disabled = false;
        }
    }

    function activarPausaGlobal(segundos) {
        isWaitingIA = true;
        const allBtns = document.querySelectorAll('.btn-ia');
        allBtns.forEach(b => b.disabled = true);
        setTimeout(() => {
            isWaitingIA = false;
            allBtns.forEach(b => b.disabled = false);
        }, segundos * 1000);
    }

    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop();
        setTimeout(cargarBorrador, 500);
    });
</script>
</body>
</html>