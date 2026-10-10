<?php

session_start();

/* =========================================================
   MODO EMBEBIDO
========================================================= */

$embedded =
    isset($_GET["embedded"]) &&
    $_GET["embedded"] === "1";


/* =========================================================
   CERRAR SESIÓN
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

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = intval($_SESSION["usuario_id"]);
$nombreUsuario = $_SESSION["nombre"] ?? "";


/* =========================================================
   MES Y AÑO
========================================================= */

$month = isset($_GET["month"])
    ? intval($_GET["month"])
    : intval(date("n"));

$year = isset($_GET["year"])
    ? intval($_GET["year"])
    : intval(date("Y"));

$view = $_GET["view"] ?? "month";

if ($month < 1) {
    $month = 12;
    $year--;
}

if ($month > 12) {
    $month = 1;
    $year++;
}


/* =========================================================
   MESES
========================================================= */

$months = [
    1 => "Enero",
    2 => "Febrero",
    3 => "Marzo",
    4 => "Abril",
    5 => "Mayo",
    6 => "Junio",
    7 => "Julio",
    8 => "Agosto",
    9 => "Septiembre",
    10 => "Octubre",
    11 => "Noviembre",
    12 => "Diciembre"
];


/* =========================================================
   DÍAS
========================================================= */

$weekdays = [
    "Lunes",
    "Martes",
    "Miércoles",
    "Jueves",
    "Viernes",
    "Sábado",
    "Domingo"
];

$firstDay = intval(
    date("N", strtotime("$year-$month-01"))
);

$daysInMonth = intval(
    date("t", strtotime("$year-$month-01"))
);

$today = date("Y-m-d");

$message = "";
$messageType = "";


/* =========================================================
   ELIMINAR EVENTO
========================================================= */

if (isset($_GET["delete"])) {

    $eventId = intval($_GET["delete"]);

    $stmt = $conn->prepare("
        DELETE FROM calendar_events
        WHERE id = ?
        AND usuario_id = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $eventId,
            $usuario_id
        );

        if ($stmt->execute()) {

            $stmt->close();

            header(
                "Location: calendar.php?month=" .
                $month .
                "&year=" .
                $year .
                ($embedded ? "&embedded=1" : "")
            );

            exit();
        }

        $stmt->close();
    } else {
        $message = "No se pudo preparar la eliminación del evento.";
        $messageType = "error";
    }
}


/* =========================================================
   GUARDAR / EDITAR EVENTO
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $eventId = isset($_POST["event_id"])
        ? intval($_POST["event_id"])
        : 0;

    $titulo = trim(
        $_POST["titulo"] ?? ""
    );

    $descripcion = trim(
        $_POST["descripcion"] ?? ""
    );

    $fecha = trim(
        $_POST["fecha"] ?? ""
    );

    /* La fecha debe guardarse siempre en formato DATE: YYYY-MM-DD. */
    $fechaValida = false;

    if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $fecha, $partes)) {
        $fechaValida = checkdate(
            intval($partes[2]),
            intval($partes[3]),
            intval($partes[1])
        );
    }

    if (!$fechaValida) {
        $fecha = "";
    } else {
        /* Normaliza el valor para que MySQL reciba exactamente una fecha DATE. */
        $fecha = sprintf(
            "%04d-%02d-%02d",
            intval($partes[1]),
            intval($partes[2]),
            intval($partes[3])
        );
    }

    $hora_inicio = trim(
        $_POST["hora_inicio"] ?? ""
    );

    $hora_fin = trim(
        $_POST["hora_fin"] ?? ""
    );

    $tipo = trim(
        $_POST["tipo"] ?? "evento"
    );

    $dia_semana = $_POST["dia_semana"] ?? "";

    if ($dia_semana === "") {
        $dia_semana = null;
    } else {
        $dia_semana = intval($dia_semana);
    }

    if ($titulo === "") {

        $message = "Escribe un título para el evento.";
        $messageType = "error";

    } elseif ($fecha === "") {

        $message = "Selecciona una fecha válida para el evento.";
        $messageType = "error";

    } else {

        /* =================================================
           EDITAR
        ================================================= */

        if ($eventId > 0) {

            $stmt = $conn->prepare("
                UPDATE calendar_events
                SET
                    titulo = ?,
                    descripcion = ?,
                    fecha = ?,
                    hora_inicio = ?,
                    hora_fin = ?,
                    tipo = ?,
                    dia_semana = ?
                WHERE id = ?
                AND usuario_id = ?
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "ssssssiii",
                    $titulo,
                    $descripcion,
                    $fecha,
                    $hora_inicio,
                    $hora_fin,
                    $tipo,
                    $dia_semana,
                    $eventId,
                    $usuario_id
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    header(
                        "Location: calendar.php?month=" .
                        $month .
                        "&year=" .
                        $year .
                        ($embedded ? "&embedded=1" : "")
                    );

                    exit();

                } else {

                    $message =
                        "No se pudo actualizar el evento: " . $stmt->error;

                    $messageType = "error";
                }

                $stmt->close();
            } else {
                $message = "No se pudo preparar la actualización del evento.";
                $messageType = "error";
            }


        /* =================================================
           CREAR
        ================================================= */

        } else {

            $stmt = $conn->prepare("
                INSERT INTO calendar_events
                (
                    usuario_id,
                    titulo,
                    descripcion,
                    fecha,
                    hora_inicio,
                    hora_fin,
                    tipo,
                    dia_semana
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "issssssi",
                    $usuario_id,
                    $titulo,
                    $descripcion,
                    $fecha,
                    $hora_inicio,
                    $hora_fin,
                    $tipo,
                    $dia_semana
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    header(
                        "Location: calendar.php?month=" .
                        $month .
                        "&year=" .
                        $year .
                        ($embedded ? "&embedded=1" : "")
                    );

                    exit();

                } else {

                    $message =
                        "No se pudo guardar el evento: " . $stmt->error;

                    $messageType = "error";
                }

                $stmt->close();
            } else {
                $message = "No se pudo preparar el guardado del evento.";
                $messageType = "error";
            }
        }
    }
}
/* =========================================================
   EVENTOS + RUTINAS AUTOMÁTICAS
========================================================= */

$eventData = [];
$rutinas = [];

/*
 * Guardamos todos los eventos del usuario.
 */
$allEvents = [];

$stmt = $conn->prepare("
    SELECT *
    FROM calendar_events
    WHERE usuario_id = ?
    ORDER BY fecha ASC, hora_inicio ASC, id ASC
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $usuario_id
    );

    if ($stmt->execute()) {

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            /*
             * Normalizar fecha.
             */
            $fechaEvento =
                trim(
                    (string)($row["fecha"] ?? "")
                );

            if (
                $fechaEvento !== "" &&
                substr($fechaEvento, 0, 10) !== "0000-00-00"
            ) {

                $fechaEvento =
                    substr(
                        $fechaEvento,
                        0,
                        10
                    );

                if (
                    preg_match(
                        '/^\d{4}-\d{2}-\d{2}$/',
                        $fechaEvento
                    )
                ) {

                    $row["fecha"] =
                        $fechaEvento;

                    /*
                     * Eventos para el calendario.
                     */
                    $eventData[$fechaEvento][] =
                        $row;

                    /*
                     * Todos los eventos para
                     * calcular las rutinas.
                     */
                    $allEvents[] =
                        $row;
                }
            }
        }
    }

    $stmt->close();
}


/* =========================================================
   CALCULAR RUTINAS AUTOMÁTICAS
========================================================= */

/*
 * Agrupamos las fechas por tipo.
 *
 * Ejemplo:
 *
 * estudio:
 * 2026-10-01
 * 2026-10-02
 * 2026-10-03
 *
 * tarea:
 * 2026-10-05
 * 2026-10-07
 */
$datesByType = [];


foreach ($allEvents as $event) {

    $tipoEvento =
        strtolower(
            trim(
                (string)($event["tipo"] ?? "")
            )
        );

    $fechaEvento =
        trim(
            (string)($event["fecha"] ?? "")
        );


    /*
     * Si no existe tipo, no se procesa.
     */
    if ($tipoEvento === "") {
        continue;
    }

    /*
     * Si no existe fecha válida, no se procesa.
     */
    if (
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $fechaEvento
        )
    ) {
        continue;
    }


    /*
     * Evitar duplicar una misma fecha
     * para el mismo tipo.
     */
    if (
        !isset(
            $datesByType[$tipoEvento]
        )
    ) {

        $datesByType[$tipoEvento] = [];
    }


    $datesByType[$tipoEvento][$fechaEvento] =
        true;
}


/*
 * Ahora buscamos cadenas de días consecutivos.
 */
foreach (
    $datesByType
    as $tipoEvento => $dates
) {

    /*
     * Convertimos las fechas a una lista.
     */
    $fechas =
        array_keys($dates);


    /*
     * Orden cronológico.
     */
    sort($fechas);


    /*
     * Necesitamos mínimo 3 fechas.
     */
    if (count($fechas) < 3) {
        continue;
    }


    $rachaActual = 1;
    $rachaMaxima = 1;


    /*
     * Guardamos las fechas de la
     * cadena consecutiva.
     */
    $inicioRacha = $fechas[0];
    $finRacha = $fechas[0];

    $mejorInicio = $fechas[0];
    $mejorFin = $fechas[0];


    for (
        $i = 1;
        $i < count($fechas);
        $i++
    ) {

        $fechaAnterior =
            new DateTime(
                $fechas[$i - 1]
            );

        $fechaActual =
            new DateTime(
                $fechas[$i]
            );


        /*
         * Diferencia entre ambas fechas.
         */
        $diferencia =
            (int)$fechaAnterior
                ->diff($fechaActual)
                ->days;


        /*
         * Día inmediatamente siguiente.
         */
        if ($diferencia === 1) {

            $rachaActual++;

            $finRacha =
                $fechas[$i];


            /*
             * Si esta cadena es mayor,
             * la guardamos.
             */
            if (
                $rachaActual >
                $rachaMaxima
            ) {

                $rachaMaxima =
                    $rachaActual;

                $mejorInicio =
                    $inicioRacha;

                $mejorFin =
                    $finRacha;
            }

        } else {

            /*
             * Se rompió la cadena.
             */
            $rachaActual = 1;

            $inicioRacha =
                $fechas[$i];

            $finRacha =
                $fechas[$i];
        }
    }


    /*
     * Solo se convierte en rutina
     * cuando existen 3 o más días
     * consecutivos.
     */
    if ($rachaMaxima >= 3) {

        $rutinas[] = [

            "tipo" =>
                $tipoEvento,

            "nombre" =>
                ucfirst(
                    $tipoEvento
                ),

            "dias" =>
                $rachaMaxima,

            "fecha_inicio" =>
                $mejorInicio,

            "fecha_fin" =>
                $mejorFin

        ];
    }
}


/*
 * Ordenar las rutinas por nombre.
 */
usort(
    $rutinas,
    function ($a, $b) {

        return strcmp(
            $a["nombre"],
            $b["nombre"]
        );
    }
);

/* =========================================================
   PRÓXIMOS EVENTOS
========================================================= */

$nextEvents = [];

$stmt = $conn->prepare("
    SELECT *
    FROM calendar_events
    WHERE usuario_id = ?
    AND fecha IS NOT NULL
    AND fecha >= CURDATE()
    ORDER BY fecha, hora_inicio
    LIMIT 8
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $usuario_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $nextEvents[] = $row;
    }

    $stmt->close();
}


/* =========================================================
   RESUMEN
========================================================= */

$totalEventos = 0;

foreach ($eventData as $events) {
    $totalEventos += count($events);
}

$totalRutinas = count($rutinas);


/* =========================================================
   FUNCIONES
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function formatTime($time)
{
    if (!$time) {
        return "";
    }

    return date(
        "g:i A",
        strtotime($time)
    );
}


/* =========================================================
   MESES ANTERIOR / SIGUIENTE
========================================================= */

$previousMonth = $month - 1;
$previousYear = $year;

if ($previousMonth < 1) {
    $previousMonth = 12;
    $previousYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;

if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>𝐂𝐀𝐋𝐄𝐍𝐃𝐀𝐑𝐈𝐎</title>

<link rel="icon" type="image/png" href="..\data\descarga.png">


<style>

/* =========================================================
   BASE
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
    color: #dff8df;
    font-family: VCR OSD Mono;
    background: #041b12;
    overflow-x: hidden;
}

:root {

    --bg: #041b12;
    --bg-dark: #06251a;
    --green-dark: #063d29;
    --green: #087f45;
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
   SIDEBAR
   NO CAMBIAR
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

    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);

    box-shadow: var(--shadow);

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

    font-size: 21px;
    font-weight: 900;

    overflow: hidden;
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

    color: #78aa8b;

    font-size: 22px;

    text-decoration: none;

    transition: .25s ease;

    cursor: pointer;
}

.nav-item:hover {

    color: white;

    background:
        rgba(32,213,162,.13);

    transform: translateY(-1px);

    box-shadow:
        0 0 18px rgba(37,217,111,.12);
}

.nav-item.active {

    color: white;

    background:
        linear-gradient(
            145deg,
            rgba(34,206,103,.75),
            rgba(5,112,61,.75)
        );

    box-shadow:

        inset 0 0 0 1px
        rgba(196,255,203,.16),

        0 8px 25px
        rgba(0,0,0,.25),

        0 0 20px
        rgba(37,217,111,.16);
}

.nav-item.active::before {

    content: "";

    position: absolute;

    left: -13px;

    width: 4px;
    height: 24px;

    border-radius: 10px;

    background: var(--lime);

    box-shadow:
        0 0 10px
        rgba(155,225,93,.6);
}

.tooltip {

    position: absolute;

    left: 70px;
    top: 50%;

    transform:
        translateY(-50%)
        translateX(-5px);

    padding: 8px 11px;

    border-radius: 9px;

    background:
        rgba(3,25,16,.96);

    border:
        1px solid var(--border);

    color: var(--white);

    font-size: 12px;
    font-weight: 600;

    white-space: nowrap;

    opacity: 0;

    pointer-events: none;

    transition: .2s ease;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.35);
}

.nav-item:hover .tooltip {

    opacity: 1;

    transform:
        translateY(-50%)
        translateX(0);
}

.logout {
    margin-top: 8px;
}


/* =========================================================
   MAIN
========================================================= */

.main {

    width: calc(100% - 126px);

    margin-left: 126px;

    padding:
        30px
        40px
        40px
        20px;

    min-height: 100vh;

    overflow-x: hidden;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 26px;
}

.welcome {
    min-width: 0;
}

.welcome h1 {

    font-family: Minecrafter;
    font-weight: lighter;

    color: var(--white);

    font-size: 49px;

    line-height: 1.1;

    margin-bottom: 6px;
}

.welcome p {

    color: var(--muted);

    font-size: 19px;
}

.profile-top {

    display: flex;

    align-items: center;

    gap: 12px;

    flex-shrink: 0;
}

.profile-name {

    color: var(--white);

    font-size: 14px;

    font-weight: 700;

    white-space: nowrap;
}

.profile-circle {

    width: 44px;
    height: 44px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    color: white;

    font-weight: 800;

    background:
        linear-gradient(
            145deg,
            #1edb75,
            #087642
        );

    border:
        1px solid
        rgba(255,255,255,.16);

    box-shadow:
        0 0 20px
        rgba(37,217,111,.18);
}


/* =========================================================
   HEADER
========================================================= */

.page-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 20px;
}

.page-title {

    display: flex;

    align-items: center;

    gap: 14px;

    min-width: 0;
}

.page-title-icon {

    width: 50px;
    height: 50px;

    display: flex;

    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    border-radius: 16px;

    background:
        linear-gradient(
            145deg,
            rgba(30,219,117,.85),
            rgba(8,118,66,.75)
        );

    color: white;

    font-size: 24px;

    box-shadow:
        0 0 24px
        rgba(37,217,111,.18);
}

.page-title h2 {

    color: var(--white);

    font-size: 29px;
}

.page-title p {

    color: var(--muted);

    font-size: 13px;

    margin-top: 4px;
}

.btn-primary {

    border: none;
    outline: none;

    padding: 13px 19px;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #25dc75,
            #07934e
        );

    color: white;

    font-size: 14px;

    font-weight: 800;

    cursor: pointer;

    white-space: nowrap;

    box-shadow:
        0 8px 25px
        rgba(16,190,91,.22);

    transition: .25s ease;
}

.btn-primary:hover {

    transform: translateY(-2px);

    box-shadow:
        0 12px 32px
        rgba(16,190,91,.32);
}


/* =========================================================
   RESUMEN
========================================================= */

.summary-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0,1fr));

    gap: 15px;

    margin-bottom: 20px;

    width: 100%;
}

.summary-card {

    position: relative;

    min-width: 0;

    padding: 18px;

    background:
        linear-gradient(
            145deg,
            rgba(7, 59, 36, 0.51),
            rgba(3, 37, 23, 0.42)
        );

    border:
        1px solid var(--border);

    border-radius: 19px;

    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    box-shadow:
        0 15px 45px
        rgba(0,0,0,.22);

    overflow: hidden;
}

.summary-card::after {

    content: "";

    position: absolute;

    width: 110px;
    height: 110px;

    right: -45px;
    top: -50px;

    border-radius: 50%;

    background:
        rgba(54,218,116,.10);
}

.summary-label {

    font-family: Minecraft;

    color: var(--green-light);

    font-size: 22px;

    margin-bottom: 6px;
}

.summary-number {

    font-family: Minecraft;
    font-weight: lighter;

    color: var(--white);

    font-size: 27px;
}

.summary-small {

    color: var(--muted);

    font-size: 19px;

    margin-top: 4px;
}


/* =========================================================
   LAYOUT
========================================================= */

.calendar-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        minmax(260px, 330px);

    gap: 20px;

    align-items: start;

    width: 100%;

    min-width: 0;
}


/* =========================================================
   CARD
========================================================= */

.glass-card {

    background:
        linear-gradient(
            145deg,
            rgba(5, 45, 28, 0.29),
            rgba(2, 31, 20, 0.3)
        );

    border:
        1px solid var(--border);

    border-radius: 23px;

    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(22px);

    box-shadow: var(--shadow);

    min-width: 0;
}


/* =========================================================
   CALENDARIO
========================================================= */

.calendar-card {

    overflow: hidden;

    min-width: 0;
}


/* =========================================================
   HEADER CALENDARIO
========================================================= */

.calendar-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 20px 22px;

    border-bottom:
        1px solid
        rgba(129,235,153,.12);
}

.month-title {

    color: var(--white);

    font-size: 21px;

    font-weight: 800;
}

.month-subtitle {

    color: var(--muted);

    font-size: 12px;

    margin-top: 4px;
}

.month-actions {

    display: flex;

    gap: 8px;

    flex-shrink: 0;
}

.month-btn {

    width: 38px;
    height: 38px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 11px;

    color: var(--white);

    text-decoration: none;

    background:
        rgba(24,116,70,.32);

    border:
        1px solid
        rgba(129,235,153,.16);

    font-size: 17px;

    transition: .2s ease;
}

.month-btn:hover {

    background:
        rgba(34,206,103,.35);

    transform: translateY(-1px);
}


/* =========================================================
   WEEKDAYS
========================================================= */

.weekdays {

    display: grid;

    grid-template-columns:
        repeat(7, minmax(0,1fr));

    padding:
        14px
        14px
        8px;

    min-width: 0;
}

.weekday {

    text-align: center;

    color: #8fbea0;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    overflow: hidden;

    white-space: nowrap;
}


/* =========================================================
   CALENDAR GRID
========================================================= */

.calendar-grid {

    display: grid;

    grid-template-columns:
        repeat(7, minmax(0,1fr));

    padding:
        0 14px 14px;

    gap: 7px;

    min-width: 0;
}


/* =========================================================
   DAY
========================================================= */

.day {

    min-width: 0;

    min-height: 105px;

    position: relative;

    padding: 9px;

    border-radius: 15px;

    background:
        rgba(5,52,32,.45);

    border:
        1px solid
        rgba(129,235,153,.08);

    transition: .2s ease;

    cursor: pointer;

    overflow: hidden;
}

.day:hover {

    background:
        rgba(19,113,64,.38);

    border-color:
        rgba(129,235,153,.22);

    transform: translateY(-1px);
}

.day.empty {

    opacity: .25;

    cursor: default;
}

.day-number {

    width: 27px;
    height: 27px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 9px;

    color: #b9d9bf;

    font-size: 12px;

    font-weight: 700;

    margin-bottom: 7px;
}

.day.today {

    border-color:
        rgba(155,225,93,.28);

    background:
        linear-gradient(
            145deg,
            rgba(33,122,68,.38),
            rgba(4,49,29,.48)
        );
}

.day.today .day-number {

    color: #02180d;

    background:
        linear-gradient(
            135deg,
            var(--lime),
            var(--green-light)
        );

    box-shadow:
        0 0 14px
        rgba(155,225,93,.28);
}


/* =========================================================
   EVENTOS
========================================================= */

.day-events {

    display: flex;

    flex-direction: column;

    gap: 4px;

    min-width: 0;
}

.event-pill {

    display: block;

    width: 100%;

    min-width: 0;

    padding: 5px 6px;

    border-radius: 7px;

    background:
        linear-gradient(
            90deg,
            rgba(25,191,95,.65),
            rgba(17,128,70,.55)
        );

    border-left:
        3px solid
        var(--lime);

    color: #eaffea;

    font-size: 10px;

    font-weight: 700;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.event-time {

    color: #a8e9b8;

    font-size: 9px;

    margin-right: 3px;
}

.more-events {

    color: var(--lime);

    font-size: 9px;

    font-weight: 700;

    padding-left: 3px;
}


/* =========================================================
   RIGHT COLUMN
========================================================= */

.right-column {

    display: flex;

    flex-direction: column;

    gap: 18px;

    min-width: 0;
}

.side-card {

    padding: 19px;

    min-width: 0;
}

.side-title {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 16px;
}

.side-title h3 {

    color: var(--white);

    font-size: 16px;
}

.side-title span {

    color: var(--green-light);

    font-size: 11px;
}


/* =========================================================
   UPCOMING
========================================================= */

.upcoming {

    display: flex;

    gap: 11px;

    padding: 12px;

    border-radius: 14px;

    background:
        rgba(29,125,69,.15);

    border:
        1px solid
        rgba(129,235,153,.09);

    margin-bottom: 9px;

    transition: .2s ease;

    min-width: 0;
}

.upcoming:hover {

    background:
        rgba(29,125,69,.24);

    border-color:
        rgba(129,235,153,.16);
}

.upcoming-date {

    min-width: 43px;

    height: 43px;

    display: flex;

    flex-direction: column;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background:
        linear-gradient(
            145deg,
            rgba(35,215,110,.75),
            rgba(6,115,60,.75)
        );

    color: white;
}

.upcoming-date strong {

    font-size: 15px;
}

.upcoming-date small {

    font-size: 8px;

    text-transform: uppercase;

    opacity: .85;
}

.upcoming-info {

    min-width: 0;
}

.upcoming-info strong {

    display: block;

    color: var(--white);

    font-size: 12px;

    overflow: hidden;

    white-space: nowrap;

    text-overflow: ellipsis;
}

.upcoming-info span {

    display: block;

    color: var(--muted);

    font-size: 10px;

    margin-top: 4px;
}

.empty-state {

    padding: 22px 12px;

    text-align: center;

    color: var(--muted);

    font-size: 12px;
}


/* =========================================================
   RUTINAS
========================================================= */

.routine {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    padding: 12px;

    border-radius: 13px;

    background:
        rgba(32,213,162,.07);

    border:
        1px solid
        rgba(129,235,153,.08);

    margin-bottom: 8px;

    min-width: 0;
}

.routine-info {

    min-width: 0;
}

.routine-info strong {

    display: block;

    color: var(--white);

    font-size: 12px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.routine-info span {

    display: block;

    color: var(--muted);

    font-size: 10px;

    margin-top: 4px;
}

.routine-day {

    width: 31px;
    height: 31px;

    display: flex;

    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    border-radius: 9px;

    color: #062516;

    background:
        var(--lime);

    font-size: 11px;

    font-weight: 900;
}


/* =========================================================
   MODAL
========================================================= */

.modal {

    position: fixed;

    inset: 0;

    z-index: 100;

    display: none;

    align-items: center;

    justify-content: center;

    padding: 20px;

    background:
        rgba(0,12,7,.70);

    backdrop-filter: blur(8px);
}

.modal.show {
    display: flex;
}

.modal-box {

    width: min(520px, 100%);

    max-height: 90vh;

    overflow-y: auto;

    padding: 25px;

    border-radius: 24px;

    background:
        linear-gradient(
            145deg,
            rgba(33, 128, 20, 0.74),
            rgba(8, 70, 30, 0.64)
        );

    border:
        1px solid
        rgba(129,235,153,.23);

    box-shadow:
        0 30px 100px
        rgba(0,0,0,.55);
}

.modal-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 20px;
}

.modal-header h3 {

    font-family: Minecrafter;
    font-weight: lighter;

    color: var(--white);

    font-size: 30px;
}

.close-modal {

    width: 35px;
    height: 35px;

    border: none;

    border-radius: 10px;

    color: #b6d5be;

    background:
        rgba(255,255,255,.06);

    cursor: pointer;

    font-size: 18px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {

    display: block;

    color: #cff3d7;

    font-size: 12px;

    font-weight: 700;

    margin-bottom: 7px;
}

.form-group input,
.form-group textarea,
.form-group select {

    width: 100%;

    padding: 12px 13px;

    border-radius: 12px;

    outline: none;

    border:
        1px solid
        rgba(129,235,153,.14);

    color: #0a591a;

    background:
        rgba(190, 255, 183, 0.45);

    font-family: peperritos;

    transition: .2s ease;
}

.form-group textarea {

    min-height: 85px;

    resize: vertical;
}

.form-group input,
.form-group textarea,
.form-group select {

    font-family: peperritos;
    font-size: 20px;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {

    border-color:
        rgba(53,212,119,.55);

    box-shadow:
        0 0 0 3px
        rgba(53,212,119,.08);
}

.form-group option {

    font-family: peperritos;

    color: #102418;

    background: white;
}

.form-row {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0,1fr));

    gap: 12px;
}

.form-actions {

    display: flex;

    justify-content: flex-end;

    gap: 9px;

    margin-top: 20px;
}

.btn-secondary {

    padding: 12px 17px;

    border-radius: 12px;

    border:
        1px solid
        rgba(129,235,153,.14);

    color: #b7d5bd;

    background:
        rgba(255,255,255,.05);

    cursor: pointer;
}


.btn-delete {

    padding: 12px 17px;

    border-radius: 12px;

    border: 1px solid rgba(255,120,120,.20);

    color: #ffd1d1;

    background: rgba(180,35,35,.14);

    cursor: pointer;

    display: none;
}

.btn-delete:hover {
    background: rgba(210,45,45,.24);
}

/* =========================================================
   ALERTA
========================================================= */

.alert {

    margin-bottom: 18px;

    padding: 13px 16px;

    border-radius: 13px;

    font-size: 12px;

    font-weight: 600;
}

.alert.error {

    color: #ffd4d4;

    background:
        rgba(220,50,50,.12);

    border:
        1px solid
        rgba(255,100,100,.18);
}


/* =========================================================
   RESPONSIVE ORIGINAL
========================================================= */

@media (max-width: 1200px) {

    .calendar-layout {

        grid-template-columns:
            minmax(0,1fr)
            minmax(240px,300px);

        gap: 15px;
    }

    .main {

        padding-right: 25px;
    }

    .day {

        min-height: 95px;
    }
}

@media (max-width: 1050px) {

    .calendar-layout {

        grid-template-columns: 1fr;
    }

    .right-column {

        display: grid;

        grid-template-columns:
            repeat(2, minmax(0,1fr));
    }
}

@media (max-width: 850px) {

    .main {

        width: calc(100% - 126px);

        margin-left: 126px;

        padding:
            22px
            18px
            30px
            12px;
    }

    .topbar {

        align-items: flex-start;
    }

    .summary-grid {

        grid-template-columns:
            repeat(3, minmax(0,1fr));

        gap: 10px;
    }

    .summary-card {

        padding: 14px;
    }

    .summary-number {

        font-size: 23px;
    }

    .right-column {

        grid-template-columns: 1fr;
    }

    .day {

        min-height: 82px;

        padding: 6px;
    }

    .event-pill {

        font-size: 8px;
    }

    .weekday {

        font-size: 8px;
    }
}

@media (max-width: 650px) {

    .main {

        width: calc(100% - 108px);

        margin-left: 108px;

        padding:
            18px
            10px
            25px
            5px;
    }

    .topbar {

        gap: 10px;
    }

    .welcome h1 {

        font-size: 23px;
    }

    .welcome p {

        font-size: 11px;
    }

    .profile-name {

        display: none;
    }

    .profile-circle {

        width: 40px;
        height: 40px;
    }

    .page-header {

        align-items: flex-start;

        flex-direction: column;

        gap: 13px;
    }

    .btn-primary {

        width: 100%;
    }

    .summary-grid {

        grid-template-columns:
            repeat(3, minmax(0,1fr));

        gap: 7px;
    }

    .summary-card {

        padding: 11px 8px;

        border-radius: 14px;
    }

    .summary-label {

        font-size: 9px;
    }

    .summary-number {

        font-size: 20px;
    }

    .summary-small {

        font-size: 8px;
    }

    .calendar-header {

        padding: 15px;
    }

    .month-title {

        font-size: 17px;
    }

    .calendar-grid {

        gap: 4px;

        padding:
            0 7px 7px;
    }

    .weekdays {

        padding:
            10px 7px 6px;
    }

    .day {

        min-height: 68px;

        border-radius: 10px;
    }

    .day-number {

        width: 23px;
        height: 23px;

        font-size: 10px;
    }

    .event-pill {

        padding: 3px;

        border-left-width: 2px;

        font-size: 7px;
    }

    .event-time {

        display: none;
    }

    .form-row {

        grid-template-columns: 1fr;
    }
}

@media (max-width: 450px) {

    .main {

        width: calc(100% - 104px);

        margin-left: 104px;

        padding-left: 3px;

        padding-right: 6px;
    }

    .summary-grid {

        gap: 5px;
    }

    .summary-card {

        padding: 9px 5px;
    }

    .summary-label {

        font-size: 8px;
    }

    .summary-number {

        font-size: 18px;
    }

    .summary-small {

        display: none;
    }

    .weekday {

        font-size: 7px;
    }

    .calendar-grid {

        gap: 3px;
    }

    .day {

        min-height: 60px;

        padding: 4px;
    }

    .day-number {

        width: 20px;
        height: 20px;

        font-size: 9px;

        margin-bottom: 4px;
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
   PEAK EMBEDDED MODE
   SOLO CUANDO CALENDAR ESTÁ DENTRO DE INDEX.PHP
========================================================= */

body.embedded {

    min-height: 100%;

    width: 100%;

    overflow-x: hidden;

    overflow-y: auto;
}


/* SIN SIDEBAR */

body.embedded .sidebar {

    display: none !important;
}


/* MAIN OCUPA TODO EL IFRAME */

body.embedded .main {

    width: 100%;

    margin-left: 0;

    min-height: 100vh;

    padding:
        30px
        40px
        210px
        30px;

    box-sizing: border-box;
}


/* FONDO */

body.embedded #videobackground {

    position: fixed;

    inset: 0;

    width: 100%;
    height: 100%;
}

body.embedded .video-overlay {

    position: fixed;

    inset: 0;
}


/* =========================================================
   EMBEDDED - PANTALLAS MEDIANAS
========================================================= */

@media (max-width: 1200px) {

    body.embedded .main {

        width: 100%;

        margin-left: 0;

        padding:
            28px
            28px
            210px
            28px;
    }

    body.embedded .calendar-layout {

        grid-template-columns:
            minmax(0, 1fr)
            minmax(230px, 290px);

        gap: 15px;
    }

    body.embedded .day {

        min-height: 92px;
    }
}


/* =========================================================
   EMBEDDED - CUANDO YA NO CABEN DOS COLUMNAS
========================================================= */

@media (max-width: 1050px) {

    body.embedded .calendar-layout {

        grid-template-columns: 1fr;

        gap: 18px;
    }

    body.embedded .right-column {

        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 15px;
    }
}


/* =========================================================
   EMBEDDED - TABLET
========================================================= */

@media (max-width: 850px) {

    body.embedded .main {

        width: 100%;

        margin-left: 0;

        padding:
            22px
            20px
            220px
            20px;
    }

    body.embedded .topbar {

        align-items: flex-start;

        gap: 15px;
    }

    body.embedded .welcome h1 {

        font-size: 36px;
    }

    body.embedded .welcome p {

        font-size: 15px;
    }

    body.embedded .summary-grid {

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

        gap: 10px;
    }

    body.embedded .summary-card {

        padding: 14px;
    }

    body.embedded .summary-label {

        font-size: 17px;
    }

    body.embedded .summary-number {

        font-size: 23px;
    }

    body.embedded .summary-small {

        font-size: 14px;
    }

    body.embedded .calendar-layout {

        grid-template-columns: 1fr;
    }

    body.embedded .right-column {

        display: grid;

        grid-template-columns:
            1fr;

        gap: 15px;
    }

    body.embedded .day {

        min-height: 78px;

        padding: 6px;
    }

    body.embedded .event-pill {

        font-size: 8px;
    }

    body.embedded .weekday {

        font-size: 8px;
    }
}


/* =========================================================
   EMBEDDED - PANTALLAS PEQUEÑAS
========================================================= */

@media (max-width: 650px) {

    body.embedded .main {

        width: 100%;

        margin-left: 0;

        padding:
            18px
            12px
            220px
            12px;
    }

    body.embedded .topbar {

        gap: 10px;
    }

    body.embedded .welcome h1 {

        font-size: 27px;
    }

    body.embedded .welcome p {

        font-size: 11px;

        line-height: 1.5;
    }

    body.embedded .profile-name {

        display: none;
    }

    body.embedded .profile-circle {

        width: 40px;
        height: 40px;
    }

    body.embedded .page-header {

        align-items: flex-start;

        flex-direction: column;

        gap: 13px;
    }

    body.embedded .page-title {

        width: 100%;
    }

    body.embedded .page-title h2 {

        font-size: 23px;
    }

    body.embedded .page-title p {

        font-size: 10px;
    }

    body.embedded .page-title-icon {

        width: 45px;
        height: 45px;

        font-size: 20px;
    }

    body.embedded .btn-primary {

        width: 100%;
    }

    body.embedded .summary-grid {

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

        gap: 7px;
    }

    body.embedded .summary-card {

        padding: 11px 8px;

        border-radius: 14px;
    }

    body.embedded .summary-label {

        font-size: 9px;
    }

    body.embedded .summary-number {

        font-size: 20px;
    }

    body.embedded .summary-small {

        font-size: 8px;
    }

    body.embedded .calendar-header {

        padding: 14px;
    }

    body.embedded .month-title {

        font-size: 17px;
    }

    body.embedded .month-subtitle {

        font-size: 10px;
    }

    body.embedded .calendar-grid {

        gap: 4px;

        padding:
            0 7px 7px;
    }

    body.embedded .weekdays {

        padding:
            10px 7px 6px;
    }

    body.embedded .day {

        min-height: 66px;

        padding: 5px;

        border-radius: 10px;
    }

    body.embedded .day-number {

        width: 23px;
        height: 23px;

        font-size: 10px;
    }

    body.embedded .event-pill {

        padding: 3px;

        border-left-width: 2px;

        font-size: 7px;
    }

    body.embedded .event-time {

        display: none;
    }

    body.embedded .right-column {

        grid-template-columns: 1fr;
    }

    body.embedded .form-row {

        grid-template-columns: 1fr;
    }
}


/* =========================================================
   EMBEDDED - MUY PEQUEÑO
========================================================= */

@media (max-width: 450px) {

    body.embedded .main {

        width: 100%;

        margin-left: 0;

        padding:
            15px
            7px
            230px
            7px;
    }

    body.embedded .welcome h1 {

        font-size: 23px;
    }

    body.embedded .welcome p {

        font-size: 9px;
    }

    body.embedded .summary-grid {

        gap: 5px;
    }

    body.embedded .summary-card {

        padding: 9px 5px;
    }

    body.embedded .summary-label {

        font-size: 8px;
    }

    body.embedded .summary-number {

        font-size: 18px;
    }

    body.embedded .summary-small {

        display: none;
    }

    body.embedded .weekday {

        font-size: 7px;
    }

    body.embedded .calendar-grid {

        gap: 3px;
    }

    body.embedded .day {

        min-height: 58px;

        padding: 4px;
    }

    body.embedded .day-number {

        width: 20px;
        height: 20px;

        font-size: 9px;

        margin-bottom: 4px;
    }

    body.embedded .event-pill {

        font-size: 6px;
    }

    body.embedded .more-events {

        font-size: 7px;
    }

    body.embedded .month-actions {

        gap: 4px;
    }

    body.embedded .month-btn {

        width: 32px;
        height: 32px;
    }
}

</style>

</head>


<body class="<?= $embedded ? 'embedded' : '' ?>">


<!-- =====================================================
     VIDEO
===================================================== -->

<video
    autoplay
    muted
    loop
    playsinline
    id="videobackground"
>

    <source
        src="../data/bggg.mp4"
        type="video/mp4"
    >

</video>

<div class="video-overlay"></div>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">

    <div class="logo">

        <img
            src="../data/descarga.png"
            alt="PEAK"
            onerror="this.style.display='none'; this.parentElement.innerHTML='P';"
        >

    </div>

    <nav class="navigation">

        <a
            href="dashboard.php"
            class="nav-item"
            data-peak-page="dashboard.php"
        >
            ⌂
            <span class="tooltip">
                Dashboard
            </span>
        </a>

        <a
            href="calendar.php"
            class="nav-item active"
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


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <div class="welcome">

            <h1>
                Mi calendario
            </h1>

            <p>
                Organiza tus actividades y mantén tu progreso.
            </p>

        </div>

        <div class="profile-top">

            <span class="profile-name">
                <?= e($nombreUsuario) ?>
            </span>

            <div class="profile-circle">

                <?= e(
                    strtoupper(
                        substr($nombreUsuario, 0, 1)
                    )
                ) ?>

            </div>

        </div>

    </div>


    <!-- HEADER -->

    <div class="page-header">

        <div class="page-title">

            <div class="page-title-icon">
                ◫
            </div>

            <div>

                <h2>
                    <?= e($months[$month]) ?>
                    <?= e($year) ?>
                </h2>

                <p>
                    Planifica tus días y organiza tus actividades.
                </p>

            </div>

        </div>

        <button
            type="button"
            class="btn-primary"
            onclick="openCreateModal()"
        >
            + Agregar evento
        </button>

    </div>


    <?php if ($message !== ""): ?>

        <div class="alert <?= e($messageType) ?>">

            <?= e($message) ?>

        </div>

    <?php endif; ?>


    <section class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                Eventos registrados
            </div>

            <div class="summary-number">
                <?= $totalEventos ?>
            </div>

            <div class="summary-small">
                Actividades con fecha
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Próximos eventos
            </div>

            <div class="summary-number">
                <?= count($nextEvents) ?>
            </div>

            <div class="summary-small">
                Pendientes
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Rutinas
            </div>

            <div class="summary-number">
                <?= $totalRutinas ?>
            </div>

            <div class="summary-small">
                Actividades repetitivas
            </div>

        </div>

    </section>


    <!-- =================================================
         CALENDARIO
    ================================================= -->

    <section class="calendar-layout">

        <div class="glass-card calendar-card">

            <div class="calendar-header">

                <div>

                    <div class="month-title">
                        <?= e($months[$month]) ?>
                        <?= e($year) ?>
                    </div>

                    <div class="month-subtitle">
                        Vista mensual
                    </div>

                </div>

                <div class="month-actions">

                    <a
                        class="month-btn"
                        href="calendar.php?month=<?= $previousMonth ?>&year=<?= $previousYear ?><?= $embedded ? '&embedded=1' : '' ?>"
                    >
                        ‹
                    </a>

                    <a
                        class="month-btn"
                        href="calendar.php?month=<?= date("n") ?>&year=<?= date("Y") ?><?= $embedded ? '&embedded=1' : '' ?>"
                        title="Ir al mes actual"
                    >
                        •
                    </a>

                    <a
                        class="month-btn"
                        href="calendar.php?month=<?= $nextMonth ?>&year=<?= $nextYear ?><?= $embedded ? '&embedded=1' : '' ?>"
                    >
                        ›
                    </a>

                </div>

            </div>


            <!-- WEEKDAYS -->

            <div class="weekdays">

                <?php foreach ($weekdays as $weekday): ?>

                    <div class="weekday">
                        <?= e($weekday) ?>
                    </div>

                <?php endforeach; ?>

            </div>


            <!-- GRID -->

            <div class="calendar-grid">

                <?php for ($i = 1; $i < $firstDay; $i++): ?>

                    <div class="day empty"></div>

                <?php endfor; ?>


                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>

                    <?php

                    $dateString = sprintf(
                        "%04d-%02d-%02d",
                        $year,
                        $month,
                        $day
                    );

                    $isToday =
                        ($dateString === $today);

                    $dayEvents =
                        $eventData[$dateString] ?? [];

                    ?>

                    <div
                        class="day <?= $isToday ? "today" : "" ?>"
                        onclick="openCreateModal('<?= e($dateString) ?>')"
                    >

                        <div class="day-number">
                            <?= $day ?>
                        </div>


                        <div class="day-events">

                            <?php

                            $visibleEvents =
                                array_slice(
                                    $dayEvents,
                                    0,
                                    3
                                );

                            ?>

                            <?php foreach ($visibleEvents as $event): ?>

                                <?php

                                $eventJson = json_encode(
                                    $event,
                                    JSON_HEX_TAG |
                                    JSON_HEX_APOS |
                                    JSON_HEX_AMP |
                                    JSON_HEX_QUOT
                                );

                                ?>

                                <div
                                    class="event-pill"
                                    onclick='event.stopPropagation(); openEditModal(<?= intval($event["id"]) ?>, <?= $eventJson ?>)'
                                >

                                    <?php if (!empty($event["hora_inicio"])): ?>

                                        <span class="event-time">

                                            <?= e(
                                                formatTime(
                                                    $event["hora_inicio"]
                                                )
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                    <?= e($event["titulo"]) ?>

                                </div>

                            <?php endforeach; ?>


                            <?php if (count($dayEvents) > 3): ?>

                                <div class="more-events">

                                    +
                                    <?= count($dayEvents) - 3 ?>
                                    más

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endfor; ?>

            </div>

        </div>


        <!-- =================================================
             COLUMNA DERECHA
        ================================================= -->

        <aside class="right-column">


            <!-- PRÓXIMOS -->

            <div class="glass-card side-card">

                <div class="side-title">

                    <h3>
                        Próximos eventos
                    </h3>

                    <span>
                        <?= count($nextEvents) ?>
                    </span>

                </div>


                <?php if (empty($nextEvents)): ?>

                    <div class="empty-state">
                        No tienes eventos próximos.
                    </div>

                <?php else: ?>

                    <?php foreach ($nextEvents as $event): ?>

                        <?php

                        $eventDate =
                            strtotime($event["fecha"]);

                        ?>

                        <div class="upcoming">

                            <div class="upcoming-date">

                                <strong>
                                    <?= date(
                                        "d",
                                        $eventDate
                                    ) ?>
                                </strong>

                                <small>
                                    <?= date(
                                        "M",
                                        $eventDate
                                    ) ?>
                                </small>

                            </div>


                            <div class="upcoming-info">

                                <strong>
                                    <?= e(
                                        $event["titulo"]
                                    ) ?>
                                </strong>

                                <span>

                                    <?php if (!empty($event["hora_inicio"])): ?>

                                        <?= e(
                                            formatTime(
                                                $event["hora_inicio"]
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        Todo el día

                                    <?php endif; ?>

                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>


           <!-- RUTINAS -->

<div class="glass-card side-card">

    <div class="side-title">

        <h3>
            Rutinas
        </h3>

        <span>
            <?= $totalRutinas ?>
        </span>

    </div>


    <?php if (empty($rutinas)): ?>

        <div class="empty-state">
            Todavía no tienes rutinas.
            <br>
            Repite un tipo durante 3 días seguidos.
        </div>

    <?php else: ?>

        <?php foreach ($rutinas as $routine): ?>

            <?php

            /*
             * Último día de la cadena.
             */
            $routineEndDate =
                strtotime(
                    $routine["fecha_fin"]
                );

            ?>

            <div class="routine">

                <div class="routine-info">

                    <strong>
                        <?= e(
                            $routine["nombre"]
                        ) ?>
                    </strong>

                    <span>

                        <?= e(
                            $routine["dias"]
                        ) ?>

                        días consecutivos

                    </span>

                </div>


                <div
                    class="routine-day"
                    title="Días consecutivos"
                >
                    <?= e(
                        $routine["dias"]
                    ) ?>
                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

        </aside>

    </section>

</main>


<!-- =====================================================
     MODAL EVENTO
===================================================== -->

<div
    class="modal"
    id="eventModal"
>

    <div class="modal-box">

        <div class="modal-header">

            <h3 id="modalTitle">
                Agregar evento
            </h3>

            <button
                type="button"
                class="close-modal"
                onclick="closeModal()"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            action="calendar.php?month=<?= $month ?>&year=<?= $year ?><?= $embedded ? '&embedded=1' : '' ?>"
            id="eventForm"
        >

            <input
                type="hidden"
                name="event_id"
                id="event_id"
                value=""
            >


            <div class="form-group">

                <label>
                    Título
                </label>

                <input
                    type="text"
                    name="titulo"
                    id="titulo"
                    placeholder="Ej. Estudiar para el examen"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Descripción
                </label>

                <textarea
                    name="descripcion"
                    id="descripcion"
                    placeholder="Agrega una descripción..."
                ></textarea>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>
                        Fecha
                    </label>

                    <input
                        type="date"
                        name="fecha"
                        id="fecha"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Tipo
                    </label>

                    <select
                        name="tipo"
                        id="tipo"
                    >

                        <option value="evento">
                            Evento
                        </option>

                        <option value="estudio">
                            Estudio
                        </option>

                        <option value="tarea">
                            Tarea
                        </option>

                        <option value="examen">
                            Examen
                        </option>

                        <option value="personal">
                            Personal
                        </option>

                        <option value="otro">
                            Otro
                        </option>

                    </select>

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>
                        Hora de inicio
                    </label>

                    <input
                        type="time"
                        name="hora_inicio"
                        id="hora_inicio"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Hora de finalización
                    </label>

                    <input
                        type="time"
                        name="hora_fin"
                        id="hora_fin"
                    >

                </div>

            </div>


            <div class="form-actions">

                <button
                    type="button"
                    class="btn-delete"
                    id="deleteEventButton"
                    onclick="deleteCurrentEvent()"
                >
                    Eliminar
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    onclick="closeModal()"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar evento
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =====================================================
     MODAL LOGOUT
     SOLO UNO
===================================================== -->

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
   ELEMENTOS
========================================================= */

const modal =
    document.getElementById("eventModal");

const eventId =
    document.getElementById("event_id");

const titulo =
    document.getElementById("titulo");

const descripcion =
    document.getElementById("descripcion");

const fecha =
    document.getElementById("fecha");

const horaInicio =
    document.getElementById("hora_inicio");

const horaFin =
    document.getElementById("hora_fin");

const tipo =
    document.getElementById("tipo");

const modalTitle =
    document.getElementById("modalTitle");

const deleteEventButton =
    document.getElementById("deleteEventButton");


/* =========================================================
   CREAR
========================================================= */

function openCreateModal(selectedDate = "") {

    modal.classList.add("show");

    modalTitle.textContent =
        "Agregar evento";

    eventId.value = "";

    deleteEventButton.style.display = "none";

    titulo.value = "";

    descripcion.value = "";

    fecha.value =
        selectedDate ||
        "<?= date("Y-m-d") ?>";

    horaInicio.value = "";

    horaFin.value = "";

    tipo.value = "evento";
}


/* =========================================================
   EDITAR
========================================================= */

function openEditModal(id, event) {

    modal.classList.add("show");

    modalTitle.textContent =
        "Editar evento";

    eventId.value = id;

    deleteEventButton.style.display = "inline-block";

    titulo.value =
        event.titulo || "";

    descripcion.value =
        event.descripcion || "";

    fecha.value =
        event.fecha || "";

    horaInicio.value =
        event.hora_inicio
            ? event.hora_inicio.substring(0, 5)
            : "";

    horaFin.value =
        event.hora_fin
            ? event.hora_fin.substring(0, 5)
            : "";

    tipo.value =
        event.tipo || "evento";
}


/* =========================================================
   ELIMINAR EVENTO
========================================================= */

function deleteCurrentEvent() {

    const id = parseInt(eventId.value || "0", 10);

    if (!id) {
        return;
    }

    if (!confirm("¿Quieres eliminar este evento?")) {
        return;
    }

    const embeddedParam =
        <?= $embedded ? "'&embedded=1'" : "''" ?>;

    window.location.href =
        "calendar.php?delete=" +
        encodeURIComponent(id) +
        "&month=<?= $month ?>&year=<?= $year ?>" +
        embeddedParam;
}


/* =========================================================
   CERRAR MODAL
========================================================= */

function closeModal() {

    modal.classList.remove("show");
}


/* =========================================================
   CLICK AFUERA
========================================================= */

modal.addEventListener(
    "click",
    function(event) {

        if (event.target === modal) {
            closeModal();
        }

    }
);


/* =========================================================
   ESC
========================================================= */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {
            closeModal();
        }

    }
);


/* =========================================================
   NAVEGACIÓN GLOBAL DE PEAK
========================================================= */

document.querySelectorAll(
    "[data-peak-page]"
).forEach(
    function(link) {

        link.addEventListener(
            "click",
            function(event) {

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
                            type: "PEAK_NAVIGATE",
                            page: this.dataset.peakPage
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


/* =========================================================
   ABRIR CONFIRMACIÓN
========================================================= */

logoutButton.addEventListener(
    "click",
    function(event) {

        event.preventDefault();

        logoutModal.classList.add(
            "show"
        );
    }
);


/* =========================================================
   CANCELAR
========================================================= */

cancelLogout.addEventListener(
    "click",
    function() {

        logoutModal.classList.remove(
            "show"
        );
    }
);


/* =========================================================
   CERRAR AL HACER CLICK FUERA
========================================================= */

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


            const response =
                await fetch(
                    "calendar.php<?= $embedded ? '?embedded=1' : '' ?>",
                    {
                        method: "POST",
                        body: formData
                    }
                );


            const data =
                await response.json();


            if (data.success) {

                window.location.href =
                    "login.php";

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

</script>

</body>
</html>