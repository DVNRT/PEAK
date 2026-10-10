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


if (!isset($_SESSION["usuario_id"])) {

    header("Location: login.php");

    exit();
}


$usuario_id =
    intval($_SESSION["usuario_id"]);

$nombreUsuario =
    $_SESSION["nombre"] ?? "";


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

?>

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>𝐈𝐀 𝐇𝐔𝐁</title>

<link
    rel="icon"
    type="image/png"
    href="../data/descarga.png"
>


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

overflow: hidden;
    scroll-behavior: smooth;
}

body {

    overflow: hidden;

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
   EXACTAMENTE COMO IA_HUB.PHP
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

    font-size: 19px;

    line-height: 0.1;

    margin-bottom: 2px;
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
   IA HEADER
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


/* =========================================================
   HERO
========================================================= */

.hero-card {

    position: relative;

    width: 100%;

    padding: 24px;

    margin-bottom: 20px;

    border-radius: 23px;

    background:
        linear-gradient(
            145deg,
            rgba(7,59,36,.51),
            rgba(3,37,23,.42)
        );

    border:
        1px solid var(--border);

    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    box-shadow:
        0 15px 45px
        rgba(0,0,0,.22);

    overflow: hidden;
}

.hero-card::after {

    content: "";

    position: absolute;

    width: 180px;
    height: 180px;

    right: -70px;
    top: -80px;

    border-radius: 50%;

    background:
        rgba(54,218,116,.10);

    pointer-events: none;
}

.hero-card h2 {

    color: var(--white);

    font-size: 23px;

    margin-bottom: 7px;
}

.hero-card h2 span {

    color: var(--green-light);
}

.hero-card p {

    color: var(--muted);

    font-size: 13px;

    line-height: 1.7;

    max-width: 850px;
}


/* =========================================================
   AI LAYOUT
========================================================= */

.ai-layout {

    display: grid;

   width: 700px;

    gap: 20px;

    width: 200%;

}


/* =========================================================
   AI OPTIONS
========================================================= */

.ai-options {

    padding: 18px;

    min-width: 0;

    border-radius: 23px;

    background:
        linear-gradient(
            145deg,
            rgba(5,45,28,.55),
            rgba(2,31,20,.45)
        );

    border:
        1px solid var(--border);

    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    box-shadow: var(--shadow);

    height: fit-content;
}

.ai-options-title {

    color: var(--green-light);

    font-size: 12px;

    font-weight: 800;

    margin-bottom: 14px;
}

.ai-option {

    width: 100%;

    display: block;

    padding: 14px;

    margin-bottom: 9px;

    border: 1px solid
        rgba(129,235,153,.10);

    border-radius: 15px;

    background:
        rgba(255,255,255,.025);

    color: var(--muted);

    text-align: left;

    font-family: inherit;

    cursor: pointer;

    transition: .25s ease;
}

.ai-option:last-child {

    margin-bottom: 0;
}

.ai-option:hover {

    color: white;

    background:
        rgba(32,213,162,.10);

    border-color:
        rgba(129,235,153,.20);

    transform:
        translateY(-1px);
}

.ai-option.active {

    color: white;

    background:
        linear-gradient(
            145deg,
            rgba(34,206,103,.24),
            rgba(5,112,61,.22)
        );

    border-color:
        rgba(155,225,93,.28);

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.18);
}

.ai-option-title {

    display: block;

    color: var(--white);

    font-size: 13px;

    font-weight: 800;

    margin-bottom: 5px;
}

.ai-option-description {

    display: block;

    color: var(--muted);

    font-size: 10px;

    line-height: 1.5;
}


/* =========================================================
   CHAT CARD
========================================================= */

.chat-card {

    display: flex;

    flex-direction: column;

    width: 1339px;

    height: 607px;


    border-radius: 23px;

    background:
        linear-gradient(
            145deg,
            rgba(5,45,28,.55),
            rgba(2,31,20,.48)
        );

    border:
        1px solid var(--border);

    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    box-shadow: var(--shadow);

    overflow: hidden;
}


.dola-frame {

    flex: 1;


    width: 100%;

    position: relative;

    background:
        rgba(2,25,15,.30);

    overflow: hidden;
}

.dola-frame iframe {

    width: 100%;

    height: 100%;

    display: block;

    border: none;

    background: white;
}


/* =========================================================
   SCROLLBAR
========================================================= */

::-webkit-scrollbar {

    width: 7px;
}

::-webkit-scrollbar-track {

    background:
        rgba(0,0,0,.12);
}

::-webkit-scrollbar-thumb {

    background:
        rgba(53,212,119,.25);

    border-radius: 20px;
}

::-webkit-scrollbar-thumb:hover {

    background:
        rgba(53,212,119,.40);
}


/* =========================================================
   RESPONSIVE
   SIDEBAR SE MANTIENE IGUAL
========================================================= */

@media (max-width: 1050px) {

    .ai-layout {

        grid-template-columns: 1fr;
    }

    .ai-options {

        display: grid;

        grid-template-columns:
            repeat(3, minmax(0,1fr));

        gap: 10px;
    }

    .ai-options-title {

        grid-column: 1 / -1;

        margin-bottom: 0;
    }

    .ai-option {

        margin-bottom: 0;
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

    .welcome h1 {

        font-size: 18px;
    }

    .welcome p {

        font-size: 14px;
    }

    .ai-options {

        grid-template-columns: 1fr;
    }

    .ai-option {

        padding: 12px;
    }

    .chat-card {

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

        font-size: 25px;
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

    .page-title h2 {

        font-size: 23px;
    }

    .hero-card {

        padding: 18px;
    }

    .hero-card h2 {

        font-size: 19px;
    }

    .chat-card {

        height: 570px;
    }
}

@media (max-width: 450px) {

    .main {

        width: calc(100% - 104px);

        margin-left: 104px;

        padding-left: 3px;

        padding-right: 6px;
    }

    .page-title-icon {

        width: 43px;
        height: 43px;

        font-size: 20px;
    }

    .page-title h2 {

        font-size: 20px;
    }

    .hero-card p {

        font-size: 11px;
    }

    .chat-header {

        padding:
            13px 14px;
    }

    .status {

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
   MODO EMBEBIDO EN INDEX.PHP
   NO AFECTA LA VISTA NORMAL
========================================================= */

body.embedded {

    min-height: 100%;

    height: 100%;

    overflow-x: hidden;

    overflow-y: auto;

    /*
       Espacio extra para que el reproductor
       flotante de INDEX no tape el contenido.
    */

    padding-bottom: 150px;
}


/* OCULTAR SIDEBAR SOLAMENTE EN INDEX */

body.embedded .sidebar {

    display: none;
}


/* MAIN A PANTALLA COMPLETA */

body.embedded .main {

    width: 100%;

    margin-left: 0;

    min-height: 100%;

    padding:
        24px
        24px
        180px
        24px;
}


/* VIDEO */

body.embedded #videobackground {

    position: fixed;
}

body.embedded .video-overlay {

    position: fixed;
}


</style>

</head>


<body class="<?= $embedded ? 'embedded' : '' ?>">


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
            class="nav-item active"
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


    <!-- =================================================
         TOPBAR
    ================================================== -->

   



    <section class="ai-layout">


        <section class="chat-card">


            <div class="dola-frame">

                <iframe
                    src="https://www.dola.com/chat/"
                    title="Dola AI"
                    allow="microphone; clipboard-write"
                    referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>

            </div>

        </section>

    </section>

</main>


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
   OPCIONES DE IA
========================================================= */

const aiOptions =
    document.querySelectorAll(
        ".ai-option"
    );


aiOptions.forEach(
    function(option) {

        option.addEventListener(
            "click",
            function() {

                aiOptions.forEach(
                    function(item) {

                        item.classList.remove(
                            "active"
                        );

                    }
                );


                option.classList.add(
                    "active"
                );

            }
        );

    }
);


/* =========================================================
   DOLA
========================================================= */

function seleccionarDola()
{
    aiOptions.forEach(
        function(item) {

            item.classList.remove(
                "active"
            );

        }
    );


    const dola =
        document.querySelector(
            ".ai-option:nth-of-type(4)"
        );


    if (dola) {

        dola.classList.add(
            "active"
        );

    }
}


/* =========================================================
   NAVEGACIÓN GLOBAL DE PEAK
   FUNCIONA CUANDO IA_HUB ESTÁ DENTRO DE INDEX.PHP
========================================================= */

document
    .querySelectorAll("[data-peak-page]")
    .forEach(
        function(link) {

            link.addEventListener(
                "click",
                function(event) {

                    /*
                       Si estamos dentro de index.php,
                       no dejamos que el iframe cambie
                       de página internamente.
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


/* ABRIR CONFIRMACIÓN */

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


/* CERRAR AL HACER CLICK FUERA */

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
               El logout se procesa en ESTA página,
               no en otra página.
            */

            const response =
                await fetch(
                    "IA_HUB.php<?= $embedded ? '?embedded=1' : '' ?>",
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