<?php
session_start();

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



if (!isset($_SESSION["usuario_id"])) {
    header("Location: pages/login.php");
    exit();
}


$nombreUsuario = $_SESSION["nombre"] ?? "Usuario";

?>
<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>𝐏𝐄𝐀𝐊</title>

    <link
        rel="icon"
        type="image/png"
        href="data/descarga.png"
    >


<style>



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

    --glass: rgba(5, 45, 28, .76);
    --glass-light: rgba(60, 190, 110, .10);
    --border: rgba(129, 235, 153, .22);

    --shadow:
        0 20px 60px rgba(0, 0, 0, .38);

    --radius: 22px;
}



* {

    margin: 0;
    padding: 0;

    box-sizing: border-box;
}


html,
body {

    width: 100%;
    height: 100%;
}


body {

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        var(--bg);

    color:
        var(--text);

    overflow:
        hidden;
}


.background {

    position: fixed;

    inset: 0;

    overflow: hidden;

    z-index: -5;

    background:
        #041b12;
}


.background video {

    width: 100%;
    height: 100%;

    object-fit: cover;

    opacity: .45;
}


.background-overlay {

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            rgba(2, 18, 11, .88),
            rgba(3, 31, 19, .60)
        );
}



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

    padding:
        20px 12px;

    background:
        rgba(3, 45, 27, .78);

    border:
        1px solid
        var(--border);

    border-radius:
        26px;

    backdrop-filter:
        blur(22px);

    -webkit-backdrop-filter:
        blur(22px);

    box-shadow:
        var(--shadow);

    z-index:
        100;
}


.logo {

    width: 52px;
    height: 52px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius:
        17px;

    background:
        linear-gradient(
            145deg,
            #087642
        );

    color:
        white;

    font-size:
        21px;

    font-weight:
        900;

    overflow:
        hidden;
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

    border-radius:
        17px;

    color:
        #78aa8b;

    font-size:
        22px;

    text-decoration:
        none;

    transition:
        .25s ease;

    cursor:
        pointer;

    border:
        none;

    outline:
        none;

    font-family:
        inherit;
}



.navigation .nav-item {

    background:
        transparent;

    appearance:
        none;

    -webkit-appearance:
        none;
}


.nav-item:hover {

    color:
        white;

    background:
        rgba(
            32,
            213,
            162,
            .13
        );

    transform:
        translateY(-1px);

    box-shadow:
        0 0 18px
        rgba(
            37,
            217,
            111,
            .12
        );
}


.nav-item.active {

    color:
        white;

    background:
        linear-gradient(
            145deg,
            rgba(34, 206, 103, .75),
            rgba(5, 112, 61, .75)
        );

    box-shadow:
        inset 0 0 0 1px
        rgba(196, 255, 203, .16),

        0 8px 25px
        rgba(0, 0, 0, .25),

        0 0 20px
        rgba(37, 217, 111, .16);
}


.nav-item.active::before {

    content:
        "";

    position:
        absolute;

    left:
        -13px;

    width:
        4px;

    height:
        24px;

    border-radius:
        10px;

    background:
        var(--lime);

    box-shadow:
        0 0 10px
        rgba(155, 225, 93, .6);
}



.tooltip {

    position:
        absolute;

    left:
        70px;

    top:
        50%;

    transform:
        translateY(-50%)
        translateX(-5px);

    padding:
        8px 11px;

    border-radius:
        9px;

    background:
        rgba(3, 25, 16, .96);

    border:
        1px solid
        var(--border);

    color:
        var(--white);

    font-size:
        12px;

    font-weight:
        600;

    white-space:
        nowrap;

    opacity:
        0;

    pointer-events:
        none;

    transition:
        .2s ease;

    box-shadow:
        0 10px 30px
        rgba(0, 0, 0, .35);
}


.nav-item:hover .tooltip {

    opacity:
        1;

    transform:
        translateY(-50%)
        translateX(0);
}


.logout {

    margin-top:
        8px;
}



.app {

    width:
        100%;

    height:
        100vh;

    padding-left:
        126px;

    position:
        relative;
}


.page-container {

    width:
        100%;

    height:
        100vh;

    padding:
        18px 18px 120px 0;
}



#pageFrame {

    width:
        100%;

    height:
        100%;

    display:
        block;

    border:
        none;

    border-radius:
        24px;

    background:
        transparent;

    overflow:
        hidden;
}


.global-player {

    position:
        fixed;

    left:
        126px;

    right:
        24px;

    bottom:
        18px;

    height:
        96px;

    display:
        flex;

    align-items:
        stretch;

    padding:
        10px 16px;

    background:
        rgba(4, 31, 20, .95);

    border:
        1px solid
        rgba(129, 235, 153, .22);

    border-radius:
        22px;

    backdrop-filter:
        blur(24px);

    -webkit-backdrop-filter:
        blur(24px);

    box-shadow:
        0 20px 50px
        rgba(0,0,0,.45),

        0 0 30px
        rgba(37,217,111,.08);

    z-index:
        200;
}


.global-section {

    flex:
        1;

    min-width:
        0;

    display:
        flex;

    align-items:
        center;
}


.global-divider {

    width:
        1px;

    margin:
        12px 16px;

    background:
        rgba(129,235,153,.18);
}



.music-global {

    gap:
        16px;
}


.global-media-info {

    min-width:
        210px;

    max-width:
        280px;

    display:
        flex;

    align-items:
        center;

    gap:
        10px;
}


.player-cover {

    width:
        52px;

    height:
        52px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        15px;

    background:
        linear-gradient(
            145deg,
            #063d29
        );

    color:
        white;

    font-size:
        22px;

    box-shadow:
        inset 0 0 0 1px
        rgba(255,255,255,.08),

        0 8px 20px
        rgba(0,0,0,.25);
}


.player-info {

    min-width:
        0;

    display:
        flex;

    flex-direction:
        column;

    gap:
        4px;
}


.player-title {

    color:
        var(--white);

    font-size:
        14px;

    font-weight:
        700;

    white-space:
        nowrap;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

    max-width:
        230px;
}


.player-artist {

    color:
        var(--muted);

    font-size:
        11px;

    white-space:
        nowrap;

    overflow:
        hidden;

    text-overflow:
        ellipsis;

    max-width:
        230px;
}


.music-controls {

    flex:
        1;

    min-width:
        0;

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    justify-content:
        center;

    gap:
        5px;
}


.player-buttons {

    display:
        flex;

    align-items:
        center;

    gap:
        8px;
}


.player-button {

    width:
        34px;

    height:
        34px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border:
        none;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.06);

    color:
        var(--text);

    cursor:
        pointer;

    font-size:
        15px;

    transition:
        .2s ease;

    font-family:
        inherit;
}


.player-button:hover {

    background:
        rgba(32, 213, 162, .16);

    color:
        white;

    transform:
        scale(1.06);
}


.player-button.main {

    width:
        40px;

    height:
        40px;

    background:
        linear-gradient(
            145deg,
            #35d477,
            #087f45
        );

    color:
        #041b12;

    font-size:
        17px;

    font-weight:
        900;

    box-shadow:
        0 5px 18px
        rgba(18,168,91,.25);
}


.progress-area {

    width:
        min(480px, 80%);

    display:
        flex;

    align-items:
        center;

    gap:
        8px;
}


.time {

    color:
        #79a98b;

    font-size:
        9px;

    min-width:
        30px;

    text-align:
        center;
}


.progress-bar {

    flex:
        1;

    height:
        4px;

    border-radius:
        20px;

    appearance:
        none;

    -webkit-appearance:
        none;

    background:
        rgba(255,255,255,.10);

    cursor:
        pointer;
}


.progress-bar::-webkit-slider-thumb {

    appearance:
        none;

    -webkit-appearance:
        none;

    width:
        10px;

    height:
        10px;

    border-radius:
        50%;

    background:
        var(--lime);

    cursor:
        pointer;

    box-shadow:
        0 0 8px
        rgba(155,225,93,.55);
}


.progress-bar::-moz-range-thumb {

    width:
        10px;

    height:
        10px;

    border:
        none;

    border-radius:
        50%;

    background:
        var(--lime);

    cursor:
        pointer;
}


.music-volume {

    min-width:
        95px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        flex-end;

    gap:
        7px;
}


.volume-icon {

    color:
        var(--muted);

    font-size:
        15px;
}


.volume-slider {

    width:
        90px;

    height:
        4px;

    appearance:
        none;

    -webkit-appearance:
        none;

    background:
        rgba(255,255,255,.10);

    border-radius:
        20px;

    cursor:
        pointer;
}


.volume-slider::-webkit-slider-thumb {

    appearance:
        none;

    -webkit-appearance:
        none;

    width:
        9px;

    height:
        9px;

    border-radius:
        50%;

    background:
        var(--green-light);
}


.volume-slider::-moz-range-thumb {

    width:
        9px;

    height:
        9px;

    border:
        none;

    border-radius:
        50%;

    background:
        var(--green-light);
}



.flowtime-global {

    gap:
        14px;
}


.flowtime-info {

    min-width:
        170px;

    display:
        flex;

    align-items:
        center;

    gap:
        10px;
}


.flowtime-cover {

    width:
        48px;

    height:
        48px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        14px;

    background:
        linear-gradient(
            145deg,
            #087f45
        );

    color:
        #041b12;

    font-size:
        21px;

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.25);
}


.flowtime-text {

    min-width:
        0;

    display:
        flex;

    flex-direction:
        column;

    gap:
        4px;
}


.flowtime-text strong {

    color:
        var(--white);

    font-size:
        13px;

    white-space:
        nowrap;
}


.flowtime-text span {

    color:
        var(--muted);

    font-size:
        10px;

    white-space:
        nowrap;
}


.flowtime-center {

    flex:
        1;

    min-width:
        120px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        10px;
}


.global-flow-time {

    color:
        var(--white);

    font-size:
        25px;

    font-weight:
        800;

    font-variant-numeric:
        tabular-nums;

    letter-spacing:
        .5px;
}


.global-flow-mode {

    padding:
        5px 8px;

    border-radius:
        7px;

    background:
        rgba(53,212,119,.10);

    border:
        1px solid
        rgba(53,212,119,.18);

    color:
        var(--green-light);

    font-size:
        9px;

    font-weight:
        800;

    letter-spacing:
        1px;
}


.flowtime-controls {

    display:
        flex;

    align-items:
        center;

    gap:
        7px;
}



.logout-modal {

    position:
        fixed;

    inset:
        0;

    display:
        none;

    align-items:
        center;

    justify-content:
        center;

    background:
        rgba(0, 10, 6, .72);

    backdrop-filter:
        blur(8px);

    z-index:
        500;
}


.logout-modal.show {

    display:
        flex;
}


.logout-box {

    width:
        min(420px, calc(100% - 40px));

    padding:
        30px;

    border-radius:
        24px;

    background:
        rgba(4, 37, 24, .97);

    border:
        1px solid
        var(--border);

    box-shadow:
        0 30px 80px
        rgba(0,0,0,.55);

    text-align:
        center;

    animation:
        modalIn .22s ease;
}


@keyframes modalIn {

    from {

        opacity:
            0;

        transform:
            translateY(12px)
            scale(.97);
    }

    to {

        opacity:
            1;

        transform:
            translateY(0)
            scale(1);
    }
}


.logout-icon {

    width:
        58px;

    height:
        58px;

    margin:
        0 auto 15px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        18px;

    background:
        rgba(155,225,93,.10);

    color:
        var(--lime);

    font-size:
        25px;
}


.logout-box h3 {

    color:
        var(--white);

    font-size:
        20px;

    margin-bottom:
        8px;
}


.logout-box p {

    color:
        var(--muted);

    font-size:
        13px;

    line-height:
        1.6;

    margin-bottom:
        22px;
}


.logout-actions {

    display:
        flex;

    justify-content:
        center;

    gap:
        10px;
}


.logout-actions button {

    border:
        none;

    padding:
        11px 16px;

    border-radius:
        12px;

    cursor:
        pointer;

    font-family:
        inherit;

    font-weight:
        700;

    transition:
        .2s ease;
}


.logout-cancel {

    background:
        rgba(255,255,255,.06);

    color:
        var(--text);
}


.logout-cancel:hover {

    background:
        rgba(255,255,255,.10);
}


.logout-confirm {

    background:
        linear-gradient(
            145deg,
            #35d477,
            #087f45
        );

    color:
        #041b12;
}


.logout-confirm:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 8px 20px
        rgba(18,168,91,.20);
}



.loading {

    position:
        fixed;

    inset:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        #041b12;

    z-index:
        1000;

    pointer-events:
        none;

    opacity:
        0;

    transition:
        .2s ease;
}


.loading.show {

    opacity:
        1;
}


.loading-content {

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    gap:
        12px;
}


.loading-logo {

    width:
        48px;

    height:
        48px;

    border-radius:
        15px;

    background:
        linear-gradient(
            145deg,
            #063d29
        );

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        white;

    font-size:
        20px;

    font-weight:
        900;

    animation:
        pulse 1.2s infinite ease-in-out;
}


@keyframes pulse {

    0%, 100% {

        transform:
            scale(1);

        opacity:
            .7;
    }

    50% {

        transform:
            scale(1.08);

        opacity:
            1;
    }
}



#youtubeGlobalPlayer {

    position:
        fixed;

    width:
        1px;

    height:
        1px;

    left:
        -100px;

    top:
        -100px;

    opacity:
        0;

    pointer-events:
        none;

    overflow:
        hidden;
}

@media (max-width: 1100px) {

    .global-media-info {

        min-width:
            170px;
    }

    .flowtime-info {

        min-width:
            135px;
    }

    .music-volume {

        min-width:
            65px;
    }

    .volume-slider {

        width:
            55px;
    }
}


@media (max-width: 900px) {

    .sidebar {

        width:
            70px;

        left:
            10px;

        top:
            10px;

        bottom:
            10px;

        padding:
            15px 8px;
    }


    .logo {

        width:
            44px;

        height:
            44px;
    }


    .nav-item {

        width:
            46px;

        height:
            46px;
    }


    .navigation {

        gap:
            7px;
    }


    .app {

        padding-left:
            90px;
    }


    .global-player {

        left:
            90px;

        right:
            10px;
    }


    .global-media-info {

        min-width:
            145px;
    }


    .flowtime-info {

        min-width:
            110px;
    }


    .flowtime-center {

        gap:
            5px;
    }


    .global-flow-time {

        font-size:
            21px;
    }
}


@media (max-width: 700px) {

    .global-player {

        height:
            110px;
    }


    .global-media-info {

        min-width:
            125px;
    }


    .flowtime-info {

        min-width:
            95px;
    }


    .flowtime-cover {

        width:
            40px;

        height:
            40px;

        font-size:
            17px;
    }


    .player-cover {

        width:
            40px;

        height:
            40px;

        font-size:
            18px;
    }


    .player-title {

        font-size:
            11px;

        max-width:
            110px;
    }


    .player-artist {

        font-size:
            9px;

        max-width:
            110px;
    }


    .flowtime-text strong {

        font-size:
            11px;
    }


    .flowtime-text span {

        font-size:
            8px;
    }


    .global-flow-time {

        font-size:
            19px;
    }


    .music-volume {

        display:
            none;
    }
}


@media (max-width: 650px) {

    body {

        overflow:
            auto;
    }


    .sidebar {

        left:
            8px;

        top:
            8px;

        bottom:
            auto;

        height:
            70px;

        width:
            calc(100% - 16px);

        min-height:
            auto;

        flex-direction:
            row;

        padding:
            8px 12px;

        border-radius:
            20px;
    }


    .navigation {

        flex-direction:
            row;
    }


    .sidebar > div:last-child {

        display:
            flex;
    }


    .nav-item.active::before {

        left:
            50%;

        top:
            auto;

        bottom:
            -8px;

        width:
            20px;

        height:
            3px;

        transform:
            translateX(-50%);
    }


    .app {

        padding-left:
            0;

        padding-top:
            88px;
    }


    .page-container {

        padding:
            8px 8px 115px;

        height:
            calc(100vh - 78px);
    }


    .global-player {

        left:
            8px;

        right:
            8px;

        bottom:
            8px;

        height:
            150px;

        flex-direction:
            column;

        padding:
            9px 12px;

        gap:
            5px;
    }


    .global-section {

        width:
            100%;

        flex:
            1;
    }


    .global-divider {

        width:
            100%;

        height:
            1px;

        margin:
            0;
    }


    .music-global {

        display:
            grid;

        grid-template-columns:
            auto 1fr auto;

        gap:
            8px;
    }


    .global-media-info {

        min-width:
            0;
    }


    .music-controls {

        min-width:
            0;
    }


    .flowtime-global {

        display:
            grid;

        grid-template-columns:
            auto 1fr auto;

        gap:
            8px;
    }


    .flowtime-info {

        min-width:
            0;
    }


    .flowtime-center {

        min-width:
            0;
    }


    .flowtime-controls {

        gap:
            4px;
    }


    .flowtime-cover {

        width:
            38px;

        height:
            38px;
    }


    .global-flow-time {

        font-size:
            18px;
    }


    .tooltip {

        display:
            none;
    }
}

</style>

</head>


<body>


<!-- =======================================================
     BACKGROUND
======================================================= -->

<div class="background">

    <video
        autoplay
        muted
        loop
        playsinline
    >

        <source
            src="data/bggg.mp4"
            type="video/mp4"
        >

    </video>


    <div class="background-overlay"></div>

</div>



<!-- =======================================================
     SIDEBAR
======================================================= -->

<aside class="sidebar">


    <div class="logo">

        <img
            src="data/descarga.png"
            alt="PEAK"

            onerror="
                this.style.display='none';
                this.parentElement.innerHTML='P';
            "
        >

    </div>



    <nav class="navigation">


        <!--
            IMPORTANTE:

            Estos ahora son BUTTONS y NO <a href="">.

            De esta manera el navegador jamás puede abrir
            calendar.php, notes.php, etc. directamente.

            JavaScript es el único responsable de cambiar
            el contenido del iframe.
        -->


        <button
            type="button"
            class="nav-item active"
            data-page="dashboard.php"
        >

            ⌂

            <span class="tooltip">
                Dashboard
            </span>

        </button>



        <button
            type="button"
            class="nav-item"
            data-page="calendar.php"
        >

            ◫

            <span class="tooltip">
                Calendario
            </span>

        </button>



        <button
            type="button"
            class="nav-item"
            data-page="flowtime.php"
        >

            ◷

            <span class="tooltip">
                Flowtime
            </span>

        </button>



        <button
            type="button"
            class="nav-item"
            data-page="notes.php"
        >

            ▤

            <span class="tooltip">
                Notas
            </span>

        </button>



        <button
            type="button"
            class="nav-item"
            data-page="music.php"
        >

            ♪

            <span class="tooltip">
                Música
            </span>

        </button>



        <button
            type="button"
            class="nav-item"
            data-page="IA_HUB.php"
        >

            ◉

            <span class="tooltip">
                IA
            </span>

        </button>


    </nav>



    <div>


        <button
            type="button"
            class="nav-item logout"
            id="logoutButton"
        >

            ↪

            <span class="tooltip">
                Cerrar sesión
            </span>

        </button>


    </div>


</aside>



<!-- =======================================================
     APP / IFRAME
======================================================= -->

<div class="app">

    <main class="page-container">

        <iframe
            id="pageFrame"
            src="pages/dashboard.php?embedded=1"
            title="PEAK"
            allow="autoplay; encrypted-media"
        ></iframe>

    </main>

</div>



<!-- =======================================================
     PLAYER GLOBAL
======================================================= -->

<div class="global-player">


    <!-- ===================================================
         MÚSICA
    ==================================================== -->

    <section class="global-section music-global">


        <div class="global-media-info">


            <div
                class="player-cover"
                id="playerCover"
            >
                ♪
            </div>


            <div class="player-info">

                <strong
                    class="player-title"
                    id="playerTitle"
                >
                    Music
                </strong>


                <span
                    class="player-artist"
                    id="playerArtist"
                >
                    Selecciona una canción
                </span>

            </div>


        </div>



        <div class="music-controls">


            <div class="player-buttons">


                <button
                    class="player-button"
                    id="previousButton"
                    type="button"
                    title="Anterior"
                >
                    ‹
                </button>


                <button
                    class="player-button main"
                    id="playButton"
                    type="button"
                    title="Reproducir"
                >
                    ▶
                </button>


                <button
                    class="player-button"
                    id="nextButton"
                    type="button"
                    title="Siguiente"
                >
                    ›
                </button>


            </div>



            <div class="progress-area">


                <span
                    class="time"
                    id="currentTime"
                >
                    0:00
                </span>


                <input
                    type="range"
                    class="progress-bar"
                    id="progressBar"
                    min="0"
                    max="100"
                    value="0"
                    step="0.1"
                >


                <span
                    class="time"
                    id="duration"
                >
                    0:00
                </span>


            </div>


        </div>



        <div class="music-volume">


            <span
                class="volume-icon"
                id="volumeIcon"
            >
                🔊
            </span>


            <input
                type="range"
                class="volume-slider"
                id="volumeSlider"
                min="0"
                max="1"
                value="0.8"
                step="0.01"
            >


        </div>


    </section>



    <div class="global-divider"></div>



    <!-- ===================================================
         FLOWTIME
    ==================================================== -->

    <section class="global-section flowtime-global">


        <div class="flowtime-info">


            <div class="flowtime-cover">
                ⏱
            </div>


            <div class="flowtime-text">


                <strong id="globalFlowTitle">
                    Flowtime
                </strong>


                <span id="globalFlowState">
                    Inactivo
                </span>


            </div>


        </div>



        <div class="flowtime-center">


            <div
                class="global-flow-time"
                id="globalFlowTime"
            >
                65:00
            </div>


            <div
                class="global-flow-mode"
                id="globalFlowMode"
            >
                FLOW
            </div>


        </div>



        <div class="flowtime-controls">


            <button
                class="player-button main"
                id="globalFlowPlay"
                type="button"
                title="Iniciar / continuar Flowtime"
            >
                ▶
            </button>


            <button
                class="player-button"
                id="globalFlowPause"
                type="button"
                title="Pausar Flowtime"
            >
                ⏸
            </button>


            <button
                class="player-button"
                id="globalFlowStop"
                type="button"
                title="Detener Flowtime"
            >
                ■
            </button>


        </div>


    </section>


</div>



<!-- =======================================================
     YOUTUBE
======================================================= -->

<div id="youtubeGlobalPlayer"></div>



<!-- =======================================================
     LOGOUT MODAL
======================================================= -->

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
            ¿Estás seguro de que quieres cerrar
            tu sesión de PEAK?
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



<!-- =======================================================
     LOADING
======================================================= -->

<div
    class="loading"
    id="loading"
>


    <div class="loading-content">


        <div class="loading-logo">
            P
        </div>


    </div>


</div>



<script src="https://www.youtube.com/iframe_api"></script>


<script>


const pageFrame =
    document.getElementById("pageFrame");


const navItems =
    document.querySelectorAll(
        ".nav-item[data-page]"
    );


const loading =
    document.getElementById("loading");



const playButton =
    document.getElementById("playButton");


const previousButton =
    document.getElementById("previousButton");


const nextButton =
    document.getElementById("nextButton");


const progressBar =
    document.getElementById("progressBar");


const currentTime =
    document.getElementById("currentTime");


const duration =
    document.getElementById("duration");


const volumeSlider =
    document.getElementById("volumeSlider");


const volumeIcon =
    document.getElementById("volumeIcon");


const playerTitle =
    document.getElementById("playerTitle");


const playerArtist =
    document.getElementById("playerArtist");


const playerCover =
    document.getElementById("playerCover");



let globalYouTubePlayer = null;

let globalYouTubeReady = false;

let currentVideoId = "";

let currentSongIndex = -1;

let playlist = [];

let isPlaying = false;

let playerVolume = 80;




function onYouTubeIframeAPIReady() {

    try {

        globalYouTubePlayer =
            new YT.Player(
                "youtubeGlobalPlayer",
                {

                    width:
                        "1",

                    height:
                        "1",

                    videoId:
                        "",

                    playerVars: {

                        autoplay:
                            0,

                        controls:
                            0,

                        rel:
                            0,

                        modestbranding:
                            1,

                        playsinline:
                            1,

                        enablejsapi:
                            1
                    },

                    events: {

                        onReady:
                            function(event) {

                                globalYouTubeReady =
                                    true;

                                event.target.setVolume(
                                    playerVolume
                                );

                                updatePlayButton();
                            },


                        onStateChange:
                            handleYouTubeState,


                        onError:
                            function(event) {

                                console.log(
                                    "Error de YouTube:",
                                    event.data
                                );

                            }

                    }

                }
            );

    }

    catch (error) {

        console.error(
            "No se pudo iniciar YouTube:",
            error
        );

    }

}



function handleYouTubeState(event) {

    if (!globalYouTubePlayer) {
        return;
    }


    if (
        event.data ===
        YT.PlayerState.PLAYING
    ) {

        isPlaying =
            true;

        updatePlayButton();

        startProgressUpdater();

    }


    else if (
        event.data ===
        YT.PlayerState.PAUSED
    ) {

        isPlaying =
            false;

        updatePlayButton();

        stopProgressUpdater();

    }


    else if (
        event.data ===
        YT.PlayerState.ENDED
    ) {

        isPlaying =
            false;

        updatePlayButton();

        stopProgressUpdater();

        playNextSong();

    }


    else if (
        event.data ===
        YT.PlayerState.CUED
    ) {

        updateDuration();

    }

}



function formatTime(seconds) {

    if (
        !Number.isFinite(seconds) ||
        seconds < 0
    ) {

        return "0:00";

    }


    const minutes =
        Math.floor(
            seconds / 60
        );


    const remaining =
        Math.floor(
            seconds % 60
        );


    return (
        minutes +
        ":" +
        String(
            remaining
        ).padStart(
            2,
            "0"
        )
    );

}



function updatePlayButton() {

    if (isPlaying) {

        playButton.innerHTML =
            "Ⅱ";

        playButton.title =
            "Pausar";

    }

    else {

        playButton.innerHTML =
            "▶";

        playButton.title =
            "Reproducir";

    }

}



function playGlobalYouTube(
    videoId,
    title = "Music",
    channel = "YouTube",
    index = -1
) {

    if (!videoId) {
        return;
    }


    currentVideoId =
        videoId;


    if (
        typeof index === "number" &&
        index >= 0
    ) {

        currentSongIndex =
            index;

    }


    playerTitle.textContent =
        title ||
        "Music";


    playerArtist.textContent =
        channel ||
        "YouTube";


    playerCover.style.backgroundImage =
        "";


    if (
        globalYouTubePlayer &&
        globalYouTubeReady
    ) {

        globalYouTubePlayer.loadVideoById(
            videoId
        );


        globalYouTubePlayer.setVolume(
            playerVolume
        );


        isPlaying =
            true;


        updatePlayButton();

        startProgressUpdater();

    }

}



playButton.addEventListener(
    "click",
    function() {

        if (
            !globalYouTubePlayer ||
            !globalYouTubeReady
        ) {

            return;

        }


        if (!currentVideoId) {

            if (
                playlist.length > 0
            ) {

                playSongFromPlaylist(
                    0
                );

            }

            return;

        }


        if (isPlaying) {

            globalYouTubePlayer.pauseVideo();

        }

        else {

            globalYouTubePlayer.playVideo();

        }

    }
);



function playSongFromPlaylist(index) {

    if (
        !playlist.length ||
        !playlist[index]
    ) {

        return;

    }


    const song =
        playlist[index];


    currentSongIndex =
        index;


    playGlobalYouTube(
        song.videoId,
        song.title,
        song.channel,
        index
    );

}


previousButton.addEventListener(
    "click",
    function() {

        if (!playlist.length) {
            return;
        }


        let index =
            currentSongIndex - 1;


        if (index < 0) {

            index =
                playlist.length - 1;

        }


        playSongFromPlaylist(
            index
        );

    }
);


nextButton.addEventListener(
    "click",
    function() {

        playNextSong();

    }
);


function playNextSong() {

    if (!playlist.length) {
        return;
    }


    let index =
        currentSongIndex + 1;


    if (
        index >= playlist.length ||
        index < 0
    ) {

        index =
            0;

    }


    playSongFromPlaylist(
        index
    );

}



let progressInterval =
    null;


function startProgressUpdater() {

    stopProgressUpdater();


    progressInterval =
        setInterval(
            function() {

                updateProgress();

            },
            500
        );

}


function stopProgressUpdater() {

    if (progressInterval) {

        clearInterval(
            progressInterval
        );

        progressInterval =
            null;

    }

}


function updateProgress() {

    if (
        !globalYouTubePlayer ||
        !globalYouTubeReady
    ) {

        return;

    }


    let current =
        0;

    let total =
        0;


    try {

        current =
            globalYouTubePlayer.getCurrentTime();


        total =
            globalYouTubePlayer.getDuration();

    }

    catch (error) {

        return;

    }


    if (
        !Number.isFinite(total) ||
        total <= 0
    ) {

        return;

    }


    const percentage =
        (
            current /
            total
        ) * 100;


    progressBar.value =
        percentage || 0;


    currentTime.textContent =
        formatTime(
            current
        );


    duration.textContent =
        formatTime(
            total
        );

}


function updateDuration() {

    if (
        !globalYouTubePlayer ||
        !globalYouTubeReady
    ) {

        return;

    }


    setTimeout(
        function() {

            try {

                const total =
                    globalYouTubePlayer.getDuration();


                if (
                    Number.isFinite(total) &&
                    total > 0
                ) {

                    duration.textContent =
                        formatTime(
                            total
                        );

                }

            }

            catch (error) {}

        },
        500
    );

}



progressBar.addEventListener(
    "input",
    function() {

        if (
            !globalYouTubePlayer ||
            !globalYouTubeReady
        ) {

            return;

        }


        let total =
            0;


        try {

            total =
                globalYouTubePlayer.getDuration();

        }

        catch (error) {

            return;

        }


        if (
            !Number.isFinite(total) ||
            total <= 0
        ) {

            return;

        }


        const newTime =
            (
                Number(this.value) /
                100
            ) *
            total;


        try {

            globalYouTubePlayer.seekTo(
                newTime,
                true
            );

        }

        catch (error) {}

    }
);

volumeSlider.addEventListener(
    "input",
    function() {

        playerVolume =
            Number(
                this.value
            ) * 100;


        if (
            globalYouTubePlayer &&
            globalYouTubeReady
        ) {

            globalYouTubePlayer.setVolume(
                playerVolume
            );

        }


        updateVolumeIcon();

    }
);


function updateVolumeIcon() {

    const volume =
        Number(
            volumeSlider.value
        );


    if (volume === 0) {

        volumeIcon.textContent =
            "🔇";

    }


    else if (volume < .5) {

        volumeIcon.textContent =
            "🔉";

    }


    else {

        volumeIcon.textContent =
            "🔊";

    }

}


volumeSlider.value =
    .8;

playerVolume =
    80;

updateVolumeIcon();




const FLOW_STORAGE_KEY =
    "peakFlowtime";


let globalFlowInterval =
    null;


const globalFlowTime =
    document.getElementById(
        "globalFlowTime"
    );


const globalFlowTitle =
    document.getElementById(
        "globalFlowTitle"
    );


const globalFlowState =
    document.getElementById(
        "globalFlowState"
    );


const globalFlowMode =
    document.getElementById(
        "globalFlowMode"
    );


const globalFlowPlay =
    document.getElementById(
        "globalFlowPlay"
    );


const globalFlowPause =
    document.getElementById(
        "globalFlowPause"
    );


const globalFlowStop =
    document.getElementById(
        "globalFlowStop"
    );


const globalFlowDefaults = {

    modo:
        "flow",

    activo:
        false,

    pausado:
        false,

    segundosRestantes:
        65 * 60,

    inicioTimestamp:
        null,

    pausaTimestamp:
        null,

    autoRest:
        true,

    flowMinutes:
        65,

    restMinutes:
        20
};



function getGlobalFlowState() {

    try {

        const saved =
            localStorage.getItem(
                FLOW_STORAGE_KEY
            );


        if (!saved) {

            return {
                ...globalFlowDefaults
            };

        }


        return {
            ...globalFlowDefaults,
            ...JSON.parse(saved)
        };

    }

    catch (error) {

        return {
            ...globalFlowDefaults
        };

    }

}



function saveGlobalFlowState(state) {

    try {

        localStorage.setItem(
            FLOW_STORAGE_KEY,
            JSON.stringify(state)
        );

    }

    catch (error) {}

}

function globalFlowFormat(seconds) {

    seconds =
        Math.max(
            0,
            Math.floor(seconds)
        );


    const minutes =
        Math.floor(
            seconds / 60
        );


    const remaining =
        seconds % 60;


    return (
        String(minutes).padStart(2, "0") +
        ":" +
        String(remaining).padStart(2, "0")
    );

}


function calculateGlobalFlow(state) {

    if (
        !state.activo ||
        state.pausado ||
        !state.inicioTimestamp
    ) {

        return state;

    }


    const elapsed =
        Math.floor(
            (
                Date.now() -
                state.inicioTimestamp
            ) / 1000
        );


    const initial =
        state.modo === "flow"
            ? state.flowMinutes * 60
            : state.restMinutes * 60;


    state.segundosRestantes =
        Math.max(
            0,
            initial - elapsed
        );


    if (
        state.segundosRestantes <= 0
    ) {



        if (
            state.modo === "flow" &&
            state.autoRest
        ) {

            state.modo =
                "rest";

            state.activo =
                true;

            state.pausado =
                false;

            state.segundosRestantes =
                state.restMinutes * 60;

            state.inicioTimestamp =
                Date.now();

            state.pausaTimestamp =
                null;

        }


        else if (
            state.modo === "rest"
        ) {

            state.modo =
                "flow";

            state.activo =
                true;

            state.pausado =
                false;

            state.segundosRestantes =
                state.flowMinutes * 60;

            state.inicioTimestamp =
                Date.now();

            state.pausaTimestamp =
                null;

        }



        else {

            state.activo =
                false;

            state.pausado =
                false;

            state.segundosRestantes =
                state.flowMinutes * 60;

            state.inicioTimestamp =
                null;

            state.pausaTimestamp =
                null;

        }

    }


    return state;

}



function updateGlobalFlowtime(
    sendToFrame = true
) {

    let state =
        getGlobalFlowState();


    state =
        calculateGlobalFlow(
            state
        );


    saveGlobalFlowState(
        state
    );


    if (globalFlowTime) {

        globalFlowTime.textContent =
            globalFlowFormat(
                state.segundosRestantes
            );

    }


    if (globalFlowMode) {

        globalFlowMode.textContent =
            state.modo === "flow"
                ? "FLOW"
                : "DESCANSO";

    }


    if (globalFlowTitle) {

        globalFlowTitle.textContent =
            state.modo === "flow"
                ? "Flowtime"
                : "Descanso ligero";

    }


    if (globalFlowState) {

        globalFlowState.textContent =
            !state.activo

                ? "Inactivo"

                : (

                    state.pausado

                        ? (
                            state.modo === "flow"
                                ? "Flow pausado"
                                : "Descanso pausado"
                        )

                        : (
                            state.modo === "flow"
                                ? "Concentración activa"
                                : "Descanso activo"
                        )

                );

    }


    if (globalFlowPlay) {

        globalFlowPlay.textContent =
            !state.activo ||
            state.pausado
                ? "▶"
                : "⏸";


        globalFlowPlay.title =
            !state.activo ||
            state.pausado

                ? "Iniciar / continuar Flowtime"

                : "Pausar Flowtime";

    }


    if (globalFlowPause) {

        globalFlowPause.style.display =
            state.activo &&
            !state.pausado

                ? "inline-flex"

                : "none";

    }


    if (globalFlowStop) {

        globalFlowStop.style.display =
            state.activo

                ? "inline-flex"

                : "none";

    }



    if (sendToFrame) {

        try {

            if (
                pageFrame &&
                pageFrame.contentWindow
            ) {

                pageFrame.contentWindow.postMessage(
                    {
                        type:
                            "PEAK_FLOWTIME_STATE",

                        state:
                            state
                    },
                    "*"
                );

            }

        }

        catch (error) {}

    }


    return state;

}


function startGlobalFlowInterval() {

    if (globalFlowInterval) {

        clearInterval(
            globalFlowInterval
        );

    }


    globalFlowInterval =
        setInterval(
            function() {

                updateGlobalFlowtime(
                    true
                );

            },
            500
        );


    updateGlobalFlowtime(
        true
    );

}


function globalFlowCommand(
    command,
    payload = {}
) {

    let state =
        getGlobalFlowState();


    state =
        calculateGlobalFlow(
            state
        );



    if (
        command ===
        "settings"
    ) {

        const flow =
            Number(
                payload.flowMinutes
            );


        const rest =
            Number(
                payload.restMinutes
            );


        if (
            !Number.isFinite(flow) ||
            flow < 1 ||
            !Number.isFinite(rest) ||
            rest < 1
        ) {

            updateGlobalFlowtime(
                true
            );

            return;

        }


        state.flowMinutes =
            Math.min(
                Math.floor(flow),
                180
            );


        state.restMinutes =
            Math.min(
                Math.floor(rest),
                120
            );


        state.autoRest =
            !!payload.autoRest;


        if (!state.activo) {

            state.modo =
                "flow";

            state.segundosRestantes =
                state.flowMinutes * 60;

        }

    }



    if (
        command ===
        "start"
    ) {

        if (
            state.segundosRestantes <= 0
        ) {

            state.segundosRestantes =
                state.flowMinutes * 60;

        }


        state.activo =
            true;

        state.pausado =
            false;


        state.inicioTimestamp =
            Date.now() -

            Math.max(
                0,

                (
                    state.modo === "flow"
                        ? state.flowMinutes * 60
                        : state.restMinutes * 60
                ) -

                state.segundosRestantes

            ) * 1000;


        state.pausaTimestamp =
            null;

    }


    if (
        command ===
        "pause"
    ) {

        state =
            calculateGlobalFlow(
                state
            );


        state.pausado =
            true;

        state.activo =
            true;

        state.pausaTimestamp =
            Date.now();

    }


    if (
        command ===
        "resume"
    ) {

        const initial =
            state.modo === "flow"
                ? state.flowMinutes * 60
                : state.restMinutes * 60;


        state.pausado =
            false;

        state.activo =
            true;


        state.inicioTimestamp =
            Date.now() -

            Math.max(
                0,
                initial -
                state.segundosRestantes
            ) * 1000;


        state.pausaTimestamp =
            null;

    }


    if (
        command ===
        "stop"
    ) {

        state.activo =
            false;

        state.pausado =
            false;

        state.modo =
            "flow";

        state.inicioTimestamp =
            null;

        state.pausaTimestamp =
            null;

        state.segundosRestantes =
            state.flowMinutes * 60;

    }


    if (
        command ===
        "skip"
    ) {

        if (
            state.modo ===
            "rest"
        ) {

            state.modo =
                "flow";

            state.activo =
                true;

            state.pausado =
                false;

            state.segundosRestantes =
                state.flowMinutes * 60;

            state.inicioTimestamp =
                Date.now();

            state.pausaTimestamp =
                null;

        }

    }


    saveGlobalFlowState(
        state
    );


    startGlobalFlowInterval();

}



globalFlowPlay?.addEventListener(
    "click",
    function() {

        const state =
            getGlobalFlowState();


        globalFlowCommand(

            !state.activo

                ? "start"

                : (
                    state.pausado
                        ? "resume"
                        : "pause"
                )

        );

    }
);


globalFlowPause?.addEventListener(
    "click",
    function() {

        globalFlowCommand(
            "pause"
        );

    }
);


globalFlowStop?.addEventListener(
    "click",
    function() {

        globalFlowCommand(
            "stop"
        );

    }
);



window.addEventListener(
    "message",
    function(event) {

        const data =
            event.data;


        if (!data) {
            return;
        }


        if (
            data.type ===
            "PEAK_FLOWTIME_COMMAND"
        ) {

            globalFlowCommand(
                data.command,
                data
            );

            return;

        }


        if (
            data.type ===
            "PEAK_FLOWTIME_READY"
        ) {

            updateGlobalFlowtime(
                true
            );

        }

    }
);


window.addEventListener(
    "storage",
    function(event) {

        if (
            event.key ===
            FLOW_STORAGE_KEY
        ) {

            updateGlobalFlowtime(
                true
            );

        }

    }
);


document.addEventListener(
    "visibilitychange",
    function() {

        if (!document.hidden) {

            updateGlobalFlowtime(
                true
            );

        }

    }
);


startGlobalFlowInterval();





const allowedPages = new Set([

    "dashboard.php",

    "calendar.php",

    "flowtime.php",

    "notes.php",

    "music.php",

    "IA_HUB.php"

]);



function normalizePage(page) {

    if (
        typeof page !== "string"
    ) {

        return null;

    }



    page =
        page
            .split("/")
            .pop()
            .split("?")[0];


    if (
        !allowedPages.has(
            page
        )
    ) {

        return null;

    }


    return page;

}



function loadPage(
    page,
    clickedItem = null
) {

    const safePage =
        normalizePage(
            page
        );


    if (!safePage) {

        console.warn(
            "Página no permitida:",
            page
        );

        return;

    }



    loading.classList.add(
        "show"
    );


    const targetUrl =
        "pages/" +
        safePage +
        "?embedded=1";

    const currentSrc =
        pageFrame.getAttribute(
            "src"
        ) || "";


    const currentPage =
        currentSrc
            .split("/")
            .pop()
            .split("?")[0];


    if (
        currentPage !==
        safePage
    ) {

        pageFrame.src =
            targetUrl;

    }



    navItems.forEach(
        function(item) {

            item.classList.remove(
                "active"
            );

        }
    );


    if (clickedItem) {

        clickedItem.classList.add(
            "active"
        );

    }

    else {

        navItems.forEach(
            function(item) {

                if (
                    item.dataset.page ===
                    safePage
                ) {

                    item.classList.add(
                        "active"
                    );

                }

            }
        );

    }



    try {

        sessionStorage.setItem(
            "peak_current_page",
            safePage
        );

    }

    catch (error) {}

}

navItems.forEach(
    function(item) {

        item.addEventListener(
            "click",
            function(event) {

                

                event.preventDefault();

                event.stopPropagation();


                const page =
                    this.dataset.page;


                loadPage(
                    page,
                    this
                );

            },
            false
        );

    }
);


pageFrame.addEventListener(
    "load",
    function() {

        loading.classList.remove(
            "show"
        );


        try {

            pageFrame.contentWindow.postMessage(
                {
                    type:
                        "PEAK_EMBEDDED"
                },
                "*"
            );


            updateGlobalFlowtime(
                true
            );

        }

        catch (error) {}

    }
);


window.addEventListener(
    "message",
    function(event) {

        const data =
            event.data;


        if (!data) {
            return;
        }



        if (
            data.type ===
            "PEAK_SET_PLAYLIST"
        ) {

            if (
                Array.isArray(
                    data.playlist
                )
            ) {

                playlist =
                    data.playlist;

            }

        }


        if (
            data.type ===
            "PEAK_PLAY_YOUTUBE"
        ) {

            if (!data.videoId) {
                return;
            }


            playGlobalYouTube(

                data.videoId,

                data.title ||
                    "Music",

                data.channel ||
                    "YouTube",

                typeof data.index ===
                    "number"

                    ? data.index

                    : -1

            );

        }


        if (
            data.type ===
            "PEAK_NAVIGATE"
        ) {

            const safePage =
                normalizePage(
                    data.page
                );


            if (!safePage) {

                return;

            }


            const target =
                Array.from(
                    navItems
                ).find(
                    function(item) {

                        return (
                            item.dataset.page ===
                            safePage
                        );

                    }
                );


            loadPage(
                safePage,
                target || null
            );

        }

    }
);



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


logoutButton.addEventListener(
    "click",
    function(event) {

        event.preventDefault();

        event.stopPropagation();


        logoutModal.classList.add(
            "show"
        );

    }
);


cancelLogout.addEventListener(
    "click",
    function() {

        logoutModal.classList.remove(
            "show"
        );

    }
);


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


confirmLogout.addEventListener(
    "click",
    async function() {

        const formData =
            new FormData();


        formData.append(
            "logout",
            "1"
        );


        try {

            const response =
                await fetch(
                    "index.php",
                    {
                        method:
                            "POST",

                        body:
                            formData
                    }
                );


            if (response.ok) {


                try {

                    sessionStorage.removeItem(
                        "peak_current_page"
                    );

                }

                catch (error) {}


                window.location.href =
                    "pages/login.php";

            }

        }

        catch (error) {

            console.error(
                "Error al cerrar sesión:",
                error
            );

        }

    }
);

try {

    const savedPage =
        sessionStorage.getItem(
            "peak_current_page"
        );


    const safeSavedPage =
        normalizePage(
            savedPage
        );


    if (safeSavedPage) {

        const target =
            Array.from(
                navItems
            ).find(
                function(item) {

                    return (
                        item.dataset.page ===
                        safeSavedPage
                    );

                }
            );


        loadPage(
            safeSavedPage,
            target || null
        );

    }

}

catch (error) {

    console.error(
        "No se pudo restaurar la página:",
        error
    );

}



updatePlayButton();

updateVolumeIcon();

updateGlobalFlowtime(
    true
);

</script>














</body>





</html>