<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "sigi_huanta_nuevo";

// Crear conexión
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Crear la base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Base de datos '$dbname' creada correctamente.<br>";
} else {
    die("Error al crear la base de datos: " . $conn->error);
}

// Seleccionar la base de datos
$conn->select_db($dbname);

// Crear tablas si no existen
$tablas = [
    "CREATE TABLE IF NOT EXISTS sigi_programa_estudios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(10) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        tipo VARCHAR(20) NOT NULL
    )",

    "CREATE TABLE IF NOT EXISTS sigi_planes_estudio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_programa_estudios INT NOT NULL,
        nombre VARCHAR(20) NOT NULL,
        resolucion VARCHAR(100) NOT NULL,
        perfil_egresado VARCHAR(3000) NOT NULL,
        FOREIGN KEY (id_programa_estudios) REFERENCES sigi_programa_estudios(id)
    )",

    "CREATE TABLE IF NOT EXISTS sigi_modulo_formativo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        descripcion VARCHAR(1000) NOT NULL,
        nro_modulo INT NOT NULL,
        id_plan_estudio INT NOT NULL,
        FOREIGN KEY (id_plan_estudio) REFERENCES sigi_planes_estudio(id)
    )",

    "CREATE TABLE IF NOT EXISTS sigi_semestre (
        id INT AUTO_INCREMENT PRIMARY KEY,
        descripcion VARCHAR(5) NOT NULL,
        id_modulo_formativo INT NOT NULL,
        FOREIGN KEY (id_modulo_formativo) REFERENCES sigi_modulo_formativo(id)
    )",

    "CREATE TABLE IF NOT EXISTS sigi_unidad_didactica (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(200) NOT NULL,
        id_semestre INT NOT NULL,
        creditos_teorico INT NOT NULL,
        creditos_practico INT NOT NULL,
        tipo VARCHAR(20) NOT NULL,
        orden INT NOT NULL,
        FOREIGN KEY (id_semestre) REFERENCES sigi_semestre(id)
    )"
];

foreach ($tablas as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Tabla creada correctamente.<br>";
    } else {
        die("Error al crear tabla: " . $conn->error);
    }
}

// Leer el archivo XML
$xml = simplexml_load_file("prog_ies.xml");
if ($xml === FALSE) {
    die("Error: No se puede cargar el archivo XML");
}

// Insertar programas de estudio
foreach ($xml->children() as $programa) {
    $codigo = (string)$programa->codigo;
    $nombre = (string)$programa->nombre;
    $tipo = (string)$programa->tipo ?? "Modular"; // Valor por defecto si no existe

    $sql = "INSERT INTO sigi_programa_estudios (codigo, nombre, tipo) VALUES ('$codigo', '$nombre', '$tipo')";
    if ($conn->query($sql) === TRUE) {
        $id_programa = $conn->insert_id;
        echo "Programa de estudio '$nombre' insertado correctamente (ID: $id_programa).<br>";
    } else {
        die("Error al insertar programa de estudio: " . $conn->error);
    }

    // Insertar planes de estudio
    foreach ($programa->planes_estudio->children() as $plan) {
        $nombre_plan = (string)$plan->nombre;
        $resolucion = (string)$plan->resolucion;
        $perfil_egresado = $conn->real_escape_string((string)$plan->perfil_egresado);

        $sql = "INSERT INTO sigi_planes_estudio (id_programa_estudios, nombre, resolucion, perfil_egresado)
                VALUES ('$id_programa', '$nombre_plan', '$resolucion', '$perfil_egresado')";
        if ($conn->query($sql) === TRUE) {
            $id_plan = $conn->insert_id;
            echo "&nbsp;&nbsp;Plan de estudio '$nombre_plan' insertado correctamente (ID: $id_plan).<br>";
        } else {
            die("Error al insertar plan de estudio: " . $conn->error);
        }

        // Insertar módulos formativos
        foreach ($plan->modulos->children() as $modulo) {
            $descripcion = (string)$modulo->descripcion;
            $nro_modulo = (int)$modulo->nro_modulo ?? 1; // Valor por defecto si no existe

            $sql = "INSERT INTO sigi_modulo_formativo (descripcion, nro_modulo, id_plan_estudio)
                    VALUES ('$descripcion', '$nro_modulo', '$id_plan')";
            if ($conn->query($sql) === TRUE) {
                $id_modulo = $conn->insert_id;
                echo "&nbsp;&nbsp;&nbsp;&nbsp;Módulo formativo '$descripcion' insertado correctamente (ID: $id_modulo).<br>";
            } else {
                die("Error al insertar módulo formativo: " . $conn->error);
            }

            // Insertar semestres
            foreach ($modulo->semestres->children() as $semestre) {
                $descripcion_semestre = (string)$semestre->nombre;

                $sql = "INSERT INTO sigi_semestre (descripcion, id_modulo_formativo)
                        VALUES ('$descripcion_semestre', '$id_modulo')";
                if ($conn->query($sql) === TRUE) {
                    $id_semestre = $conn->insert_id;
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Semestre '$descripcion_semestre' insertado correctamente (ID: $id_semestre).<br>";
                } else {
                    die("Error al insertar semestre: " . $conn->error);
                }

                // Insertar unidades didácticas
                foreach ($semestre->unidades_didacticas->children() as $unidad) {
                    $nombre_unidad = (string)$unidad->nombre;
                    $creditos_teorico = (int)$unidad->creditos_teorico;
                    $creditos_practico = (int)$unidad->creditos_practico;
                    $tipo = (string)$unidad->tipo;
                    $orden = (int)$unidad->orden ?? 1; // Valor por defecto si no existe

                    $sql = "INSERT INTO sigi_unidad_didactica (nombre, id_semestre, creditos_teorico, creditos_practico, tipo, orden)
                            VALUES ('$nombre_unidad', '$id_semestre', '$creditos_teorico', '$creditos_practico', '$tipo', '$orden')";
                    if ($conn->query($sql) === TRUE) {
                        echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Unidad didáctica '$nombre_unidad' insertada correctamente.<br>";
                    } else {
                        die("Error al insertar unidad didáctica: " . $conn->error);
                    }
                }
            }
        }
    }
}

echo "<br>¡Proceso completado con éxito!";
$conn->close();
?>
