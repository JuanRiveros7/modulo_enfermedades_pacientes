<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carga de Pacientes y Enfermedades</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light">

<navbar class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand"> <i class="bi bi-hospital"> </i> Eps Famisanar</a> 
    <a href="index_funcionario.php" class="btn btn-success">Regresar</a>
  </div>
</navbar>

<div class="container py-4">
  <div class="card shadow">
    <div class="card-header bg-dark text-white">
      <h4 class="mb-0"><i class="bi bi-file-earmark-spreadsheet"></i> Subir archivo plano</h4>
    </div>
    <div class="card-body">

      <form id="uploadForm" action="validacion_file.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="file-input" class="form-label">Seleccionar archivo (CSV/TXT)</label>
          <input class="form-control" type="file" name="dataCliente" id="file-input" accept=".csv,.txt" required>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-upload"></i> Subir y procesar
          </button>
        </div>
      </form>

      <!-- Barra de progreso de subida de archivos -->
      <div class="progress mt-4 d-none" id="progressContainer">
        <div id="progressBar" 
             class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
             role="progressbar" style="width: 0%">0%</div>
      </div>

      <!-- Mensaje de estado -->
      <div id="uploadMessage" class="alert mt-3 d-none"></div>

      <!-- Tabla de resultados (subida CSV) -->
      <div class="table-responsive mt-4">
        <table class="table table-striped table-hover" id="dataTable">
          <thead class="table-dark">
            <tr>
              <th>Documento</th>
              <th>Nombre</th>
              <th>Apellido</th>
              <th>Fecha Nacimiento</th>
              <th>Sexo</th>
              <th>Teléfono</th>
              <th>Dirección</th>
              <th>Enfermedad</th>
              <th>Fecha Diagnóstico</th>
              <th>Estado</th>
              <th>Observaciones</th>
            </tr>
          </thead>

          <tbody>
            <!-- Se llena dinámicamente -->
          </tbody>

        </table>
      </div>
    </div>
  </div>

  <div class="card shadow mt-5">
    <div class="card-header bg-dark text-white">
      <h4 class="mb-0"><i class="bi bi-search"></i> Consultar Pacientes</h4>
    </div>
    <div class="card-body">
      <section class="mb-3">
        <label for="caja-busqueda" class="form-label">Buscar paciente</label>
        <input type="text" class="form-control" id="caja-busqueda" placeholder="Escribe nombre, documento, enfermedad...">
        <button class="btn btn-success btn-sm mt-2" onclick="buscar_datos();">Buscar</button>
        <button class="btn btn-secondary btn-sm mt-2" onclick="$('#caja-busqueda').val(''); buscar_datos();">Limpiar búsqueda</button>
      </section>

      <section id="datos" class="mt-3">
        <!-- Resultados AJAX -->
      </section>
    </div>
  </div>

</div>

<script>
/* ===  Script de Subida de archivo con barra de progreso === */
document.getElementById("uploadForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    const progressContainer = document.getElementById("progressContainer");
    const progressBar = document.getElementById("progressBar");
    const uploadMessage = document.getElementById("uploadMessage");

    progressContainer.classList.remove("d-none");
    progressBar.style.width = "0%";
    progressBar.textContent = "0%";
    progressBar.classList.add("progress-bar-animated");
    uploadMessage.classList.add("d-none");

    const xhr = new XMLHttpRequest();
    xhr.open("POST", form.action, true);

    xhr.upload.addEventListener("progress", function(e) {
        if (e.lengthComputable) {
            let percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + "%";
            progressBar.textContent = percent + "%";
        }
    });

    xhr.onload = function() {
        progressBar.classList.remove("progress-bar-animated");

        if (xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);

                if (data.status === "success") {
                    progressBar.style.width = "100%";
                    progressBar.textContent = "100%";

                    uploadMessage.className = "alert alert-success mt-3";
                    uploadMessage.textContent = data.message;
                    uploadMessage.classList.remove("d-none");

                    const tableBody = document.querySelector("#dataTable tbody");
                    tableBody.innerHTML = "";
                    data.data.forEach(row => {
                        const tr = document.createElement("tr");
                        tr.innerHTML = `
                            <td>${row.documento}</td>
                            <td>${row.nombre}</td>
                            <td>${row.apellido}</td>
                            <td>${row.fecha_nacimiento}</td>
                            <td>${row.sexo}</td>
                            <td>${row.telefono}</td>
                            <td>${row.direccion}</td>
                            <td>${row.nombre_enfermedad}</td>
                            <td>${row.fecha_diagnostico}</td>
                            <td>${row.estado}</td>
                            <td>${row.observaciones}</td>
                        `;
                        tableBody.appendChild(tr);
                    });

                } else {
                    uploadMessage.className = "alert alert-danger mt-3";
                    uploadMessage.textContent = "Error al procesar archivo.";
                    uploadMessage.classList.remove("d-none");
                }
            } catch (err) {
                console.error("Error parseando JSON:", err);
                uploadMessage.className = "alert alert-danger mt-3";
                uploadMessage.textContent = "Respuesta inválida del servidor.";
                uploadMessage.classList.remove("d-none");
            }
        } else {
            uploadMessage.className = "alert alert-danger mt-3";
            uploadMessage.textContent = "Error en la subida.";
            uploadMessage.classList.remove("d-none");
        }
    };

    xhr.send(formData);
});

/* === Búsqueda dinámica con paginación === */
$(document).ready(function() {
    buscar_datos();

    // Buscar mientras escribe
    $('#caja-busqueda').on('keyup', function(){
        buscar_datos();
    });

    // Paginación
    $(document).on('click', '.pagina', function(e){
        e.preventDefault();
        let pagina = $(this).data('page');
        buscar_datos('', pagina);
    });
});

function buscar_datos(consulta = '', pagina = 1){
    consulta = $('#caja-busqueda').val();
    $.ajax({
        url: 'pacientes_listado.php',
        type: 'POST',
        data: {consulta: consulta, pagina: pagina},
        success: function(respuesta){
            $("#datos").html(respuesta);
        },
        error: function(){
            console.log("Error en la petición AJAX");
        }
    });
}
</script>

</body>
</html>