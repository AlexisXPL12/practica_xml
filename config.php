<?php
$conn = new mysqli("localhost", "root", "root", "sigi_huanta");
if ($conn->connect_errno) {
    die("Error de conexión: (" . $conn->connect_errno . ") " . $conn->connect_error);
}

$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

// Nodo raíz: <programas_estudio>
$programasEstudioNode = $xml->createElement('programas_estudio');
$xml->appendChild($programasEstudioNode);

// Consultar programas de estudio
$queryProgramas = "SELECT id, codigo, nombre FROM sigi_programa_estudios";
$resultProgramas = $conn->query($queryProgramas);

if ($resultProgramas->num_rows > 0) {
    while ($programa = $resultProgramas->fetch_assoc()) {
        // Nodo <programa_$id>
        $programaNode = $xml->createElement('programa_' . $programa['id']);
        $programaNode->appendChild($xml->createElement('codigo', $programa['codigo']));
        $programaNode->appendChild($xml->createElement('nombre', $programa['nombre']));

        // Consultar planes de estudio asociados al programa
        $queryPlanes = "SELECT id, nombre, resolucion, perfil_egresado FROM sigi_planes_estudio WHERE id_programa_estudios = " . $programa['id'];
        $resultPlanes = $conn->query($queryPlanes);

        if ($resultPlanes->num_rows > 0) {
            $planesNode = $xml->createElement('planes_estudio');
            while ($plan = $resultPlanes->fetch_assoc()) {
                // Nodo <plan_$id>
                $planNode = $xml->createElement('plan_' . $plan['id']);
                $planNode->appendChild($xml->createElement('nombre', $plan['nombre']));
                $planNode->appendChild($xml->createElement('resolucion', $plan['resolucion']));
                $planNode->appendChild($xml->createElement('perfil_egresado', $plan['perfil_egresado']));

                // Consultar módulos formativos asociados al plan
                $queryModulos = "SELECT id, descripcion FROM sigi_modulo_formativo WHERE id_plan_estudio = " . $plan['id'] . " ORDER BY nro_modulo";
                $resultModulos = $conn->query($queryModulos);

                if ($resultModulos->num_rows > 0) {
                    $modulosNode = $xml->createElement('modulos');
                    while ($modulo = $resultModulos->fetch_assoc()) {
                        // Nodo <modulo_$id>
                        $moduloNode = $xml->createElement('modulo_' . $modulo['id']);
                        $moduloNode->appendChild($xml->createElement('descripcion', $modulo['descripcion']));

                        // Consultar semestres asociados al módulo
                        $querySemestres = "SELECT id, descripcion FROM sigi_semestre WHERE id_modulo_formativo = " . $modulo['id'] . " ORDER BY descripcion";
                        $resultSemestres = $conn->query($querySemestres);

                        if ($resultSemestres->num_rows > 0) {
                            $semestresNode = $xml->createElement('semestres');
                            while ($semestre = $resultSemestres->fetch_assoc()) {
                                // Nodo <semestre_$id>
                                $semestreNode = $xml->createElement('semestre_' . $semestre['id']);
                                $semestreNode->appendChild($xml->createElement('descripcion', $semestre['descripcion']));

                                // Consultar unidades didácticas asociadas al semestre
                                $queryUnidades = "SELECT nombre, creditos_teorico, creditos_practico, tipo FROM sigi_unidad_didactica WHERE id_semestre = " . $semestre['id'] . " ORDER BY orden";
                                $resultUnidades = $conn->query($queryUnidades);

                                if ($resultUnidades->num_rows > 0) {
                                    $unidadesNode = $xml->createElement('unidades_didacticas');
                                    while ($unidad = $resultUnidades->fetch_assoc()) {
                                        // Nodo <unidad>
                                        $unidadNode = $xml->createElement('unidad');
                                        $unidadNode->appendChild($xml->createElement('nombre', $unidad['nombre']));
                                        $unidadNode->appendChild($xml->createElement('tipo', $unidad['tipo']));
                                        $unidadNode->appendChild($xml->createElement('creditos_teorico', $unidad['creditos_teorico']));
                                        $unidadNode->appendChild($xml->createElement('creditos_practico', $unidad['creditos_practico']));

                                        // Cálculo de horas
                                        $horasSemanales = $unidad['creditos_teorico'] + $unidad['creditos_practico'];
                                        $horasMensuales = $horasSemanales * 4;
                                        $horasTotalesSemestre = $horasSemanales * 18;

                                        $unidadNode->appendChild($xml->createElement('horas_semanales', $horasSemanales));
                                        $unidadNode->appendChild($xml->createElement('horas_mensuales', $horasMensuales));
                                        $unidadNode->appendChild($xml->createElement('horas_totales_semestre', $horasTotalesSemestre));
                                        $unidadesNode->appendChild($unidadNode);
                                    }
                                    $semestreNode->appendChild($unidadesNode);
                                }
                                $semestresNode->appendChild($semestreNode);
                            }
                            $moduloNode->appendChild($semestresNode);
                        }
                        $modulosNode->appendChild($moduloNode);
                    }
                    $planNode->appendChild($modulosNode);
                }
                $planesNode->appendChild($planNode);
            }
            $programaNode->appendChild($planesNode);
        }
        $programasEstudioNode->appendChild($programaNode);
    }
}

$file = "prog_ies.xml";
$xml->save($file);

echo "Archivo XML generado correctamente: " . $file;
$conn->close();
?>
