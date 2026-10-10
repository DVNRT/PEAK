<?php
session_start();

/* =========================================================
   MODO EMBEBIDO
========================================================= */

$embedded =
    isset($_GET["embedded"]) &&
    $_GET["embedded"] === "1";


/* =========================================================
   LOGOUT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["logout"])
) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo json_encode([
        "success" => true
    ]);

    exit();
}


require_once __DIR__ . "/../backend/config/database.php";


/* =========================================================
   SEGURIDAD / USUARIO ACTUAL
========================================================= */

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = (int) $_SESSION["usuario_id"];


/* =========================================================
   DATOS DEL USUARIO
========================================================= */

$nombreUsuario = "Usuario";

$stmt = $conn->prepare("
    SELECT nombre
    FROM usuarios
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $usuario_id
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    if ($usuario =
        $resultado->fetch_assoc()
    ) {

        $nombreUsuario =
            $usuario["nombre"];
    }

    $stmt->close();
}


/* =========================================================
   DATOS INICIALES
   No se inventan actividades.
========================================================= */

$estudioHoy = 0;
$ejercicioHoy = 0;
$aguaHoy = 0;
$racha = 0;

$objetivoEstudio = 4;
$objetivoEjercicio = 60;
$objetivoAgua = 8;

$hoy = date("Y-m-d");




/* =========================================================
   SISTEMA XP / NIVELES
========================================================= */

/*
   XP PRINCIPAL:
   xp_componentes = 0, 1 o 2
   Cada componente equivale a 33.33%.
   Al llegar a 3:
   - sube el nivel principal
   - vuelve a 0
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS peak_user_progress (

        id INT AUTO_INCREMENT PRIMARY KEY,

        usuario_id INT NOT NULL,

        nivel INT NOT NULL DEFAULT 1,

        xp_componentes TINYINT NOT NULL DEFAULT 0,

        ejercicio_progreso INT NOT NULL DEFAULT 0,
        ejercicio_nivel INT NOT NULL DEFAULT 1,

        hidratacion_progreso INT NOT NULL DEFAULT 0,
        hidratacion_nivel INT NOT NULL DEFAULT 1,

        hidratacion_ultima_subida DATE NULL,

        estudio_progreso INT NOT NULL DEFAULT 0,
        estudio_nivel INT NOT NULL DEFAULT 1,

        UNIQUE KEY usuario_unico (usuario_id),

        INDEX idx_usuario (usuario_id)

    ) ENGINE=InnoDB
");


/* =========================================================
   CREAR PROGRESO DEL USUARIO
========================================================= */

$stmtProgress = $conn->prepare("
    INSERT INTO peak_user_progress
    (
        usuario_id
    )
    VALUES
    (
        ?
    )
    ON DUPLICATE KEY UPDATE
        usuario_id = VALUES(usuario_id)
");

if ($stmtProgress) {

    $stmtProgress->bind_param(
        "i",
        $usuario_id
    );

    $stmtProgress->execute();

    $stmtProgress->close();
}


/* =========================================================
   OBTENER PROGRESO XP
========================================================= */

$xpData = [

    "nivel" => 1,

    "xp_componentes" => 0,

    "ejercicio_progreso" => 0,
    "ejercicio_nivel" => 1,

    "hidratacion_progreso" => 0,
    "hidratacion_nivel" => 1,

    "hidratacion_ultima_subida" => null,

    "estudio_progreso" => 0,
    "estudio_nivel" => 1

];


$stmtProgress = $conn->prepare("
    SELECT
        nivel,
        xp_componentes,

        ejercicio_progreso,
        ejercicio_nivel,

        hidratacion_progreso,
        hidratacion_nivel,
        hidratacion_ultima_subida,

        estudio_progreso,
        estudio_nivel

    FROM peak_user_progress

    WHERE usuario_id = ?

    LIMIT 1
");

if ($stmtProgress) {

    $stmtProgress->bind_param(
        "i",
        $usuario_id
    );

    $stmtProgress->execute();

    $resultadoProgress =
        $stmtProgress->get_result();

    if ($filaProgress =
        $resultadoProgress->fetch_assoc()
    ) {

        $xpData = $filaProgress;
    }

    $stmtProgress->close();
}


/* =========================================================
   VARIABLES DEL SISTEMA XP
========================================================= */

$nivelUsuario =
    (int) $xpData["nivel"];

$xpComponentes =
    (int) $xpData["xp_componentes"];

$ejercicioProgreso =
    (int) $xpData["ejercicio_progreso"];

$ejercicioNivel =
    (int) $xpData["ejercicio_nivel"];

$hidratacionProgreso =
    (int) $xpData["hidratacion_progreso"];

$hidratacionNivel =
    (int) $xpData["hidratacion_nivel"];

$hidratacionUltimaSubida =
    $xpData["hidratacion_ultima_subida"];

$estudioProgreso =
    (int) $xpData["estudio_progreso"];

$estudioNivel =
    (int) $xpData["estudio_nivel"];


/* =========================================================
   XP PRINCIPAL
========================================================= */

$porcentajeXP =
    ($xpComponentes / 3) * 100;


/* =========================================================
   FUNCIÓN PARA DAR 33.33% DE XP
========================================================= */

function otorgarXPPrincipal(
    $conn,
    $usuario_id
) {

    $stmt = $conn->prepare("
        SELECT
            nivel,
            xp_componentes

        FROM peak_user_progress

        WHERE usuario_id = ?

        LIMIT 1
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        "i",
        $usuario_id
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $datos =
        $resultado->fetch_assoc();

    $stmt->close();


    if (!$datos) {
        return;
    }


    $nivel =
        (int) $datos["nivel"];

    $componentes =
        (int) $datos["xp_componentes"];


    /*
       Cada nivel de ámbito aporta
       1/3 de la barra principal.
    */

    $componentes++;


    /*
       Al completar los 3 ámbitos:
       sube el nivel principal.
    */

    if ($componentes >= 3) {

        $componentes = 0;

        $nivel++;
    }


    $stmt = $conn->prepare("
        UPDATE peak_user_progress

        SET
            nivel = ?,
            xp_componentes = ?

        WHERE usuario_id = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "iii",
            $nivel,
            $componentes,
            $usuario_id
        );

        $stmt->execute();

        $stmt->close();
    }
}



/* =========================================================
   SISTEMA DIARIO DE HÁBITOS Y USO
========================================================= */

/*
   Esta tabla guarda una fila por usuario y por día.

   app_opens:
   cantidad de veces que se abrió/entró a la aplicación.

   app_minutes:
   minutos de uso activo.

   exercise_done:
   1 = hizo ejercicio ese día.

   exercise_minutes:
   minutos de ejercicio.

   water_glasses:
   vasos de agua registrados.
*/

$conn->query("
    CREATE TABLE IF NOT EXISTS peak_daily_activity (

        id INT AUTO_INCREMENT PRIMARY KEY,

        usuario_id INT NOT NULL,

        fecha DATE NOT NULL,

        app_opens INT NOT NULL DEFAULT 0,

        app_minutes INT NOT NULL DEFAULT 0,

        exercise_done TINYINT(1) NOT NULL DEFAULT 0,

        exercise_minutes INT NOT NULL DEFAULT 0,

        water_glasses INT NOT NULL DEFAULT 0,

        UNIQUE KEY usuario_fecha (usuario_id, fecha),

        INDEX idx_usuario (usuario_id),

        INDEX idx_fecha (fecha)

    ) ENGINE=InnoDB
");


/* =========================================================
   ASEGURAR REGISTRO DEL DÍA
========================================================= */

$stmtDaily = $conn->prepare("
    INSERT INTO peak_daily_activity
    (
        usuario_id,
        fecha
    )
    VALUES
    (
        ?,
        ?
    )
    ON DUPLICATE KEY UPDATE
        fecha = VALUES(fecha)
");

if ($stmtDaily) {

    $stmtDaily->bind_param(
        "is",
        $usuario_id,
        $hoy
    );

    $stmtDaily->execute();

    $stmtDaily->close();
}


/* =========================================================
   API INTERNA DEL DASHBOARD
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["dashboard_action"])
) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );


    $accion =
        $_POST["dashboard_action"];


   /* =====================================================
   MARCAR EJERCICIO
===================================================== */

if ($accion === "toggle_exercise") {

    $stmt = $conn->prepare("
        SELECT exercise_done
        FROM peak_daily_activity
        WHERE usuario_id = ?
        AND fecha = ?
        LIMIT 1
    ");

    $actual = 0;

    if ($stmt) {

        $stmt->bind_param(
            "is",
            $usuario_id,
            $hoy
        );

        $stmt->execute();

        $resultado =
            $stmt->get_result();

        if ($fila =
            $resultado->fetch_assoc()
        ) {

            $actual =
                (int) $fila["exercise_done"];
        }

        $stmt->close();
    }


    /*
       Si ya estaba marcado, no hacemos
       nada para evitar ganar XP varias
       veces el mismo día.
    */

    if ($actual === 1) {

        echo json_encode([
            "success" => true,
            "exercise_done" => 1,
            "exercise_minutes" => 30,
            "message" => "El ejercicio de hoy ya fue registrado."
        ]);

        exit();
    }


    /* =====================================================
       MARCAR COMO HECHO
    ===================================================== */

    $nuevo = 1;

    $minutosEjercicio = 30;


    $stmt = $conn->prepare("
        UPDATE peak_daily_activity

        SET
            exercise_done = ?,
            exercise_minutes = ?

        WHERE usuario_id = ?
        AND fecha = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "iiis",
            $nuevo,
            $minutosEjercicio,
            $usuario_id,
            $hoy
        );

        $stmt->execute();

        $stmt->close();
    }


    /* =====================================================
       DAR +20% AL PROGRESO DE EJERCICIO
    ===================================================== */

    $stmt = $conn->prepare("
        SELECT
            ejercicio_progreso,
            ejercicio_nivel

        FROM peak_user_progress

        WHERE usuario_id = ?

        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $usuario_id
        );

        $stmt->execute();

        $resultado =
            $stmt->get_result();

        $xp =
            $resultado->fetch_assoc();

        $stmt->close();


        if ($xp) {

            $progreso =
                (int) $xp["ejercicio_progreso"];

            $nivel =
                (int) $xp["ejercicio_nivel"];


            $progreso += 20;


            /*
               100% = subida de nivel.
            */

            if ($progreso >= 100) {

                $progreso = 0;

                $nivel++;


                /*
                   Ejercicio acaba de subir
                   de nivel → +33.33% XP principal.
                */

                otorgarXPPrincipal(
                    $conn,
                    $usuario_id
                );
            }


            $stmtUpdate =
                $conn->prepare("
                    UPDATE peak_user_progress

                    SET
                        ejercicio_progreso = ?,
                        ejercicio_nivel = ?

                    WHERE usuario_id = ?
                ");

            if ($stmtUpdate) {

                $stmtUpdate->bind_param(
                    "iii",
                    $progreso,
                    $nivel,
                    $usuario_id
                );

                $stmtUpdate->execute();

                $stmtUpdate->close();
            }
        }
    }


    echo json_encode([
        "success" => true,
        "exercise_done" => 1,
        "exercise_minutes" => 30
    ]);

    exit();
}


    /* =====================================================
       AGREGAR VASO
    ===================================================== */

   /* =====================================================
   AGREGAR VASO
===================================================== */

if ($accion === "add_water") {

    /* =====================================================
       OBTENER VASOS ACTUALES
    ===================================================== */

    $vasosActuales = 0;

    $stmt = $conn->prepare("
        SELECT water_glasses
        FROM peak_daily_activity

        WHERE usuario_id = ?
        AND fecha = ?

        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "is",
            $usuario_id,
            $hoy
        );

        $stmt->execute();

        $resultado =
            $stmt->get_result();

        if ($fila =
            $resultado->fetch_assoc()
        ) {

            $vasosActuales =
                (int) $fila["water_glasses"];
        }

        $stmt->close();
    }


    /* =====================================================
       MÁXIMO 8 VASOS POR DÍA
    ===================================================== */

    if ($vasosActuales >= 8) {

        echo json_encode([
            "success" => false,
            "water_glasses" => 8,
            "message" =>
                "Ya registraste los 8 vasos de hoy."
        ]);

        exit();
    }


    /* =====================================================
       SUMAR VASO
    ===================================================== */

    $stmt = $conn->prepare("
        UPDATE peak_daily_activity

        SET water_glasses =
            LEAST(water_glasses + 1, 8)

        WHERE usuario_id = ?
        AND fecha = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "is",
            $usuario_id,
            $hoy
        );

        $stmt->execute();

        $stmt->close();
    }


    $vasos =
        $vasosActuales + 1;


    if ($vasos > 8) {
        $vasos = 8;
    }


    /* =====================================================
       CADA 2 VASOS = +25%
    ===================================================== */

    if (
        $vasos % 2 === 0 &&
        $vasos <= 8
    ) {

        $stmt = $conn->prepare("
            SELECT
                hidratacion_progreso,
                hidratacion_nivel,
                hidratacion_ultima_subida

            FROM peak_user_progress

            WHERE usuario_id = ?

            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                "i",
                $usuario_id
            );

            $stmt->execute();

            $resultado =
                $stmt->get_result();

            $xp =
                $resultado->fetch_assoc();

            $stmt->close();


            if ($xp) {

                $progreso =
                    (int) $xp[
                        "hidratacion_progreso"
                    ];

                $nivel =
                    (int) $xp[
                        "hidratacion_nivel"
                    ];

                $ultimaSubida =
                    $xp[
                        "hidratacion_ultima_subida"
                    ];


                /*
                   Solo se permite una subida
                   de hidratación por día.

                   Esto evita que alguien quite
                   y vuelva a agregar vasos para
                   conseguir XP infinita el mismo día.
                */

                $puedeSubirNivel = true;

                if (
                    $ultimaSubida === $hoy
                ) {

                    $puedeSubirNivel = false;
                }


                /*
                   Cada pareja de vasos aporta 25%.
                */

                if ($puedeSubirNivel) {

                    $progreso += 25;


                    /*
                       Al llegar a 100%:
                       - sube nivel
                       - reinicia barra
                       - +33.33% XP principal
                    */

                    if ($progreso >= 100) {

                        $progreso = 0;

                        $nivel++;

                        otorgarXPPrincipal(
                            $conn,
                            $usuario_id
                        );


                        $ultimaSubida =
                            $hoy;
                    }


                    $stmtUpdate =
                        $conn->prepare("
                            UPDATE peak_user_progress

                            SET
                                hidratacion_progreso = ?,
                                hidratacion_nivel = ?,
                                hidratacion_ultima_subida = ?

                            WHERE usuario_id = ?
                        ");

                    if ($stmtUpdate) {

                        $stmtUpdate->bind_param(
                            "iisi",
                            $progreso,
                            $nivel,
                            $ultimaSubida,
                            $usuario_id
                        );

                        $stmtUpdate->execute();

                        $stmtUpdate->close();
                    }
                }
            }
        }
    }


    echo json_encode([
        "success" => true,
        "water_glasses" => $vasos
    ]);

    exit();
}


    /* =====================================================
       QUITAR VASO
    ===================================================== */

    if ($accion === "remove_water") {

        $stmt = $conn->prepare("
            UPDATE peak_daily_activity

            SET water_glasses =
                GREATEST(water_glasses - 1, 0)

            WHERE usuario_id = ?
            AND fecha = ?
        ");

        if ($stmt) {

            $stmt->bind_param(
                "is",
                $usuario_id,
                $hoy
            );

            $stmt->execute();

            $stmt->close();
        }


        $vasos = 0;


        $stmt = $conn->prepare("
            SELECT water_glasses
            FROM peak_daily_activity
            WHERE usuario_id = ?
            AND fecha = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                "is",
                $usuario_id,
                $hoy
            );

            $stmt->execute();

            $resultado =
                $stmt->get_result();

            if ($fila =
                $resultado->fetch_assoc()
            ) {

                $vasos =
                    (int) $fila["water_glasses"];
            }

            $stmt->close();
        }


        echo json_encode([
            "success" => true,
            "water_glasses" => $vasos
        ]);

        exit();
    }


    /* =====================================================
       REGISTRAR APERTURA
    ===================================================== */

    if ($accion === "register_open") {

        $stmt = $conn->prepare("
            UPDATE peak_daily_activity

            SET app_opens =
                app_opens + 1

            WHERE usuario_id = ?
            AND fecha = ?
        ");

        if ($stmt) {

            $stmt->bind_param(
                "is",
                $usuario_id,
                $hoy
            );

            $stmt->execute();

            $stmt->close();
        }


        echo json_encode([
            "success" => true
        ]);

        exit();
    }


    /* =====================================================
       SUMAR MINUTO ACTIVO
    ===================================================== */

    /* =====================================================
   SUMAR MINUTO ACTIVO + XP DE ESTUDIO
===================================================== */

if ($accion === "add_minute") {

    /* =====================================================
       SUMAR MINUTO DE USO
    ===================================================== */

    $stmt = $conn->prepare("
        UPDATE peak_daily_activity

        SET app_minutes =
            app_minutes + 1

        WHERE usuario_id = ?
        AND fecha = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "is",
            $usuario_id,
            $hoy
        );

        $stmt->execute();

        $stmt->close();
    }


    /* =====================================================
       PROGRESO DE ESTUDIO
       
       30 min = 25%
       60 min = 50%
       90 min = 75%
       120 min = 100%
    ===================================================== */

    $stmt = $conn->prepare("
        SELECT
            estudio_progreso,
            estudio_nivel

        FROM peak_user_progress

        WHERE usuario_id = ?

        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $usuario_id
        );

        $stmt->execute();

        $resultado =
            $stmt->get_result();

        $xp =
            $resultado->fetch_assoc();

        $stmt->close();


        if ($xp) {

            $progreso =
                (int) $xp[
                    "estudio_progreso"
                ];

            $nivel =
                (int) $xp[
                    "estudio_nivel"
                ];


            /*
               Cada minuto activo suma
               un minuto al progreso.

               120 minutos = 1 nivel.
            */

            $progreso++;


            /*
               Se permite incluso que el usuario
               complete más de un nivel si por
               alguna razón llegan varios minutos
               pendientes.
            */

            while ($progreso >= 120) {

                $progreso -= 120;

                $nivel++;


                /*
                   Cada nivel de estudio:
                   +33.33% XP principal.
                */

                otorgarXPPrincipal(
                    $conn,
                    $usuario_id
                );
            }


            $stmtUpdate =
                $conn->prepare("
                    UPDATE peak_user_progress

                    SET
                        estudio_progreso = ?,
                        estudio_nivel = ?

                    WHERE usuario_id = ?
                ");

            if ($stmtUpdate) {

                $stmtUpdate->bind_param(
                    "iii",
                    $progreso,
                    $nivel,
                    $usuario_id
                );

                $stmtUpdate->execute();

                $stmtUpdate->close();
            }
        }
    }


    echo json_encode([
        "success" => true
    ]);

    exit();
}


    /* =====================================================
       REGISTRAR MINUTOS DE EJERCICIO
    ===================================================== */

    if ($accion === "add_exercise_minutes") {

        $minutos =
            isset($_POST["minutes"])
                ? max(
                    0,
                    (int) $_POST["minutes"]
                )
                : 0;


        if ($minutos > 0) {

            $stmt = $conn->prepare("
                UPDATE peak_daily_activity

                SET
                    exercise_done = 1,
                    exercise_minutes =
                        exercise_minutes + ?

                WHERE usuario_id = ?
                AND fecha = ?
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "iis",
                    $minutos,
                    $usuario_id,
                    $hoy
                );

                $stmt->execute();

                $stmt->close();
            }
        }


        echo json_encode([
            "success" => true
        ]);

        exit();
    }


    echo json_encode([
        "success" => false,
        "message" => "Acción no válida"
    ]);

    exit();
}


/* =========================================================
   OBTENER DATOS DIARIOS
========================================================= */

$actividadHoy = [

    "app_opens" => 0,

    "app_minutes" => 0,

    "exercise_done" => 0,

    "exercise_minutes" => 0,

    "water_glasses" => 0
];


$stmt = $conn->prepare("
    SELECT
        app_opens,
        app_minutes,
        exercise_done,
        exercise_minutes,
        water_glasses

    FROM peak_daily_activity

    WHERE usuario_id = ?
    AND fecha = ?

    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "is",
        $usuario_id,
        $hoy
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    if ($fila =
        $resultado->fetch_assoc()
    ) {

        $actividadHoy =
            $fila;
    }

    $stmt->close();
}


$aperturasHoy =
    (int) $actividadHoy["app_opens"];

$appMinutesHoy =
    (int) $actividadHoy["app_minutes"];

$ejercicioMarcado =
    (int) $actividadHoy["exercise_done"];

$ejercicioMinutos =
    (int) $actividadHoy["exercise_minutes"];

$aguaHoy =
    (int) $actividadHoy["water_glasses"];













/* =========================================================
   EVENTOS DEL USUARIO
========================================================= */

$eventos = [];

$stmt = $conn->prepare("
    SELECT
        id,
        titulo,
        descripcion,
        fecha,
        hora_inicio,
        hora_fin,
        tipo
    FROM calendar_events
    WHERE usuario_id = ?
    ORDER BY fecha ASC, hora_inicio ASC
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $usuario_id
    );

    $stmt->execute();

    $resultadoEventos =
        $stmt->get_result();

    while (
        $evento =
        $resultadoEventos->fetch_assoc()
    ) {

        $eventos[] = $evento;
    }

    $stmt->close();
}


/* =========================================================
   ACTIVIDAD DEL DÍA
========================================================= */

$eventosHoy = [];

foreach ($eventos as $evento) {

    if ($evento["fecha"] === $hoy) {
        $eventosHoy[] = $evento;
    }
}


/* =========================================================
   DURACIÓN DE LOS EVENTOS
========================================================= */

function calcularMinutos($inicio, $fin)
{
    if (!$inicio || !$fin) {
        return 0;
    }

    $inicioTime = strtotime($inicio);
    $finTime = strtotime($fin);

    if (
        $inicioTime === false ||
        $finTime === false
    ) {
        return 0;
    }

    $minutos =
        ($finTime - $inicioTime) / 60;

    if ($minutos < 0) {
        $minutos += 24 * 60;
    }

    return (int) $minutos;
}


/* =========================================================
   CLASIFICACIÓN DE ACTIVIDADES
========================================================= */

foreach ($eventosHoy as $evento) {

    $tipo =
        strtolower(
            trim(
                $evento["tipo"] ?? ""
            )
        );

    $titulo =
        strtolower(
            trim(
                $evento["titulo"] ?? ""
            )
        );

    $descripcion =
        strtolower(
            trim(
                $evento["descripcion"] ?? ""
            )
        );

    $texto =
        $tipo . " " .
        $titulo . " " .
        $descripcion;

    $minutos =
        calcularMinutos(
            $evento["hora_inicio"],
            $evento["hora_fin"]
        );


    /* ESTUDIO */

    if (
        strpos($texto, "estudio") !== false ||
        strpos($texto, "estudiar") !== false ||
        strpos($texto, "tarea") !== false ||
        strpos($texto, "clase") !== false ||
        strpos($texto, "fisica") !== false ||
        strpos($texto, "física") !== false ||
        strpos($texto, "matematica") !== false ||
        strpos($texto, "matemática") !== false ||
        strpos($texto, "proyecto") !== false
    ) {

        $estudioHoy += $minutos;
    }


    /* EJERCICIO */

    if (
        strpos($texto, "ejercicio") !== false ||
        strpos($texto, "gym") !== false ||
        strpos($texto, "gimnasio") !== false ||
        strpos($texto, "entrenamiento") !== false ||
        strpos($texto, "correr") !== false ||
        strpos($texto, "caminar") !== false ||
        strpos($texto, "deporte") !== false
    ) {

        $ejercicioHoy += $minutos;
    }


    /* AGUA */

    if (
        strpos($texto, "agua") !== false ||
        strpos($texto, "hidratación") !== false ||
        strpos($texto, "hidratacion") !== false
    ) {

        $aguaHoy++;
    }
}


/* =========================================================
   CONVERSIÓN DE ESTUDIO
========================================================= */

$estudioHoras =
    $estudioHoy / 60;


/* =========================================================
   PORCENTAJES
========================================================= */

$porcentajeEstudio = 0;
$porcentajeEjercicio = 0;
$porcentajeAgua = 0;

if ($objetivoEstudio > 0) {

    $porcentajeEstudio =
        min(
            ($estudioHoras / $objetivoEstudio) * 100,
            100
        );
}

if ($objetivoEjercicio > 0) {

    $porcentajeEjercicio =
        min(
            ($ejercicioHoy / $objetivoEjercicio) * 100,
            100
        );
}

$bloquesAgua =
    floor($aguaHoy / 2);

$objetivoBloquesAgua =
    floor($objetivoAgua / 2);

$porcentajeAgua = 0;

if ($objetivoBloquesAgua > 0) {

    $porcentajeAgua =
        min(
            ($bloquesAgua /
            $objetivoBloquesAgua) * 100,
            100
        );
}

/* =========================================================
   RACHA
   UN DÍA CUENTA CUANDO SE COMPLETA 1 HORA
========================================================= */

$diasConHora = [];


$stmtRacha = $conn->prepare("
    SELECT
        fecha,
        app_minutes

    FROM peak_daily_activity

    WHERE usuario_id = ?

    AND fecha <= ?

    AND app_minutes >= 60

    ORDER BY fecha DESC
");

if ($stmtRacha) {

    $stmtRacha->bind_param(
        "is",
        $usuario_id,
        $hoy
    );

    $stmtRacha->execute();

    $resultadoRacha =
        $stmtRacha->get_result();

    while (
        $filaRacha =
        $resultadoRacha->fetch_assoc()
    ) {

        $diasConHora[
            $filaRacha["fecha"]
        ] = true;
    }

    $stmtRacha->close();
}


$racha = 0;


$fechaComprobar =
    new DateTime($hoy);


while (
    isset(
        $diasConHora[
            $fechaComprobar->format("Y-m-d")
        ]
    )
) {

    $racha++;

    $fechaComprobar->modify("-1 day");
}



/* =========================================================
   NIVEL PRINCIPAL DEL USUARIO
========================================================= */

$nivel =
    $nivelUsuario;


/* =========================================================
   XP PRINCIPAL
========================================================= */

$xpComponentesActuales =
    $xpComponentes;

$porcentajeXP =
    ($xpComponentesActuales / 3) * 100;


/* =========================================================
   PROGRESOS DE LOS ÁMBITOS
========================================================= */

$porcentajeEjercicioXP =
    min(
        $ejercicioProgreso,
        100
    );


$porcentajeHidratacionXP =
    min(
        $hidratacionProgreso,
        100
    );


$porcentajeEstudioXP =
    min(
        ($estudioProgreso / 120) * 100,
        100
    );


/* =========================================================
   PROGRESO DE RACHA
========================================================= */

$objetivoRacha = 7;

$porcentajeRacha =
    min(
        ($racha / $objetivoRacha) * 100,
        100
    );


/* =========================================================
   EVENTOS PRÓXIMOS
========================================================= */

$proximosEventos = [];

foreach ($eventos as $evento) {

    if (
        !empty($evento["fecha"]) &&
        $evento["fecha"] >= $hoy
    ) {

        $proximosEventos[] =
            $evento;
    }
}

usort(
    $proximosEventos,
    function ($a, $b) {

        $fechaA =
            $a["fecha"] . " " .
            ($a["hora_inicio"] ?? "00:00:00");

        $fechaB =
            $b["fecha"] . " " .
            ($b["hora_inicio"] ?? "00:00:00");

        return
            strtotime($fechaA) -
            strtotime($fechaB);
    }
);

$proximosEventos =
    array_slice(
        $proximosEventos,
        0,
        2
    );


/* =========================================================
   ACTIVIDAD DE LOS ÚLTIMOS 7 DÍAS
========================================================= */

$semana = [];

for ($i = 6; $i >= 0; $i--) {

    $fecha =
        new DateTime($hoy);

    $fecha->modify(
        "-" . $i . " days"
    );

    $fechaSQL =
        $fecha->format("Y-m-d");

    $minutosDia = 0;

    foreach ($eventos as $evento) {

        if (
            $evento["fecha"] ===
            $fechaSQL
        ) {

            $minutosDia +=
                calcularMinutos(
                    $evento["hora_inicio"],
                    $evento["hora_fin"]
                );
        }
    }

    $semana[] = [

        "fecha" =>
            $fechaSQL,

        "dia" =>
            strtoupper(
                $fecha->format("D")
            ),

        "minutos" =>
            $minutosDia
    ];
}


/* =========================================================
   ALTURA DEL GRÁFICO
========================================================= */

$maxMinutos = 1;

foreach ($semana as $dia) {

    if (
        $dia["minutos"] >
        $maxMinutos
    ) {

        $maxMinutos =
            $dia["minutos"];
    }
}


/* =========================================================
   INICIAL
========================================================= */

$inicialUsuario =
    strtoupper(
        mb_substr(
            trim($nombreUsuario),
            0,
            1,
            "UTF-8"
        )
    );

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>𝐃𝐀𝐒𝐇𝐁𝐎𝐀𝐑𝐃</title>

<link
    rel="icon"
    type="image/png"
    href="../data/descarga.png"
>

<style>

/* =========================================================
   VARIABLES
========================================================= */

:root {

    --bg: #041b12;
    --bg-dark: #06251a;
    --green-dark: #063d29;
    --green: #087f46;
    --green-main: #12a85b;
    --green-light: #35d477;
    --lime: #9be15d;
    --mint: #c9f7b8;
    --turquoise: #20d5a2;

    --white: #f2fff0;
    --text: #dff8df;
    --muted: #9bc5a8;

    --glass: rgba(5,45,28,.76);
    --glass-light: rgba(60,190,110,.10);

    --border: rgba(129,235,153,.22);

    --shadow:
        0 20px 60px rgba(0,0,0,.38);

    --radius: 22px;
}


/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    min-height: 100vh;

    font-family:
       VCR OSD MONO;

    color: var(--text);

    background:
        var(--bg);

    overflow-x: hidden;
}

a {
    text-decoration: none;
    color: inherit;
}

button {
    font-family: inherit;
}


/* =========================================================
   VIDEO
========================================================= */

#videobackground {

    position: fixed;

    inset: 0;

    width: 100%;
    height: 100%;

    object-fit: cover;

    z-index: -5;

    filter:
        brightness(.60)
        saturate(1.35)
        contrast(1.05);
}

.video-overlay {

    position: fixed;

    inset: 0;

    z-index: -4;

    background:

        radial-gradient(
            circle at 75% 15%,
            rgba(72,255,134,.20),
            transparent 30%
        ),

        radial-gradient(
            circle at 15% 80%,
            rgba(0,205,146,.18),
            transparent 30%
        ),

        linear-gradient(
            120deg,
            rgba(1,28,17,.78),
            rgba(3,72,40,.55),
            rgba(1,25,15,.76)
        );
}


/* =========================================================
   APP
========================================================= */

.app {
    min-height: 100vh;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    width: 88px;

    min-height: 100vh;

    position: fixed;

    left: 18px;
    top: 18px;
    bottom: 18px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: space-between;

    padding: 20px 12px;

    background:
        rgba(3,45,27,.78);

    border:
        1px solid var(--border);

    border-radius: 26px;

    backdrop-filter:
        blur(22px);

    -webkit-backdrop-filter:
        blur(22px);

    box-shadow:
        var(--shadow);

    z-index: 20;
}


.logo {

    width: 52px;
    height: 52px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 17px;

    background:
        linear-gradient(
            145deg,
            #087642
        );

    color: white;

    font-size: 20px;

    font-weight: 800;

    box-shadow:
        0 8px 25px rgba(0,0,0,.25);
}


.logo img {

    width: 100%;
    height: 100%;

    object-fit: cover;

    display: block;

    filter:
        brightness(100)
        saturate(135)
        contrast(1.05);
}


.navigation {

    display: flex;

    flex-direction: column;

    gap: 11px;

    align-items: center;
}


.nav-item {

    position: relative;

    width: 54px;
    height: 54px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 17px;

    color: #83ad92;

    font-size: 22px;

    transition: .25s ease;

    cursor: pointer;
}


.nav-item:hover {

    color: white;

    background:
        rgba(60,190,110,.12);

    transform:
        translateY(-2px);
}


.nav-item.active {

    color: white;

    background:
        linear-gradient(
            145deg,
            rgba(30,219,117,.48),
            rgba(8,118,66,.65)
        );

    box-shadow:
        inset 0 0 0 1px
        rgba(255,255,255,.10),
        0 8px 25px rgba(0,0,0,.20);
}


.nav-item.active::before {

    content: "";

    position: absolute;

    left: -13px;

    width: 4px;
    height: 24px;

    border-radius: 10px;

    background:
        var(--green-light);
}


.tooltip {

    position: absolute;

    left: 70px;

    padding:
        7px 11px;

    border-radius: 8px;

    background:
        #04140c;

    color: white;

    font-size: 12px;

    white-space: nowrap;

    opacity: 0;

    pointer-events: none;

    transform:
        translateX(-5px);

    transition: .2s;
}


.nav-item:hover .tooltip {

    opacity: 1;

    transform:
        translateX(0);
}


.logout {
    color: #9b7777;
}

.logout:hover {

    color: #ffaaaa;

    background:
        rgba(130,50,50,.15);
}


/* =========================================================
   MAIN
========================================================= */

.main {

    width:
        calc(100% - 126px);

    margin-left:
        126px;

    padding:
        30px 40px 40px 20px;

    min-width: 0;
}


.container {

    max-width:
        1450px;

    margin:
        auto;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 30px;
}


.welcome {

    display: flex;

    flex-direction: column;

    gap: 6px;
}


.small-title {

    color:
        var(--green-light);

    font-size: 13px;

    letter-spacing: 2px;

    text-transform: uppercase;

    font-weight: 600;
}


.welcome h1 {

    font-size:
        clamp(32px,4vw,52px);

    line-height: 1;

    letter-spacing: -2px;

    color:
        var(--white);
}


.welcome h1 span {

    color:
        var(--green-light);
}


.welcome p {

    color:
        var(--muted);

    font-size: 14px;
}


/* =========================================================
   PROFILE
========================================================= */

.profile {

    display: flex;

    align-items: center;

    gap: 12px;

    padding:
        8px 12px 8px 8px;

    border-radius: 16px;

    background:
        rgba(60,190,110,.08);

    border:
        1px solid var(--border);
}


.avatar {

    width: 42px;
    height: 42px;

    border-radius: 13px;

    display: flex;

    align-items: center;
    justify-content: center;

    background:
        linear-gradient(
            145deg,
            #1edb75,
            #087642
        );

    font-weight: 800;

    color: white;
}


.profile-info {

    display: flex;

    flex-direction: column;

    gap: 2px;
}


.profile-info strong {
    font-size: 13px;
}

.profile-info span {

    font-size: 11px;

    color:
        var(--muted);
}


/* =========================================================
   STATS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4,1fr);

    gap: 16px;

    margin-bottom: 18px;
}


.stat-card {

    position: relative;

    overflow: hidden;

    padding: 22px;

    min-height: 150px;

    background:
        linear-gradient(
            145deg,
            rgba(8, 80, 45, 0.43),
            rgba(4, 35, 22, 0.46)
        );

    border:
        1px solid var(--border);

    border-radius:
        var(--radius);

    backdrop-filter:
        blur(12px);

    box-shadow:
        var(--shadow);

    transition:
        .3s ease;
}


.stat-card:hover {

    transform:
        translateY(-4px);

    border-color:
        rgba(53,212,119,.35);
}


.stat-top {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom: 15px;
}


.stat-icon {

    width: 38px;
    height: 38px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background:
        rgba(53,212,119,.10);

    font-size: 18px;
}


.stat-label {

    font-family: Minecraft;

    font-weight: lighter;

    color:
        var(--muted);

    font-size: 18px;
}


.stat-number {

    font-family: Minecraft;

    font-weight: lighter;

    font-size: 30px;

    letter-spacing: -1px;
}


.stat-number small {

    font-family: Minecraft;

    font-weight: lighter;

    font-size: 13px;

    color:
        var(--muted);
}


.progress {

    width: 100%;

    height: 5px;

    margin-top: 13px;

    border-radius: 20px;

    overflow: hidden;

    background:
        rgba(255,255,255,.07);
}


.progress-bar {

    height: 100%;

    border-radius: inherit;

    background:
        linear-gradient(
            90deg,
            #087f46,
            #35d477,
            #9be15d
        );

    transition:
        width .5s ease;
}


/* =========================================================
   DASHBOARD GRID
========================================================= */

.dashboard-grid {

    display: grid;

    grid-template-columns:
        minmax(0,1.65fr)
        minmax(280px,.85fr);

    gap: 18px;
}


.card {

    background:
        rgba(4, 38, 24, 0.51);

    border:
        1px solid var(--border);

    border-radius:
        var(--radius);

    backdrop-filter:
        blur(2px);

    box-shadow:
        var(--shadow);
}


.card-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        22px 23px 12px;
}


.card-title {

    display: flex;

    flex-direction: column;

    gap: 4px;
}


.card-title h2 {
    font-size: 27px;
}

.card-title p {

    color:
        var(--muted);

    font-size: 16px;
}


.view-all {

    font-size: 12px;

    color:
        var(--green-light);

    cursor: pointer;
}


/* =========================================================
   CHART
========================================================= */

.chart-card {
    min-height: 355px;
}


.chart {

    height: 255px;

    display: flex;

    align-items: flex-end;

    gap: 18px;

    padding:
        30px 25px 22px;
}


.bar-column {

    height: 100%;

    flex: 1;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: flex-end;

    gap: 9px;
}


.bar-value {

    font-size: 10px;

    color:
        var(--muted);
}


.bar-area {

    width: 100%;

    height: 190px;

    display: flex;

    align-items: flex-end;

    justify-content: center;
}


.bar {

    width:
        min(34px,75%);

    min-height: 2px;

    border-radius:
        9px 9px 4px 4px;

    background:
        linear-gradient(
            to top,
            #087f46,
            #35d477
        );

    box-shadow:
        0 6px 20px
        rgba(53,212,119,.16);

    animation:
        growBar 1s ease backwards;

    transform-origin:
        bottom;
}


.bar.active {

    background:
        linear-gradient(
            to top,
            #12a85b,
            #9be15d
        );

    box-shadow:
        0 0 25px
        rgba(53,212,119,.22);
}


@keyframes growBar {

    from {
        transform:
            scaleY(0);
    }

    to {
        transform:
            scaleY(1);
    }
}


.day {

    font-size: 11px;

    color:
        #7da48a;
}


/* =========================================================
   SIDE COLUMN
========================================================= */

.side-column {

    display: flex;

    flex-direction: column;

    gap: 18px;
}


/* =========================================================
   STREAK
========================================================= */

.streak-card {

    padding: 24px;

    min-height: 170px;

    position: relative;

    overflow: hidden;
}


.streak-content {

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.streak-number {

    font-size: 50px;

    font-weight: 800;

    line-height: 1;
}


.streak-number span {

    font-size: 16px;

    color:
        var(--muted);

    font-weight: 400;
}


.streak-flame {

    font-size: 52px;

    color:
        var(--green-light);
}


.streak-text {

    margin-top: 15px;

    color:
        var(--muted);

    font-size: 12px;
}


/* =========================================================
   EVENTS
========================================================= */

.events-card {
    min-height: 167px;
}


.event-list {

    padding:
        5px 22px 18px;

    display:
        flex;

    flex-direction:
        column;

    gap: 10px;
}


.event {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 10px;

    border-radius: 13px;

    background:
        rgba(255,255,255,.035);
}


.event-date {

    width: 38px;
    height: 38px;

    flex-shrink: 0;

    display: flex;

    flex-direction: column;

    justify-content: center;

    align-items: center;

    border-radius: 10px;

    background:
        rgba(53,212,119,.14);
}


.event-date strong {
    font-size: 13px;
}

.event-date span {

    font-size: 8px;

    color:
        var(--muted);
}


.event-info {

    display: flex;

    flex-direction: column;

    gap: 3px;

    min-width: 0;
}


.event-info strong {

    font-size: 12px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.event-info span {

    color:
        var(--muted);

    font-size: 10px;
}


/* =========================================================
   LOWER CARDS
========================================================= */

.lower-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 18px;

    margin-top: 18px;
}


.habit-card {

    padding: 20px;

    min-height: 145px;
}


.habit-header {

    font-size: 20px;

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom: 16px;
}


.habit-icon {

    width: 42px;
    height: 42px;

    display: flex;

    justify-content: center;

    align-items: center;

    border-radius: 13px;

    background:
        rgba(53,212,119,.09);

    font-size: 19px;
}


.habit-card h3 {

    font-size: 20px;

    margin-bottom: 4px;
}


.habit-card p {

    color:
        var(--muted);

    font-size: 16px;
}


.habit-percentage {

    color:
        var(--green-light);

    font-size: 13px;

    font-weight: 700;
}


/* =========================================================
   MUSIC
========================================================= */

.music-card {

    margin-top: 18px;

    padding:
        18px 22px;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap: 20px;
}


.music-left {

    display: flex;

    align-items: center;

    gap: 13px;
}


.music-cover {

    width: 46px;
    height: 46px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #12a85b,
            #063d29
        );

    font-size: 20px;
}


.music-info {

    font-size: 20px;

    display: flex;

    flex-direction: column;

    gap: 3px;
}


.music-info strong {
    font-size: 23px;
}

.music-info span {

    color:
        var(--muted);

    font-size: 11px;
}


.music-controls {

    display: flex;

    align-items: center;

    gap: 8px;
}


.music-button {

    width: 37px;
    height: 37px;

    display: flex;

    align-items: center;
    justify-content: center;

    border: none;

    border-radius: 50%;

    color:
        var(--text);

    background:
        rgba(255,255,255,.06);

    cursor: pointer;

    transition: .2s;
}


.music-button:hover {

    background:
        rgba(53,212,119,.16);

    transform:
        scale(1.05);
}


.music-button.play {

    background:
        var(--green-light);

    color:
        #062016;
}


/* =========================================================
   RESPONSIVE ORIGINAL
========================================================= */

@media (max-width:1100px) {

    .stats {
        grid-template-columns:
            repeat(2,1fr);
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .lower-grid {
        grid-template-columns:
            repeat(3,1fr);
    }
}


@media (max-width:760px) {

    .sidebar {

        top: auto;
        left: 10px;
        right: 10px;
        bottom: 10px;

        width: auto;

        height: 70px;

        min-height: 0;

        flex-direction: row;

        padding:
            8px 12px;

        border-radius: 22px;
    }


    .logo {
        display: none;
    }


    .navigation {

        width: 100%;

        flex-direction: row;

        justify-content:
            space-around;

        gap: 3px;
    }


    .nav-item {

        width: 45px;
        height: 45px;
    }


    .nav-item.active::before {
        display: none;
    }


    .tooltip {
        display: none;
    }


    .logout {
        display: none;
    }


    .main {

        width: 100%;

        margin-left: 0;

        padding:
            22px 15px 100px;
    }


    .topbar {
        align-items:
            flex-start;
    }


    .profile-info {
        display: none;
    }


    .stats {

        grid-template-columns:
            1fr 1fr;

        gap: 10px;
    }


    .stat-card {

        padding: 17px;

        min-height: 130px;
    }


    .stat-number {
        font-size: 25px;
    }


    .lower-grid {
        grid-template-columns: 1fr;
    }


    .chart {
        gap: 8px;
    }


    .bar {
        width: 70%;
    }
}


@media (max-width:450px) {

    .stats {
        grid-template-columns: 1fr;
    }


    .welcome h1 {
        font-size: 34px;
    }


    .profile {
        display: none;
    }
}


/* =========================================================
   MODAL CERRAR SESIÓN
========================================================= */

.logout-modal {

    position: fixed;

    inset: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

    background:
        rgba(0,0,0,.58);

    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);

    opacity: 0;

    visibility: hidden;

    pointer-events: none;

    transition: .25s ease;

    z-index: 100;
}

.logout-modal.show {

    opacity: 1;

    visibility: visible;

    pointer-events: auto;
}

.logout-box {

    width: min(390px, 100%);

    padding: 27px;

    text-align: center;

    border-radius: 23px;

    background:
        linear-gradient(
            145deg,
            rgba(5,45,28,.96),
            rgba(2,31,20,.96)
        );

    border:
        1px solid var(--border);

    box-shadow:
        0 25px 70px
        rgba(0,0,0,.50),

        0 0 30px
        rgba(37,217,111,.08);

    transform:
        translateY(10px)
        scale(.97);

    transition: .25s ease;
}

.logout-modal.show .logout-box {

    transform:
        translateY(0)
        scale(1);
}

.logout-icon {

    width: 58px;
    height: 58px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin:
        0 auto 15px;

    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            rgba(34,206,103,.25),
            rgba(5,112,61,.25)
        );

    border:
        1px solid
        rgba(155,225,93,.20);

    color:
        var(--green-light);

    font-size: 25px;
}

.logout-box h3 {

    color: var(--white);

    font-size: 20px;

    margin-bottom: 8px;
}

.logout-box p {

    color: var(--muted);

    font-size: 12px;

    line-height: 1.6;

    margin-bottom: 21px;
}

.logout-actions {

    display: flex;

    gap: 10px;
}

.logout-actions button {

    flex: 1;

    height: 44px;

    border-radius: 13px;

    font-family: inherit;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;

    transition: .2s ease;
}

.logout-cancel {

    border:
        1px solid
        rgba(129,235,153,.15);

    background:
        rgba(255,255,255,.035);

    color:
        var(--muted);
}

.logout-cancel:hover {

    color: white;

    background:
        rgba(255,255,255,.07);
}

.logout-confirm {

    border: none;

    background:
        linear-gradient(
            145deg,
            #1edb75,
            #087642
        );

    color: white;

    box-shadow:
        0 5px 18px
        rgba(37,217,111,.12);
}

.logout-confirm:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 8px 25px
        rgba(37,217,111,.22);
}


/* =========================================================
   PEAK INDEX - MODO EMBEBIDO
   SOLO SE APLICA CUANDO index.php CARGA ESTA PÁGINA
========================================================= */

body.embedded {

    min-height: 100%;

    height: 100%;

    overflow-x: hidden;

    overflow-y: auto;

    /*
       Espacio inferior para que el reproductor
       flotante del index.php no tape el contenido.
    */
    padding-bottom: 150px;
}


/* OCULTAR SIDEBAR SOLAMENTE EN INDEX */

body.embedded .sidebar {
    display: none;
}


/* MAIN OCUPA TODO EL IFRAME */

body.embedded .main {

    width: 100%;

    margin-left: 0;

    padding:
        24px 24px 170px 24px;

    min-height: 100%;

    overflow-x: hidden;
}


/* VIDEO */

body.embedded #videobackground {
    position: fixed;
}

body.embedded .video-overlay {
    position: fixed;
}


/* CONTENEDOR */

body.embedded .container {

    width: 100%;

    max-width: 1450px;

    margin: 0 auto;
}


/* STATS */

body.embedded .stats {

    width: 100%;

    gap: 12px;
}


/* GRID PRINCIPAL */

body.embedded .dashboard-grid {

    width: 100%;

    grid-template-columns:
        minmax(0,1.65fr)
        minmax(240px,.85fr);

    gap: 14px;
}


/* CHART */

body.embedded .chart {

    gap: 12px;

    padding-left: 18px;

    padding-right: 18px;
}


/* LOWER GRID */

body.embedded .lower-grid {

    gap: 14px;
}


/* MUSIC */

body.embedded .music-card {

    margin-bottom: 20px;
}


/* =========================================================
   EMBEDDED - 1100px
========================================================= */

@media (max-width:1100px) {

    body.embedded .dashboard-grid {

        grid-template-columns: 1fr;

        gap: 14px;
    }

    body.embedded .side-column {

        display: grid;

        grid-template-columns:
            repeat(2,minmax(0,1fr));

        gap: 14px;
    }

    body.embedded .stats {

        grid-template-columns:
            repeat(4,minmax(0,1fr));
    }
}


/* =========================================================
   EMBEDDED - 850px
========================================================= */

@media (max-width:850px) {

    body.embedded .main {

        width: 100%;

        margin-left: 0;

        padding:
            20px 16px 175px 16px;
    }


    body.embedded .topbar {

        align-items:
            flex-start;
    }


    body.embedded .welcome h1 {

        font-size: 36px;
    }


    body.embedded .welcome p {

        font-size: 12px;
    }


    body.embedded .profile-info {

        display: none;
    }


    body.embedded .stats {

        grid-template-columns:
            repeat(2,minmax(0,1fr));

        gap: 10px;
    }


    body.embedded .stat-card {

        padding: 16px;

        min-height: 125px;
    }


    body.embedded .stat-label {

        font-size: 14px;
    }


    body.embedded .stat-number {

        font-size: 25px;
    }


    body.embedded .dashboard-grid {

        grid-template-columns: 1fr;
    }


    body.embedded .side-column {

        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }


    body.embedded .lower-grid {

        grid-template-columns:
            repeat(3,minmax(0,1fr));

        gap: 10px;
    }


    body.embedded .habit-card {

        padding: 15px;
    }


    body.embedded .habit-card h3 {

        font-size: 16px;
    }


    body.embedded .habit-card p {

        font-size: 11px;
    }


    body.embedded .chart {

        height: 230px;

        gap: 8px;
    }


    body.embedded .bar-area {

        height: 165px;
    }


    body.embedded .music-info strong {

        font-size: 18px;
    }
}


/* =========================================================
   EMBEDDED - 650px
========================================================= */

@media (max-width:650px) {

    body.embedded {

        padding-bottom: 170px;
    }


    body.embedded .main {

        padding:
            17px 10px 185px 10px;
    }


    body.embedded .topbar {

        margin-bottom: 18px;
    }


    body.embedded .welcome h1 {

        font-size: 29px;

        letter-spacing: -1px;
    }


    body.embedded .welcome p {

        font-size: 10px;
    }


    body.embedded .stats {

        grid-template-columns:
            repeat(2,minmax(0,1fr));

        gap: 7px;
    }


    body.embedded .stat-card {

        padding: 12px;

        min-height: 105px;

        border-radius: 17px;
    }


    body.embedded .stat-top {

        margin-bottom: 8px;
    }


    body.embedded .stat-icon {

        width: 30px;
        height: 30px;

        font-size: 14px;
    }


    body.embedded .stat-label {

        font-size: 10px;
    }


    body.embedded .stat-number {

        font-size: 20px;
    }


    body.embedded .stat-number small {

        font-size: 9px;
    }


    body.embedded .dashboard-grid {

        gap: 10px;
    }


    body.embedded .card-header {

        padding:
            16px 15px 10px;
    }


    body.embedded .card-title h2 {

        font-size: 19px;
    }


    body.embedded .card-title p {

        font-size: 10px;
    }


    body.embedded .chart {

        height: 190px;

        padding:
            20px 12px 15px;

        gap: 5px;
    }


    body.embedded .bar-area {

        height: 135px;
    }


    body.embedded .bar-value {

        font-size: 8px;
    }


    body.embedded .day {

        font-size: 8px;
    }


    body.embedded .side-column {

        grid-template-columns: 1fr;

        gap: 10px;
    }


    body.embedded .streak-card {

        min-height: 135px;

        padding: 18px;
    }


    body.embedded .streak-number {

        font-size: 38px;
    }


    body.embedded .streak-flame {

        font-size: 40px;
    }


    body.embedded .events-card {

        min-height: 140px;
    }


    body.embedded .lower-grid {

        grid-template-columns: 1fr;

        gap: 10px;
    }


    body.embedded .habit-card {

        min-height: 115px;

        padding: 15px;
    }


    body.embedded .music-card {

        padding:
            14px;

        gap: 10px;
    }


    body.embedded .music-info span {

        font-size: 8px;
    }


    body.embedded .music-info strong {

        font-size: 15px;
    }


    body.embedded .music-cover {

        width: 38px;
        height: 38px;
    }
}


/* =========================================================
   EMBEDDED - 450px
========================================================= */

@media (max-width:450px) {

    body.embedded .main {

        padding-left: 6px;

        padding-right: 6px;

        padding-bottom: 190px;
    }


    body.embedded .welcome h1 {

        font-size: 25px;
    }


    body.embedded .stats {

        grid-template-columns:
            1fr 1fr;

        gap: 5px;
    }


    body.embedded .stat-card {

        padding: 10px 8px;

        border-radius: 14px;
    }


    body.embedded .stat-number {

        font-size: 18px;
    }


    body.embedded .stat-label {

        font-size: 8px;
    }


    body.embedded .stat-icon {

        display: none;
    }


    body.embedded .chart {

        height: 175px;

        padding:
            17px 7px 12px;

        gap: 3px;
    }


    body.embedded .bar-area {

        height: 120px;
    }


    body.embedded .card-title h2 {

        font-size: 17px;
    }


    body.embedded .view-all {

        font-size: 9px;
    }


    body.embedded .music-card {

        flex-direction: column;

        align-items:
            stretch;
    }


    body.embedded .music-controls {

        justify-content: center;
    }
}

</style>

</head>


<body class="<?= $embedded ? 'embedded' : '' ?>">


<!-- =========================================================
     VIDEO
========================================================= -->

<video
    autoplay
    muted
    loop
    playsinline
    id="videobackground">

    <source
        src="../data/bggg.mp4"
        type="video/mp4">

</video>


<div class="video-overlay"></div>


<!-- =========================================================
     APP
========================================================= -->

<div class="app">


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">

    <div class="logo">

        <img
            src="../data/descarga.png"
            alt="PEAK"
            onerror="
                this.style.display='none';
                this.parentElement.innerHTML='P';
            "
        >

    </div>


    <nav class="navigation">


        <a
            href="dashboard.php"
            class="nav-item active"
            data-peak-page="dashboard.php"
        >

            ⌂

            <span class="tooltip">
                Dashboard
            </span>

        </a>


        <a
            href="calendar.php"
            class="nav-item"
            data-peak-page="calendar.php"
        >

            ◫

            <span class="tooltip">
                Calendario
            </span>

        </a>


        <a
            href="flowtime.php"
            class="nav-item"
            data-peak-page="flowtime.php"
        >

            ◷

            <span class="tooltip">
                Flowtime
            </span>

        </a>


        <a
            href="notes.php"
            class="nav-item"
            data-peak-page="notes.php"
        >

            ▤

            <span class="tooltip">
                Notas
            </span>

        </a>


        <a
            href="music.php"
            class="nav-item"
            data-peak-page="music.php"
        >

            ♪

            <span class="tooltip">
                Música
            </span>

        </a>


        <a
            href="IA_HUB.php"
            class="nav-item"
            data-peak-page="IA_HUB.php"
        >

            ◉

            <span class="tooltip">
                IA
            </span>

        </a>

    </nav>


    <div>

        <a
            href="#"
            class="nav-item logout"
            id="logoutButton"
        >

            ↪

            <span class="tooltip">
                Cerrar sesión
            </span>

        </a>

    </div>

</aside>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">

<div class="container">


<!-- =========================================================
     HEADER
========================================================= -->

<header class="topbar">

    <div class="welcome">

        <h1>

            ¡Bienvenido,

            <span>

                <?php

                echo htmlspecialchars(
                    $nombreUsuario,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>

            </span>!!

        </h1>


        <p>
            Mantén el ritmo. Hoy también cuenta.
        </p>

    </div>


    <div class="profile">

        <div class="avatar">

            <?php

            echo htmlspecialchars(
                $inicialUsuario,
                ENT_QUOTES,
                "UTF-8"
            );

            ?>

        </div>


        <div class="profile-info">

            <strong>

                <?php

                echo htmlspecialchars(
                    $nombreUsuario,
                    ENT_QUOTES,
                    "UTF-8"
                );

                ?>

            </strong>


            <span>
    Nivel <?php echo $nivel; ?> · En progreso
</span>

<div
    style="
        width:170px;
        margin-top:6px;
    "
>

    <div
        style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:4px;
            font-size:8px;
            color:var(--muted);
        "
    >

        <span>
            XP
        </span>

        <span>
            <?php
            echo number_format(
                $porcentajeXP,
                2
            );
            ?>%
        </span>

    </div>


    <div
        style="
            width:100%;
            height:5px;
            border-radius:20px;
            overflow:hidden;
            background:rgba(255,255,255,.07);
        "
    >

        <div
            style="
                height:100%;
                width:<?php echo $porcentajeXP; ?>%;
                border-radius:inherit;
                background:
                    linear-gradient(
                        90deg,
                        #087f46,
                        #35d477,
                        #9be15d
                    );
                transition:width .5s ease;
            "
        ></div>

    </div>

</div>

        </div>

    </div>

</header>


<!-- =========================================================
     STATS
========================================================= -->

<section class="stats">


<!-- ESTUDIO -->

<article class="stat-card">

    <div class="stat-top">

        <div>

            <div class="stat-label">
                ESTUDIO
            </div>


            <div class="stat-number">

                <?php

                echo number_format(
                    $estudioHoras,
                    1
                );

                ?>

                <small>
                    h hoy
                </small>

            </div>

        </div>


        <div class="stat-icon">
            ◈
        </div>

    </div>


    <div class="progress">

        <div
            class="progress-bar"
            style="
                width:
                <?php echo $porcentajeEstudio; ?>%
            "
        ></div>

    </div>

</article>


<!-- EJERCICIO -->

<article class="stat-card">

    <div class="stat-top">

        <div>

            <div class="stat-label">
                EJERCICIO
            </div>


            <div class="stat-number">

                <?php
                echo $ejercicioHoy;
                ?>

                <small>
                    min
                </small>

            </div>

        </div>


        <div class="stat-icon">
            ♧
        </div>

    </div>


    <div class="progress">

        <div
            class="progress-bar"
            style="
                width:
                <?php echo $porcentajeEjercicio; ?>%
            "
        ></div>

    </div>

</article>


<!-- AGUA -->

<article class="stat-card">

    <div class="stat-top">

        <div>

            <div class="stat-label">
                HIDRATACIÓN
            </div>


            <div class="stat-number">

                <?php
                echo $aguaHoy;
                ?>

                <small>
                    / <?php echo $objetivoAgua; ?> vasos
                </small>

            </div>

        </div>


        <div class="stat-icon">
            ◇
        </div>

    </div>


    <div class="progress">

        <div
            class="progress-bar"
            style="
                width:
                <?php echo $porcentajeAgua; ?>%
            "
        ></div>

    </div>

</article>


<!-- RACHA -->

<article class="stat-card">

    <div class="stat-top">

        <div>

            <div class="stat-label">
                RACHA
            </div>


            <div class="stat-number">

                <?php echo $racha; ?>

                <small>
                    días
                </small>

            </div>

        </div>


        <div class="stat-icon">
            ✦
        </div>

    </div>


    <div class="progress">

        <div
            class="progress-bar"
            style="
                width:
                <?php echo $porcentajeRacha; ?>%
            "
        ></div>

    </div>

</article>


</section>


<!-- =========================================================
     MAIN GRID
========================================================= -->

<section class="dashboard-grid">


<!-- =====================================================
     CHART
====================================================== -->

<article class="card chart-card">

    <div class="card-header">

        <div class="card-title">

            <h2>
                Actividad semanal
            </h2>

            <p>
                Tu actividad durante los últimos 7 días
            </p>

        </div>


        <span class="view-all">
            Esta semana
        </span>

    </div>


    <div class="chart">

        <?php foreach ($semana as $index => $dia): ?>

            <?php

            $altura = 0;

            if ($maxMinutos > 0) {

                $altura =
                    ($dia["minutos"] /
                    $maxMinutos) * 100;
            }

            $horas =
                $dia["minutos"] / 60;

            ?>

            <div class="bar-column">

                <span class="bar-value">

                    <?php

                    echo number_format(
                        $horas,
                        1
                    );

                    ?>h

                </span>


                <div class="bar-area">

                    <div
                        class="bar
                        <?php
                        echo $index === 6
                            ? "active"
                            : "";
                        ?>"
                        style="
                            height:
                            <?php
                            echo max(
                                $altura,
                                2
                            );
                            ?>%;
                        "
                    ></div>

                </div>


                <span class="day">

                    <?php

                    $diasEspanol = [

                        "MON" => "LUN",
                        "TUE" => "MAR",
                        "WED" => "MIÉ",
                        "THU" => "JUE",
                        "FRI" => "VIE",
                        "SAT" => "SÁB",
                        "SUN" => "DOM"

                    ];

                    echo
                        $diasEspanol[
                            $dia["dia"]
                        ]
                        ??
                        $dia["dia"];

                    ?>

                </span>

            </div>

        <?php endforeach; ?>

    </div>

</article>


<!-- =====================================================
     RIGHT
====================================================== -->

<div class="side-column">


<!-- RACHA -->

<article class="card streak-card">

    <div class="streak-content">

        <div>

            <div class="stat-label">
                RACHA ACTUAL
            </div>


            <div class="streak-number">

                <?php echo $racha; ?>

                <span>
                    días
                </span>

            </div>

        </div>


        <div class="streak-flame">
            ✦
        </div>

    </div>


    <p class="streak-text">

        <?php if ($racha > 0): ?>

            ¡Sigue así!
            Estás construyendo
            una buena rutina.

        <?php else: ?>

            Aún no tienes una racha.
            Agrega una actividad para comenzar.

        <?php endif; ?>

    </p>

</article>


<!-- EVENTOS -->

<article class="card events-card">

    <div class="card-header">

        <div class="card-title">

            <h2>
                Próximamente
            </h2>

            <p>
                Tus próximos eventos
            </p>

        </div>


        <a
            href="calendar.php<?= $embedded ? '?embedded=1' : '' ?>"
            class="view-all"
            data-peak-page="calendar.php"
        >

            Ver todo

        </a>

    </div>


    <div class="event-list">


        <?php if (
            count($proximosEventos) > 0
        ): ?>


            <?php foreach (
                $proximosEventos
                as $evento
            ): ?>

                <?php

                $fechaEvento =
                    new DateTime(
                        $evento["fecha"]
                    );

                $meses = [

                    "01" => "ENE",
                    "02" => "FEB",
                    "03" => "MAR",
                    "04" => "ABR",
                    "05" => "MAY",
                    "06" => "JUN",
                    "07" => "JUL",
                    "08" => "AGO",
                    "09" => "SEP",
                    "10" => "OCT",
                    "11" => "NOV",
                    "12" => "DIC"

                ];

                $numeroMes =
                    $fechaEvento->format("m");

                $diaEvento =
                    $fechaEvento->format("d");

                $mesEvento =
                    $meses[$numeroMes]
                    ?? $numeroMes;

                $horaEvento =
                    $evento["hora_inicio"];

                $horaMostrar = "";

                if (!empty($horaEvento)) {

                    $horaMostrar =
                        date(
                            "g:i A",
                            strtotime(
                                $horaEvento
                            )
                        );
                }

                $duracion =
                    calcularMinutos(
                        $evento["hora_inicio"],
                        $evento["hora_fin"]
                    );

                ?>


                <div class="event">

                    <div class="event-date">

                        <strong>

                            <?php
                            echo $diaEvento;
                            ?>

                        </strong>


                        <span>

                            <?php
                            echo $mesEvento;
                            ?>

                        </span>

                    </div>


                    <div class="event-info">

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $evento["titulo"],
                                ENT_QUOTES,
                                "UTF-8"
                            );

                            ?>

                        </strong>


                        <span>

                            <?php

                            if (
                                $horaMostrar !== ""
                            ) {

                                echo $horaMostrar;

                            } else {

                                echo "Sin hora";
                            }


                            if ($duracion > 0) {

                                echo
                                    " · " .
                                    $duracion .
                                    " min";
                            }

                            ?>

                        </span>

                    </div>

                </div>

            <?php endforeach; ?>


        <?php else: ?>


            <div class="event">

                <div class="event-date">
                    +
                </div>


                <div class="event-info">

                    <strong>
                        Sin próximos eventos
                    </strong>

                    <span>
                        Agrega uno desde Calendario
                    </span>

                </div>

            </div>


        <?php endif; ?>


    </div>

</article>


</div>

</section>


<!-- =========================================================
     HABITS
========================================================= -->

<section class="lower-grid">


<!-- ESTUDIO -->

<article class="card habit-card">

    <div class="habit-header">

        <div>

            <h3>
                Tiempo de estudio
            </h3>

            <p>
                Meta diaria:
                <?php echo $objetivoEstudio; ?>h
            </p>

        </div>


        <div class="habit-icon">
            ◈
        </div>

    </div>


    <div class="habit-percentage">

        <?php

        echo round(
            $porcentajeEstudio
        );

        ?>%

    </div>


    <div class="progress">

        <div
            class="progress-bar"
            style="
                width:
                <?php
                echo $porcentajeEstudio;
                ?>%
            "
        ></div>

    </div>



    <div
    style="
        margin-top:12px;
    "
>

    <div
        style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:5px;
            font-size:9px;
            color:var(--muted);
        "
    >

        <span>
            Nivel de estudio <?php echo $estudioNivel; ?>
        </span>

        <span>
            <?php
            echo round(
                $porcentajeEstudioXP
            );
            ?>%
        </span>

    </div>


    <div
        style="
            width:100%;
            height:5px;
            background:rgba(255,255,255,.07);
            border-radius:20px;
            overflow:hidden;
        "
    >

        <div
            style="
                width:<?php echo $porcentajeEstudioXP; ?>%;
                height:100%;
                border-radius:20px;
                background:
                    linear-gradient(
                        90deg,
                        #087f46,
                        #35d477,
                        #9be15d
                    );
                transition:width .5s ease;
            "
        ></div>

    </div>

</div>



</article>














<!-- EJERCICIO -->

<article class="card habit-card">

    <div class="habit-header">

        <div>

            <h3>
                Movimiento
            </h3>

            <p>
                Meta diaria:
                <?php echo $objetivoEjercicio; ?> min
            </p>

        </div>


        <div class="habit-icon">
            ♧
        </div>
        <div
    id="exerciseStatus"
    style="
        margin-top:14px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
    "
>

    <span
        id="exerciseText"
        style="
            font-size:11px;
            color:var(--muted);
        "
    >
        <?php
        echo $ejercicioMarcado
            ? "Ejercicio realizado hoy"
            : "¿Hiciste ejercicio hoy?";
        ?>
    </span>


    <button
        type="button"
        id="exerciseButton"
        style="
            border:1px solid var(--border);
            background:
                <?php
                echo $ejercicioMarcado
                    ? "rgba(53,212,119,.20)"
                    : "rgba(255,255,255,.05)";
                ?>;
            color:var(--text);
            border-radius:10px;
            padding:8px 12px;
            font-family:inherit;
            font-size:10px;
            cursor:pointer;
        "
    >

        <?php
        echo $ejercicioMarcado
            ? "✓ Hecho"
            : "Marcar";
        ?>

    </button>


    <div
    style="
        margin-top:12px;
    "
>

    <div
        style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:5px;
            font-size:9px;
            color:var(--muted);
        "
    >

        <span>
            Nivel de ejercicio <?php echo $ejercicioNivel; ?>
        </span>

        <span>
            <?php
            echo $ejercicioProgreso;
            ?>%
        </span>

    </div>


    <div
        style="
            width:100%;
            height:5px;
            background:rgba(255,255,255,.07);
            border-radius:20px;
            overflow:hidden;
        "
    >

        <div
            style="
                width:<?php echo $ejercicioProgreso; ?>%;
                height:100%;
                border-radius:20px;
                background:
                    linear-gradient(
                        90deg,
                        #087f46,
                        #35d477,
                        #9be15d
                    );
                transition:width .5s ease;
            "
        ></div>

    </div>

</div>

</div>

    </div>


    <div class="habit-percentage">

        <?php

        echo round(
            $porcentajeEjercicio
        );

        ?>%

    </div>


    <div class="progress">

        <div
            class="progress-bar"
            style="
                width:
                <?php
                echo $porcentajeEjercicio;
                ?>%
            "
        ></div>

    </div>

</article>


<!-- AGUA -->

<article class="card habit-card">

    <div class="habit-header">

        <div>

            <h3>
                Hidratación
            </h3>

            <p>

                <?php echo $aguaHoy; ?>

                de

                <?php echo $objetivoAgua; ?>

                vasos

            </p>

        </div>


        <div class="habit-icon">
            ◇
        </div>

    </div>


    <div class="habit-percentage">

        <?php

        echo round(
            $porcentajeAgua
        );

        ?>%

    </div>


    <div class="progress">

        <div
            class="progress-bar"
            style="
                width:
                <?php
                echo $porcentajeAgua;
                ?>%
            "
        ></div>




    </div>

    <div
    style="
        margin-top:14px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
    "
>

    <div>

        <strong
            id="waterCounter"
            style="
                font-size:18px;
                color:var(--white);
            "
        >
            <?php echo $aguaHoy; ?>
        </strong>

        <span
            style="
                color:var(--muted);
                font-size:10px;
            "
        >
            vasos hoy
        </span>

    </div>


    <div
        style="
            display:flex;
            gap:6px;
        "
    >

        <button
            type="button"
            id="removeWaterButton"
            style="
                width:32px;
                height:32px;
                border:1px solid var(--border);
                border-radius:10px;
                background:rgba(255,255,255,.05);
                color:var(--text);
                cursor:pointer;
                font-family:inherit;
            "
        >
            −
        </button>


        <button
            type="button"
            id="addWaterButton"
            style="
                height:32px;
                border:none;
                border-radius:10px;
                padding:0 12px;
                background:
                    linear-gradient(
                        145deg,
                        #1edb75,
                        #087642
                    );
                color:white;
                cursor:pointer;
                font-family:inherit;
                font-size:10px;
            "
        >
            + 1 vaso
        </button>

    </div>

</div>

</article>


</section>






</div>

</main>

</div>


<!-- =========================================================
     LOGOUT MODAL
========================================================= -->

<div
    class="logout-modal"
    id="logoutModal"
>

    <div class="logout-box">

        <div class="logout-icon">
            ↪
        </div>


        <h3>
            ¿Cerrar sesión?
        </h3>


        <p>
            ¿Estás seguro de que quieres
            cerrar tu sesión de PEAK?
        </p>


        <div class="logout-actions">

            <button
                type="button"
                class="logout-cancel"
                id="cancelLogout"
            >
                Cancelar
            </button>


            <button
                type="button"
                class="logout-confirm"
                id="confirmLogout"
            >
                Sí, cerrar sesión
            </button>

        </div>

    </div>

</div>


<script>

/* =========================================================
   MÚSICA
========================================================= */

let playing = false;


function toggleMusic() {

    playing = !playing;

    const button =
        document.getElementById(
            "playButton"
        );


    if (playing) {

        button.innerHTML = "Ⅱ";

    } else {

        button.innerHTML = "▶";
    }
}


function previousSong() {

    /*
       El reproductor real está en index.php.
       Este botón se comunica con el padre.
    */

    if (
        window.parent &&
        window.parent !== window
    ) {

        window.parent.postMessage(
            {
                type:
                    "PEAK_PREVIOUS_SONG"
            },
            "*"
        );
    }

}


function nextSong() {

    /*
       El reproductor real está en index.php.
    */

    if (
        window.parent &&
        window.parent !== window
    ) {

        window.parent.postMessage(
            {
                type:
                    "PEAK_NEXT_SONG"
            },
            "*"
        );
    }

}


/* =========================================================
   ANIMACIÓN
========================================================= */

const cards =
    document.querySelectorAll(
        ".stat-card, .card"
    );


cards.forEach(
    (card, index) => {

        card.style.opacity = "0";

        card.style.transform =
            "translateY(12px)";


        setTimeout(
            () => {

                card.style.transition =
                    "opacity .5s ease, transform .5s ease";

                card.style.opacity =
                    "1";

                card.style.transform =
                    "translateY(0)";

            },
            80 + (index * 60)
        );

    }
);


/* =========================================================
   NAVEGACIÓN DEL INDEX
========================================================= */

document
    .querySelectorAll(
        "[data-peak-page]"
    )
    .forEach(
        function(link) {

            link.addEventListener(
                "click",
                function(event) {

                    /*
                       Si estamos dentro de index.php,
                       no dejamos que el iframe navegue.
                    */

                    if (
                        window.parent &&
                        window.parent !== window &&
                        document.body.classList.contains(
                            "embedded"
                        )
                    ) {

                        event.preventDefault();


                        window.parent.postMessage(
                            {
                                type:
                                    "PEAK_NAVIGATE",

                                page:
                                    this.dataset.peakPage
                            },
                            "*"
                        );
                    }

                }
            );

        }
    );


/* =========================================================
   CERRAR SESIÓN
========================================================= */

const logoutButton =
    document.getElementById(
        "logoutButton"
    );

const logoutModal =
    document.getElementById(
        "logoutModal"
    );

const cancelLogout =
    document.getElementById(
        "cancelLogout"
    );

const confirmLogout =
    document.getElementById(
        "confirmLogout"
    );


/* ABRIR */

logoutButton.addEventListener(
    "click",
    function(event) {

        event.preventDefault();

        logoutModal.classList.add(
            "show"
        );
    }
);


/* CANCELAR */

cancelLogout.addEventListener(
    "click",
    function() {

        logoutModal.classList.remove(
            "show"
        );
    }
);


/* CLICK FUERA */

logoutModal.addEventListener(
    "click",
    function(event) {

        if (
            event.target ===
            logoutModal
        ) {

            logoutModal.classList.remove(
                "show"
            );
        }
    }
);


/* =========================================================
   CONFIRMAR LOGOUT
========================================================= */

confirmLogout.addEventListener(
    "click",
    async function() {

        confirmLogout.disabled = true;

        confirmLogout.textContent =
            "Cerrando...";


        try {

            const formData =
                new FormData();


            formData.append(
                "logout",
                "1"
            );


            /*
               IMPORTANTE:
               El POST ahora se hace a
               dashboard.php, que es donde
               está el handler del logout.
            */

            const response =
                await fetch(
                    "dashboard.php<?= $embedded ? '?embedded=1' : '' ?>",
                    {
                        method: "POST",
                        body: formData
                    }
                );


            const data =
                await response.json();


            if (data.success) {

                /*
                   Si estamos dentro de index.php,
                   hacemos que el padre vaya al login.
                */

                if (
                    window.parent &&
                    window.parent !== window &&
                    document.body.classList.contains(
                        "embedded"
                    )
                ) {

                    window.parent.location.href =
                        "pages/login.php";

                } else {

                    window.location.href =
                        "login.php";
                }

                return;
            }


            confirmLogout.disabled =
                false;

            confirmLogout.textContent =
                "Sí, cerrar sesión";


        } catch (error) {

            console.error(error);

            confirmLogout.disabled =
                false;

            confirmLogout.textContent =
                "Sí, cerrar sesión";
        }

    }
);




/* =========================================================
   HÁBITOS DIARIOS
========================================================= */

const dashboardURL =
    "dashboard.php<?= $embedded ? '?embedded=1' : '' ?>";


async function dashboardAction(action, extra = {}) {

    const formData =
        new FormData();

    formData.append(
        "dashboard_action",
        action
    );


    Object.keys(extra).forEach(
        function(key) {

            formData.append(
                key,
                extra[key]
            );

        }
    );


    const response =
        await fetch(
            dashboardURL,
            {
                method: "POST",
                body: formData
            }
        );


    return await response.json();
}


/* =========================================================
   EJERCICIO
========================================================= */

const exerciseButton =
    document.getElementById(
        "exerciseButton"
    );

const exerciseText =
    document.getElementById(
        "exerciseText"
    );


if (exerciseButton) {

    exerciseButton.addEventListener(
        "click",
        async function() {

            exerciseButton.disabled =
                true;


            try {

                const data =
                    await dashboardAction(
                        "toggle_exercise"
                    );


                if (data.success) {

                    if (
                        Number(
                            data.exercise_done
                        ) === 1
                    ) {

                        exerciseButton.textContent =
                            "✓ Hecho";

                        exerciseText.textContent =
                            "Ejercicio realizado hoy";

                        exerciseButton.style.background =
                            "rgba(53,212,119,.20)";

                    } else {

                        exerciseButton.textContent =
                            "Marcar";

                        exerciseText.textContent =
                            "¿Hiciste ejercicio hoy?";

                        exerciseButton.style.background =
                            "rgba(255,255,255,.05)";
                    }

                    /*
                       Recargamos para actualizar
                       las barras y estadísticas PHP.
                    */

                    setTimeout(
                        function() {
                            location.reload();
                        },
                        250
                    );
                }


            } catch (error) {

                console.error(
                    "Error ejercicio:",
                    error
                );

                exerciseButton.disabled =
                    false;
            }

        }
    );
}


/* =========================================================
   AGUA
========================================================= */

const addWaterButton =
    document.getElementById(
        "addWaterButton"
    );

const removeWaterButton =
    document.getElementById(
        "removeWaterButton"
    );

const waterCounter =
    document.getElementById(
        "waterCounter"
    );


if (addWaterButton) {

    addWaterButton.addEventListener(
        "click",
        async function() {

            addWaterButton.disabled =
                true;


            try {

                const data =
                    await dashboardAction(
                        "add_water"
                    );


                if (data.success) {

                    waterCounter.textContent =
                        data.water_glasses;


                    /*
                       La barra superior y la
                       barra inferior también
                       deben actualizarse.
                    */

                    setTimeout(
                        function() {
                            location.reload();
                        },
                        250
                    );
                }


            } catch (error) {

                console.error(
                    "Error agua:",
                    error
                );

                addWaterButton.disabled =
                    false;
            }

        }
    );
}


if (removeWaterButton) {

    removeWaterButton.addEventListener(
        "click",
        async function() {

            removeWaterButton.disabled =
                true;


            try {

                const data =
                    await dashboardAction(
                        "remove_water"
                    );


                if (data.success) {

                    waterCounter.textContent =
                        data.water_glasses;


                    setTimeout(
                        function() {
                            location.reload();
                        },
                        250
                    );
                }


            } catch (error) {

                console.error(
                    "Error quitando agua:",
                    error
                );

                removeWaterButton.disabled =
                    false;
            }

        }
    );
}


/* =========================================================
   REGISTRAR APERTURA DEL DASHBOARD
========================================================= */

let aperturaRegistrada = false;


if (!aperturaRegistrada) {

    aperturaRegistrada = true;


    dashboardAction(
        "register_open"
    )
    .catch(
        function(error) {

            console.error(
                "Error registrando apertura:",
                error
            );

        }
    );
}


/* =========================================================
   TIEMPO ACTIVO
========================================================= */

let dashboardVisible =
    !document.hidden;


document.addEventListener(
    "visibilitychange",
    function() {

        dashboardVisible =
            !document.hidden;

    }
);


/*
   Cada 60 segundos activos se
   registra un minuto.

   No cuenta tiempo cuando la
   pestaña está oculta.
*/

setInterval(
    function() {

        if (!dashboardVisible) {
            return;
        }


        dashboardAction(
            "add_minute"
        )
        .catch(
            function(error) {

                console.error(
                    "Error registrando minuto:",
                    error
                );

            }
        );

    },
    60000
);

</script>


</body>

</html>