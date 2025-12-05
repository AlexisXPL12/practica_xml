<?php
$conexion = new mysqli("localhost", "root", "root", "sigi_huanta");
if ($conexion->connect_errno) {
    die("Fallo al conectar a MySQL: (" . $conexion->connect_errno . ")" . $conexion->connect_error);
}

// Crear el documento XML
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

// Nodo raíz
$programasEstudio = $xml->createElement('programas_estudio');
$xml->appendChild($programasEstudio);

// Contadores para nodos consecutivos
$contadorPrograma = 1;
$queryProgramas = "SELECT id, codigo, tipo, nombre FROM sigi_programa_estudios";
$resultProgramas = $conexion->query($queryProgramas);

if ($resultProgramas->num_rows > 0) {
    while ($programa = $resultProgramas->fetch_assoc()) {
        // Nodo programa (p1, p2, p3...)
        $programaNode = $xml->createElement('p' . $contadorPrograma);
        $programaNode->appendChild($xml->createElement('codigo', $programa['codigo']));
        $programaNode->appendChild($xml->createElement('nombre', $programa['nombre']));

        // Consultar planes de estudio asociados al programa
        $queryPlanes = "
            SELECT id, nombre, resolucion, perfil_egresado
            FROM sigi_planes_estudio
            WHERE id_programa_estudios = " . $programa['id'];
        $resultPlanes = $conexion->query($queryPlanes);

        if ($resultPlanes->num_rows > 0) {
            $planesNode = $xml->createElement('planes_estudio');
            $contadorPlan = 1;
            while ($plan = $resultPlanes->fetch_assoc()) {
                // Nodo plan (plan1, plan2, plan3...)
                $planNode = $xml->createElement('plan' . $contadorPlan);
                $planNode->appendChild($xml->createElement('nombre', $plan['nombre']));
                $planNode->appendChild($xml->createElement('resolucion', $plan['resolucion']));
                $planNode->appendChild($xml->createElement('perfil_egresado', $plan['perfil_egresado']));

                // Consultar módulos formativos asociados al plan
                $queryModulos = "
                    SELECT id, descripcion, nro_modulo
                    FROM sigi_modulo_formativo
                    WHERE id_plan_estudio = " . $plan['id'] . "
                    ORDER BY nro_modulo";
                $resultModulos = $conexion->query($queryModulos);

                if ($resultModulos->num_rows > 0) {
                    $modulosNode = $xml->createElement('modulos');
                    $contadorModulo = 1;
                    while ($modulo = $resultModulos->fetch_assoc()) {
                        // Nodo módulo (mod1, mod2, mod3...)
                        $moduloNode = $xml->createElement('mod' . $contadorModulo);
                        $moduloNode->appendChild($xml->createElement('nombre', $modulo['descripcion']));

                        // Consultar semestres asociados al módulo
                        $querySemestres = "
                            SELECT id, descripcion
                            FROM sigi_semestre
                            WHERE id_modulo_formativo = " . $modulo['id'] . "
                            ORDER BY descripcion";
                        $resultSemestres = $conexion->query($querySemestres);

                        if ($resultSemestres->num_rows > 0) {
                            $semestresNode = $xml->createElement('semestres');
                            $contadorSemestre = 1;
                            while ($semestre = $resultSemestres->fetch_assoc()) {
                                // Nodo semestre (sem1, sem2, sem3...)
                                $semestreNode = $xml->createElement('sem' . $contadorSemestre);
                                $semestreNode->appendChild($xml->createElement('nombre', $semestre['descripcion']));

                                // Consultar unidades didácticas asociadas al semestre
                                $queryUnidades = "
                                    SELECT nombre, creditos_teorico, creditos_practico, tipo
                                    FROM sigi_unidad_didactica
                                    WHERE id_semestre = " . $semestre['id'] . "
                                    ORDER BY orden";
                                $resultUnidades = $conexion->query($queryUnidades);

                                if ($resultUnidades->num_rows > 0) {
                                    $unidadesNode = $xml->createElement('unidades_didacticas');
                                    while ($unidad = $resultUnidades->fetch_assoc()) {
                                        $unidadNode = $xml->createElement('unidad_didactica');
                                        $unidadNode->appendChild($xml->createElement('nombre', $unidad['nombre']));
                                        $unidadNode->appendChild($xml->createElement('tipo', $unidad['tipo']));

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
                                $contadorSemestre++;
                            }
                            $moduloNode->appendChild($semestresNode);
                        }
                        $modulosNode->appendChild($moduloNode);
                        $contadorModulo++;
                    }
                    $planNode->appendChild($modulosNode);
                }
                $planesNode->appendChild($planNode);
                $contadorPlan++;
            }
            $programaNode->appendChild($planesNode);
        }
        $programasEstudio->appendChild($programaNode);
        $contadorPrograma++;
    }
}

$archivo = "prog_ies.xml";
$xml->save($archivo);

echo "Archivo XML generado correctamente: " . $archivo;
$conexion->close();
?>
