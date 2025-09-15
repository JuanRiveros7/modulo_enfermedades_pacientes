<?php
require_once("database/config.php");

$consulta = $_POST['consulta'] ?? '';
$pagina   = max(1, (int) ($_POST['pagina'] ?? 1));
$limite   = 5;
$inicio   = ($pagina - 1) * $limite;

$where = "";
$params = [];
$types  = "";

if(!empty($consulta)){
    $where = "WHERE p.nombre LIKE ? 
              OR p.apellido LIKE ?
              OR p.documento LIKE ?
              OR e.nombre_enfermedad LIKE ?
              OR pe.estado LIKE ?";
    $like = "%$consulta%";
    $params = [$like, $like, $like, $like, $like];
    $types  = "sssss";
}

// Total de registros
$sqlTotal = "SELECT COUNT(*) AS total
             FROM pacientes p
             LEFT JOIN paciente_enfermedad pe ON p.documento = pe.documento
             LEFT JOIN enfermedades e ON pe.id_enfermedad = e.id_enfermedad
             $where";

$stmtTotal = $con->prepare($sqlTotal);
if($where){
    $stmtTotal->bind_param($types, ...$params);
}
$stmtTotal->execute();
$total = $stmtTotal->get_result()->fetch_assoc()['total'];
$stmtTotal->close();

// Datos paginados
$sql = "SELECT p.documento, p.nombre, p.apellido, p.fecha_nacimiento, 
               p.sexo, p.telefono, p.direccion,
               e.nombre_enfermedad, pe.fecha_diagnostico, 
               pe.estado, pe.observaciones
        FROM pacientes p
        LEFT JOIN paciente_enfermedad pe ON p.documento = pe.documento
        LEFT JOIN enfermedades e ON pe.id_enfermedad = e.id_enfermedad
        $where
        ORDER BY p.documento DESC
        LIMIT ?, ?";

$stmt = $con->prepare($sql);

if($where){
    $paramsPag = array_merge($params, [$inicio, $limite]);
    $stmt->bind_param($types . "ii", ...$paramsPag);
} else {
    $stmt->bind_param("ii", $inicio, $limite);
}

$stmt->execute();
$result = $stmt->get_result();

$output = "<table class='table table-hover table-striped table-sm'>
<thead class='table-dark'>
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
<tbody>";

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $row = array_map(fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $row);
        $output .= "<tr>
            <td>{$row['documento']}</td>
            <td>{$row['nombre']}</td>
            <td>{$row['apellido']}</td>
            <td>{$row['fecha_nacimiento']}</td>
            <td>{$row['sexo']}</td>
            <td>{$row['telefono']}</td>
            <td>{$row['direccion']}</td>
            <td>{$row['nombre_enfermedad']}</td>
            <td>{$row['fecha_diagnostico']}</td>
            <td>{$row['estado']}</td>
            <td>{$row['observaciones']}</td>
        </tr>";
    }
} else {
    $output .= "<tr><td colspan='11' class='text-center'>No se encontraron pacientes</td></tr>";
}
$output .= "</tbody></table>";

// Paginación
$total_paginas = ceil($total / $limite);
if($total_paginas > 1){
    $output .= "<nav><ul class='pagination justify-content-center'>";
    for($i=1; $i<=$total_paginas; $i++){
        $active = ($i == $pagina) ? "active" : "";
        $output .= "<li class='page-item $active'>
            <a href='#' class='page-link pagina' data='$i'>$i</a>
        </li>";
    }
    $output .= "</ul></nav>";
}

echo $output;