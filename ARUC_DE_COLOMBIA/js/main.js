document.addEventListener('DOMContentLoaded', function() {
    // Solo ejecutar si estamos en la página de afiliación
    if (!document.getElementById('formAfiliacion')) return;
    limitarFechaMaxima();


    // ======================
    // ORGANIZACIÓN
    // ======================
    const organizacion = document.getElementById('organizacion');
    const otraOrgDiv = document.getElementById('otraOrgDiv');

    function limitarFechaMaxima() {
    const hoy = new Date().toISOString().split('T')[0];
    const camposFecha = document.querySelectorAll('input[type="date"]');

    camposFecha.forEach(campo => {
        campo.setAttribute('max', hoy);
    });
    }


    if (organizacion) {
        organizacion.addEventListener('change', () => {
            if (organizacion.value === 'Otra') {
                otraOrgDiv.classList.remove('d-none');
                document.getElementById('otra_organizacion').required = true;
            } else {
                otraOrgDiv.classList.add('d-none');
                document.getElementById('otra_organizacion').required = false;
            }
        });
    }

    // ======================
    // ACTIVIDADES AGRÍCOLAS
    // ======================
    const agricolaSi = document.getElementById('agricola_si');
    const agricolaNo = document.getElementById('agricola_no');
    const agricolaSeccion = document.getElementById('seccion_agricola');

    if (agricolaSi && agricolaNo && agricolaSeccion) {
        agricolaSi.addEventListener('change', () => {
            if (agricolaSi.checked) agricolaSeccion.classList.remove('d-none');
        });
        
        agricolaNo.addEventListener('change', () => {
            if (agricolaNo.checked) agricolaSeccion.classList.add('d-none');
        });
    }

    // ======================
    // ACTIVIDADES PECUARIAS
    // ======================
    const pecuariaSi = document.getElementById('pecuaria_si');
    const pecuariaNo = document.getElementById('pecuaria_no');
    const pecuariaSeccion = document.getElementById('seccion_pecuaria');

    if (pecuariaSi && pecuariaNo && pecuariaSeccion) {
        pecuariaSi.addEventListener('change', () => {
            if (pecuariaSi.checked) pecuariaSeccion.classList.remove('d-none');
        });
        
        pecuariaNo.addEventListener('change', () => {
            if (pecuariaNo.checked) pecuariaSeccion.classList.add('d-none');
        });
    }

    // ======================
    // PREDIO
    // ======================
    const predioSelect = document.getElementById('predioSelect');
    const predioCampos = document.getElementById('predioCampos');

    if (predioSelect && predioCampos) {
        predioSelect.addEventListener('change', () => {
            if (predioSelect.value === 'Si') {
                predioCampos.classList.remove('d-none');
            } else {
                predioCampos.classList.add('d-none');
            }
        });
    }

    // ======================
    // CAMPOS DINÁMICOS
    // ======================

    // Familiares
    const familiaresContainer = document.getElementById('familiaresContainer');
    const btnAgregarFamiliar = document.getElementById('btnAgregarFamiliar');
    let familiaIndex = 0;

    if (btnAgregarFamiliar && familiaresContainer) {
        btnAgregarFamiliar.addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'familiar-row';
            row.innerHTML = `
                <button type="button" class="btn btn-danger btn-sm btn-eliminar-familiar">X</button>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Primer Apellido</label>
                        <input type="text" name="grupo_primer_apellido[]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Segundo Apellido</label>
                        <input type="text" name="grupo_segundo_apellido[]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nombres</label>
                        <input type="text" name="grupo_nombres[]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Edad</label>
                        <input type="number" name="grupo_edad[]" class="form-control" min="0">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label">Tipo de documento</label>
                        <select name="grupo_documento[]" class="form-select">
                            <option value="">Seleccionar</option>
                            <option value="CC">Cédula</option>
                            <option value="TI">Tarjeta Identidad</option>
                            <option value="RC">Registro Civil</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">N° de documento</label>
                        <input type="text" name="grupo_num_documento[]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Parentesco</label>
                        <input type="text" name="grupo_parentesco[]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Escolaridad</label>
                        <input type="text" name="grupo_escolaridad[]" class="form-control">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label d-block">¿Pertenece a otra organización?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="grupo_otra_organizacion[${familiaIndex}]" value="Si" onchange="toggleOrganizacionFamiliar(${familiaIndex}, this.value)">
                            <label class="form-check-label">Sí</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="grupo_otra_organizacion[${familiaIndex}]" value="No" checked onchange="toggleOrganizacionFamiliar(${familiaIndex}, this.value)">
                            <label class="form-check-label">No</label>
                        </div>
                    </div>
                    <div class="col-md-6 d-none" id="org-familiar-${familiaIndex}">
                        <label class="form-label">¿Cuál organización?</label>
                        <input type="text" name="grupo_cual_org[]" class="form-control">
                    </div>
                </div>
            `;
            familiaresContainer.appendChild(row);
            familiaIndex++;
        });

        familiaresContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-eliminar-familiar')) {
                e.target.closest('.familiar-row').remove();
            }
        });
    }

    // Actividades agrícolas
    const agricolasContainer = document.getElementById('agricolasContainer');
    const btnAgregarAgricola = document.getElementById('btnAgregarAgricola');
    let agricolaIndex = 0;

    if (btnAgregarAgricola && agricolasContainer) {
        btnAgregarAgricola.addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'agricola-row';
            row.innerHTML = `
                <button type="button" class="btn btn-danger btn-sm btn-eliminar-agricola">X</button>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Producto</label>
                        <input type="text" name="producto_agricola[]" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Variedad del producto</label>
                        <input type="text" name="variedad[]" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Antigüedad</label>
                        <input type="text" name="antiguedad[]" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Periodo de producción (mes)</label>
                        <input type="text" name="periodo_produccion[]" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Área total del cultivo</label>
                        <input type="text" name="area_cultivo[]" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cantidad de producción</label>
                        <input type="text" name="cantidad_produccion[]" class="form-control">
                    </div>
                </div>
            `;
            agricolasContainer.appendChild(row);
            agricolaIndex++;
        });

        agricolasContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-eliminar-agricola')) {
                e.target.closest('.agricola-row').remove();
            }
        });
    }

    // Actividades pecuarias
    const pecuariasContainer = document.getElementById('pecuariasContainer');
    const btnAgregarPecuaria = document.getElementById('btnAgregarPecuaria');
    let pecuariaIndex = 0;

    if (btnAgregarPecuaria && pecuariasContainer) {
        btnAgregarPecuaria.addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'pecuaria-row';
            row.innerHTML = `
                <button type="button" class="btn btn-danger btn-sm btn-eliminar-pecuaria">X</button>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Producto</label>
                        <input type="text" name="producto_pecuaria[]" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cantidad de animales</label>
                        <input type="text" name="cantidad_animales[]" class="form-control">
                    </div>
                </div>
            `;
            pecuariasContainer.appendChild(row);
            pecuariaIndex++;
        });

        pecuariasContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-eliminar-pecuaria')) {
                e.target.closest('.pecuaria-row').remove();
            }
        });
    }

    // ======================
    // VALIDACIÓN Y ENVÍO
    // ======================
    const formAfiliacion = document.getElementById('formAfiliacion');
    
    if (formAfiliacion) {
        formAfiliacion.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validarFormulario()) {
                alert('Por favor, complete todos los campos obligatorios marcados con *.');
                return;
            }
            
            // Enviar formulario
            const formData = new FormData(this);
            
            fetch('php/guardar_afiliacion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar modal de éxito
                    const modal = new bootstrap.Modal(document.getElementById('modalExito'));
                    modal.show();
                    
                    // Limpiar formulario después de 2 segundos
                    setTimeout(() => {
                        formAfiliacion.reset();
                        familiaresContainer.innerHTML = '';
                        agricolasContainer.innerHTML = '';
                        pecuariasContainer.innerHTML = '';
                        familiaIndex = 0;
                        agricolaIndex = 0;
                        pecuariaIndex = 0;
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al enviar el formulario');
            });
        });
    }

    // Función de validación
    function validarFormulario() {
        const requiredFields = formAfiliacion.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        const checkboxes = formAfiliacion.querySelectorAll('input[type="checkbox"][required]');
        checkboxes.forEach(checkbox => {
            if (!checkbox.checked) {
                isValid = false;
                checkbox.classList.add('is-invalid');
            } else {
                checkbox.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }
});