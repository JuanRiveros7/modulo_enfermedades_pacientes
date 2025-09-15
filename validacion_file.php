<?php
require("database/config.php");
header('Content-Type: application/json; charset=utf-8');

// Valida el archivo subido
if (!isset($_FILES['dataCliente']) || $_FILES['dataCliente']['error'] != UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "Error al subir el archivo"]);
    exit;
}

$archivotmp = $_FILES['dataCliente']['tmp_name'];
$lineas     = file($archivotmp);

$i = 0;
$resultados = [];

// Detecta el delimitador en la primera línea
$firstLine = $lineas[0];
$delimiter = (substr_count($firstLine, ";") > substr_count($firstLine, ",")) ? ";" : ",";

foreach ($lineas as $linea) {
    if ($i != 0) { 
        $datos = str_getcsv($linea, $delimiter);

        // Valida que haya al menos 13 columnas en el archivo
        if (count($datos) < 13) {
            $i++;
            continue;
        }

        // Campos de la tabla pacientes
        $documento        = !empty($datos[0]) ? trim($datos[0]) : '';
        $nombre           = !empty($datos[1]) ? trim($datos[1]) : '';
        $apellido         = !empty($datos[2]) ? trim($datos[2]) : '';
        $fecha_nacimiento = !empty($datos[3]) ? trim($datos[3]) : null;
        $sexo             = !empty($datos[4]) ? trim($datos[4]) : null;
        $telefono         = !empty($datos[5]) ? trim($datos[5]) : null;
        $direccion        = !empty($datos[6]) ? trim($datos[6]) : null;

        // Campos de la tabla enfermedades
        $nombre_enfermedad = !empty($datos[7]) ? trim($datos[7]) : '';
        $descripcion       = !empty($datos[8]) ? trim($datos[8]) : null;
        $codigo_cie10      = !empty($datos[9]) ? trim($datos[9]) : null;

        // Campos de la relación
        $fecha_diagnostico = !empty($datos[10]) ? trim($datos[10]) : null;
        $estado            = !empty($datos[11]) ? trim($datos[11]) : 'Activo';
        $observaciones     = !empty($datos[12]) ? trim($datos[12]) : null;

        if (!empty($documento) && !empty($nombre_enfermedad)) {
            
            // --- PACIENTES ---
            $res_paciente = mysqli_query($con, "SELECT documento FROM pacientes WHERE documento='$documento'");
            if (mysqli_num_rows($res_paciente) == 0) {
                $insertPaciente = "INSERT INTO pacientes 
                    (documento, nombre, apellido, fecha_nacimiento, sexo, telefono, direccion) VALUES 
                    ('$documento','$nombre','$apellido','$fecha_nacimiento','$sexo','$telefono','$direccion')";
                mysqli_query($con, $insertPaciente);
            
            } else {
                $updatePaciente = "UPDATE pacientes SET 
                    nombre='$nombre',
                    apellido='$apellido',
                    fecha_nacimiento='$fecha_nacimiento',
                    sexo='$sexo',
                    telefono='$telefono',
                    direccion='$direccion'
                    WHERE documento='$documento'";
                mysqli_query($con, $updatePaciente);
            }

            // --- ENFERMEDADES ---
            $res_enfermedad = mysqli_query($con, "SELECT id_enfermedad FROM enfermedades WHERE codigo_cie10='$codigo_cie10'");
            if (mysqli_num_rows($res_enfermedad) == 0) {
                $insertEnfermedad = "INSERT INTO enfermedades 
                    (nombre_enfermedad, descripcion, codigo_cie10) VALUES 
                    ('$nombre_enfermedad','$descripcion','$codigo_cie10')";
                mysqli_query($con, $insertEnfermedad);
                $id_enfermedad = mysqli_insert_id($con);
            } else {
                $row = mysqli_fetch_assoc($res_enfermedad);
                $id_enfermedad = $row['id_enfermedad'];
            }

            // --- RELACIÓN PACIENTE - ENFERMEDAD ---
            $res_relacion = mysqli_query($con, "SELECT id_relacion FROM paciente_enfermedad 
                                                WHERE documento='$documento' AND id_enfermedad='$id_enfermedad'");
            if (mysqli_num_rows($res_relacion) == 0) {
                $insertRelacion = "INSERT INTO paciente_enfermedad 
                    (documento, id_enfermedad, fecha_diagnostico, estado, observaciones) VALUES 
                    ('$documento','$id_enfermedad','$fecha_diagnostico','$estado','$observaciones')";
                mysqli_query($con, $insertRelacion);
            
            } else {
                $updateRelacion = "UPDATE paciente_enfermedad SET 
                    fecha_diagnostico='$fecha_diagnostico',
                    estado='$estado',
                    observaciones='$observaciones'
                    WHERE documento='$documento' AND id_enfermedad='$id_enfermedad'";
                mysqli_query($con, $updateRelacion);
            }

            // Guardar en array de resultados
            $resultados[] = [
                "documento"        => $documento,
                "nombre"           => $nombre,
                "apellido"         => $apellido,
                "fecha_nacimiento" => $fecha_nacimiento,
                "sexo"             => $sexo,
                "telefono"         => $telefono,
                "direccion"        => $direccion,
                "nombre_enfermedad"=> $nombre_enfermedad,
                "fecha_diagnostico"=> $fecha_diagnostico,
                "estado"           => $estado,
                "observaciones"    => $observaciones
            ];
        }
    }
    $i++;
}

// Respuesta JSON
echo json_encode([
    "status" => "success",
    "message" => "Archivo procesado correctamente",
    "total_registros" => count($resultados),
    "data" => $resultados
]);
?>