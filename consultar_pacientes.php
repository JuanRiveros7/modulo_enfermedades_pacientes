<?php
session_start();
require_once("controller/validar_sesion.php"); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Consultar Pacientes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="controller/estilos.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

  <div class="container py-4">

    <form id="form-busqueda" class="mb-3" onsubmit="event.preventDefault(); buscar_datos();">
      <label for="caja-busqueda" class="form-label">Buscar paciente</label>
      <input type="text" class="form-control" name="caja-busqueda" id="caja-busqueda" 
             placeholder="Escribe nombre, documento, enfermedad..." aria-label="Buscar paciente">
      <button type="button" class="btn btn-primary btn-sm mt-2" onclick="buscar_datos();">Buscar</button>
      <button type="button" class="btn btn-secondary btn-sm mt-2" 
              onclick="$('#caja-busqueda').val(''); buscar_datos();">Limpiar búsqueda</button>
    </form>

    <section id="datos" class="mt-3">
      <!-- Resultados con AJAX -->
    </section>
  </div>

  <script>
    $(document).ready(function() {
        buscar_datos();

        // Búsqueda en tiempo real
        $('#caja-busqueda').on('keyup', function(){
            var valor = $(this).val();
            if(valor.length >= 1){
                buscar_datos(valor);
            } else {
                buscar_datos();
            }
        });

        // Enter dentro del input también busca
        $('#caja-busqueda').on('keypress', function(e){
            if(e.which == 13){ // Enter
                e.preventDefault();
                buscar_datos($(this).val());
            }
        });

        // Paginación dinámica
        $(document).on('click', '.pagina', function(e){
            e.preventDefault();
            var pagina = $(this).attr('data');
            var valor = $('#caja-busqueda').val();
            buscar_datos(valor, pagina);
        });
    });

    function buscar_datos(consulta = '', pagina = 1){
        $.ajax({
            url: 'pacientes_listado.php',
            type: 'POST',
            data: {consulta: consulta, pagina: pagina},
        })
        .done(function(respuesta){
            $("#datos").html(respuesta);
        })
        .fail(function(){
            $("#datos").html('<div class="alert alert-danger">⚠️ Error al cargar los datos.</div>');
        });
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>