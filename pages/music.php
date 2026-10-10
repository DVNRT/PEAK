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

require_once __DIR__ . "/../backend/config/database.php";
require_once __DIR__ . "/../backend/config/youtube.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = intval($_SESSION["usuario_id"]);
$nombreUsuario = $_SESSION["nombre"] ?? "";



$embedded =
    isset($_GET["embedded"]) &&
    $_GET["embedded"] === "1";


function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}




if (isset($_GET["youtube_search"])) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    $query =
        trim($_GET["youtube_search"]);


    if ($query === "") {

        echo json_encode([
            "success" => false,
            "message" => "Escribe algo para buscar."
        ]);

        exit();
    }


    $maxResults = 12;


    $url =
        "https://www.googleapis.com/youtube/v3/search?"
        .
        http_build_query([

            "part" => "snippet",

            "q" => $query,

            "type" => "video",

            "videoEmbeddable" => "true",

            "maxResults" => $maxResults,

            "regionCode" => "DO",

            "relevanceLanguage" => "es",

            "key" => YOUTUBE_API_KEY

        ]);


    $ch = curl_init();


    curl_setopt_array(
        $ch,
        [

            CURLOPT_URL =>
                $url,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_TIMEOUT =>
                15,

            CURLOPT_SSL_VERIFYPEER =>
                true

        ]
    );


    $response =
        curl_exec($ch);


    $curl_error =
        curl_error($ch);


    $http_code =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    curl_close($ch);


    if (
        $response === false ||
        $curl_error
    ) {

        echo json_encode([

            "success" => false,

            "message" =>
                "No se pudo conectar con YouTube."

        ]);

        exit();
    }


    $data =
        json_decode(
            $response,
            true
        );


    if (
        $http_code !== 200 ||
        isset($data["error"])
    ) {

        $message =
            "Ocurrió un error al consultar YouTube.";


        if (
            isset(
                $data["error"]["message"]
            )
        ) {

            $message =
                $data["error"]["message"];
        }


        echo json_encode([

            "success" => false,

            "message" => $message

        ]);

        exit();
    }


    $results = [];


    foreach (
        $data["items"] ?? []
        as $item
    ) {

        if (
            !isset(
                $item["id"]["videoId"]
            )
        ) {

            continue;
        }


        $results[] = [

            "videoId" =>
                $item["id"]["videoId"],

            "title" =>
                $item["snippet"]["title"]
                ?? "",

            "channel" =>
                $item["snippet"]["channelTitle"]
                ?? "",

            "description" =>
                $item["snippet"]["description"]
                ?? "",

            "thumbnail" =>

                $item["snippet"]["thumbnails"]["high"]["url"]

                ??

                $item["snippet"]["thumbnails"]["medium"]["url"]

                ??

                $item["snippet"]["thumbnails"]["default"]["url"]

                ??

                ""

        ];
    }


    echo json_encode([

        "success" => true,

        "results" => $results

    ]);

    exit();
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

<title>𝐌𝐔́𝐒𝐈𝐂𝐀</title>



<style>

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

    transform:
        translateY(-1px);

    box-shadow:
        0 0 18px
        rgba(37,217,111,.12);
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


body.embedded {

    min-height: 100%;

    overflow-x: hidden;
}


body.embedded .sidebar {

    display: none;
}


body.embedded .main {

    width: 100%;

    margin-left: 0;

    padding:
        30px
        40px
        40px
        20px;
}


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



.glass-card {

    background:
        linear-gradient(
            145deg,
            rgba(5,45,28,.29),
            rgba(2,31,20,.30)
        );

    border:
        1px solid var(--border);

    border-radius: 23px;

    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(22px);

    box-shadow: var(--shadow);

    min-width: 0;
}
    

.music-search {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 7px;

    margin-bottom: 20px;
}


.search-input {

    flex: 1;

    min-width: 0;

    height: 48px;

    padding:
        0 14px;

    border: none;

    outline: none;

    border-radius: 13px;

    color: var(--white);

    background:
        rgba(5,52,32,.55);

    font-family: inherit;

    font-size: 13px;
}


.search-input::placeholder {

    color: #7da88d;
}


.search-input:focus {

    box-shadow:
        0 0 0 3px
        rgba(53,212,119,.07);
}


.search-button {

    height: 48px;

    padding:
        0 20px;

    border: none;

    border-radius: 13px;

    color: white;

    background:
        linear-gradient(
            135deg,
            #25dc75,
            #07934e
        );

    font-family: inherit;

    font-size: 13px;

    font-weight: 800;

    cursor: pointer;

    white-space: nowrap;

    box-shadow:
        0 8px 25px
        rgba(16,190,91,.22);

    transition: .25s ease;
}


.search-button:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 32px
        rgba(16,190,91,.32);
}



.player-card {

    display: none;

    padding: 17px;

    margin-bottom: 20px;
}


.player-card.show {

    display: block;
}


.player-wrapper {

    width: 100%;

    aspect-ratio: 16 / 9;

    border-radius: 18px;

    overflow: hidden;

    background: #020b07;
}


#youtubePlayer {

    width: 100%;

    height: 100%;
}


.player-info {

    padding:
        14px
        3px
        2px;
}


.player-title {

    color: var(--white);

    font-family: Minecrafter;

    font-weight: lighter;

    font-size: 22px;

    line-height: 1.35;

    word-break: break-word;
}


.player-channel {

    color: var(--muted);

    font-size: 12px;

    margin-top: 7px;
}

body.embedded .player-card {

    display: none !important;
}


.results-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 13px;
}


.results-title {

    color: var(--white);

    font-family: Minecrafter;

    font-weight: lighter;

    font-size: 22px;
}


.results-status {

    color: var(--muted);

    font-size: 11px;
}


.results-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0,1fr));

    gap: 15px;
}


.video-card {

    overflow: hidden;

    cursor: pointer;

    transition: .25s ease;
}


.video-card:hover {

    transform:
        translateY(-3px);

    border-color:
        rgba(129,235,153,.35);

    box-shadow:
        0 18px 50px
        rgba(0,0,0,.30);
}


.thumbnail {

    position: relative;

    width: 100%;

    aspect-ratio: 16 / 9;

    overflow: hidden;

    background:
        rgba(0,0,0,.25);
}


.thumbnail img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

    transition: .25s ease;
}


.video-card:hover
.thumbnail img {

    transform:
        scale(1.04);
}


.play-overlay {

    position: absolute;

    inset: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(0,0,0,.15);

    opacity: 0;

    transition: .2s ease;
}


.video-card:hover
.play-overlay {

    opacity: 1;
}


.play-button {

    width: 54px;
    height: 54px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    color: white;

    background:
        linear-gradient(
            145deg,
            #25dc75,
            #07934e
        );

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.35);

    font-size: 21px;
}


.video-info {

    padding:
        13px
        14px
        15px;
}


.video-title {

    color: var(--white);

    font-size: 19px;

    line-height: 1.45;

    display: -webkit-box;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

    overflow: hidden;

    word-break: break-word;
}


.video-channel {

    color: var(--muted);

    font-size: 15px;

    margin-top: 8px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.empty-state {

    grid-column:
        1 / -1;

    min-height: 280px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

    padding: 30px;

    color: var(--muted);
}


.empty-icon {

    width: 65px;
    height: 65px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 20px;

    margin-bottom: 15px;

    color: var(--green-light);

    background:
        rgba(32,213,162,.08);

    border:
        1px solid
        rgba(129,235,153,.12);

    font-size: 28px;
}


.empty-state h3 {

    color: var(--white);

    font-family: Minecrafter;

    font-weight: lighter;

    font-size: 22px;

    margin-bottom: 7px;
}


.empty-state p {

    color: var(--muted);

    font-size: 17px;

    max-width: 500px;

    line-height: 1.6;
}



.loading {

    grid-column:
        1 / -1;

    min-height: 280px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: var(--green-light);

    font-size: 12px;
}


.loading-spinner {

    width: 22px;
    height: 22px;

    border:
        3px solid
        rgba(53,212,119,.15);

    border-top-color:
        var(--green-light);

    border-radius: 50%;

    margin-right: 10px;

    animation:
        spin .8s linear infinite;
}


@keyframes spin {

    to {

        transform:
            rotate(360deg);
    }
}



.error-box {

    grid-column:
        1 / -1;

    padding: 20px;

    border-radius: 18px;

    color: #ffbebe;

    background:
        rgba(150,30,30,.16);

    border:
        1px solid
        rgba(255,100,100,.18);

    font-size: 12px;

    line-height: 1.6;
}



@media (max-width: 1200px) {

    .results-grid {

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


    .music-search {

        flex-direction: column;

        align-items: stretch;
    }


    .search-button {

        width: 100%;
    }


    .results-grid {

        grid-template-columns: 1fr;
    }


    .results-header {

        align-items: flex-start;

        flex-direction: column;

        gap: 5px;
    }
}



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
            onerror="
                this.style.display='none';
                this.parentElement.innerHTML='P';
            "
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
            class="nav-item active"
            data-peak-page="music.php"
        >

            ♪

            <span class="tooltip">
                Musica
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


<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="welcome">

            <h1>
                Musica
            </h1>

            <p>
                Escucha contenido de YouTube mientras haces tus cosas.
            </p>

        </div>


        <div class="profile-top">

            <span class="profile-name">

                <?= e($nombreUsuario) ?>

            </span>


            <div class="profile-circle">

                <?= e(
                    strtoupper(
                        substr(
                            $nombreUsuario,
                            0,
                            1
                        )
                    )
                ) ?>

            </div>

        </div>

    </div>


    <div class="page-header"></div>


    <!-- =================================================
         BUSCADOR
    ================================================= -->

    <section
        class="glass-card music-search"
    >

        <input
            type="text"
            id="searchInput"
            class="search-input"
            placeholder="Buscar canciones, artistas, videos..."
            autocomplete="off"
        >


        <button
            type="button"
            class="search-button"
            id="searchButton"
        >

            Buscar

        </button>

    </section>


    <!-- =================================================
         REPRODUCTOR LOCAL
    ================================================= -->

    <section
        class="glass-card player-card"
        id="playerCard"
    >

        <div class="player-wrapper">

            <div
                id="youtubePlayer"
            ></div>

        </div>


        <div class="player-info">

            <div
                class="player-title"
                id="playerTitle"
            >

                Selecciona un video

            </div>


            <div
                class="player-channel"
                id="playerChannel"
            >

                YouTube

            </div>

        </div>

    </section>


    <!-- =================================================
         RESULTADOS
    ================================================= -->

    <div class="results-header">

        <h3 class="results-title">

            Resultados

        </h3>


        <span
            class="results-status"
            id="resultsStatus"
        >

            Busca algo para comenzar

        </span>

    </div>


    <section
        class="results-grid"
        id="resultsGrid"
    >

        <div
            class="glass-card empty-state"
        >

            <div class="empty-icon">

                ♪

            </div>


            <h3>

                Explora YouTube

            </h3>


            <p>

                Busca una canción, artista, video o cualquier
                contenido que quieras reproducir.

            </p>

        </div>

    </section>


</main>


<!-- =====================================================
     MODAL CERRAR SESIÓN
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


<script src="https://www.youtube.com/iframe_api"></script>


<script>


let youtubePlayer = null;

let playerReady = false;



const searchInput =
    document.getElementById(
        "searchInput"
    );


const searchButton =
    document.getElementById(
        "searchButton"
    );


const resultsGrid =
    document.getElementById(
        "resultsGrid"
    );


const resultsStatus =
    document.getElementById(
        "resultsStatus"
    );


const playerCard =
    document.getElementById(
        "playerCard"
    );


const playerTitle =
    document.getElementById(
        "playerTitle"
    );


const playerChannel =
    document.getElementById(
        "playerChannel"
    );



function onYouTubeIframeAPIReady()
{

    youtubePlayer =
        new YT.Player(
            "youtubePlayer",
            {

                width:
                    "100%",

                height:
                    "100%",

                playerVars: {

                    autoplay:
                        0,

                    controls:
                        1,

                    rel:
                        0,

                    modestbranding:
                        1,

                    playsinline:
                        1

                },

                events: {

                    onReady:
                        function()
                        {

                            playerReady =
                                true;

                        },


                    onError:
                        function(event)
                        {

                            console.log(
                                "Error de YouTube:",
                                event.data
                            );

                        }

                }

            }
        );

}



async function searchYouTube()
{

    const query =
        searchInput.value.trim();


    if (!query) {

        searchInput.focus();

        return;

    }


    resultsStatus.textContent =
        "Buscando...";


    resultsGrid.innerHTML = `

        <div class="loading">

            <div class="loading-spinner"></div>

            Buscando en YouTube...

        </div>

    `;


    try {

        const response =
            await fetch(
                `music.php?youtube_search=${encodeURIComponent(query)}`
            );


        const data =
            await response.json();


        if (!data.success) {

            showError(
                data.message ||
                "No se pudo realizar la búsqueda."
            );

            return;

        }


        renderResults(
            data.results
        );


    } catch (error) {

        console.error(
            error
        );


        showError(
            "No se pudo conectar con el servidor."
        );

    }

}



function renderResults(
    results
)
{

    resultsGrid.innerHTML =
        "";


    if (
        !results ||
        results.length === 0
    ) {

        resultsGrid.innerHTML = `

            <div
                class="glass-card empty-state"
            >

                <div class="empty-icon">
                    ♪
                </div>

                <h3>
                    No encontramos resultados
                </h3>

                <p>
                    Intenta buscar con otro nombre,
                    artista o canción.
                </p>

            </div>

        `;


        resultsStatus.textContent =
            "0 resultados";


        if (
            window.parent &&
            window.parent !== window
        ) {

            window.parent.postMessage(
                {
                    type:
                        "PEAK_SET_PLAYLIST",

                    playlist:
                        []
                },
                "*"
            );

        }

        return;

    }


    resultsStatus.textContent =
        `${results.length} resultados`;



    if (
        window.parent &&
        window.parent !== window
    ) {

        window.parent.postMessage(
            {
                type:
                    "PEAK_SET_PLAYLIST",

                playlist:
                    results.map(
                        function(video)
                        {

                            return {

                                videoId:
                                    video.videoId,

                                title:
                                    video.title,

                                channel:
                                    video.channel

                            };

                        }
                    )

            },
            "*"
        );

    }


    results.forEach(
        function(video, index)
        {

            const card =
                document.createElement(
                    "article"
                );


            card.className =
                "glass-card video-card";


            card.innerHTML = `

                <div class="thumbnail">

                    <img
                        src="${escapeHTML(video.thumbnail)}"
                        alt=""
                        loading="lazy"
                    >


                    <div class="play-overlay">

                        <div class="play-button">

                            ▶

                        </div>

                    </div>

                </div>


                <div class="video-info">

                    <div class="video-title">

                        ${escapeHTML(video.title)}

                    </div>


                    <div class="video-channel">

                        ${escapeHTML(video.channel)}

                    </div>

                </div>

            `;


            card.addEventListener(
                "click",
                function()
                {

                    playVideo(

                        video.videoId,

                        video.title,

                        video.channel,

                        index

                    );

                }
            );


            resultsGrid.appendChild(
                card
            );

        }
    );

}



function playVideo(
    videoId,
    title,
    channel,
    index = -1
)
{

    if (!videoId) {
        return;
    }



    if (
        window.parent &&
        window.parent !== window
    ) {

        window.parent.postMessage(
            {

                type:
                    "PEAK_PLAY_YOUTUBE",

                videoId:
                    videoId,

                title:
                    cleanText(title),

                channel:
                    cleanText(channel),

                index:
                    typeof index === "number"
                        ? index
                        : -1

            },
            "*"
        );


        return;
    }



    playerCard.classList.add(
        "show"
    );


    playerTitle.textContent =
        cleanText(title);


    playerChannel.textContent =
        cleanText(channel);


    if (!youtubePlayer) {

        return;

    }


    if (!playerReady) {

        setTimeout(
            function()
            {

                playVideo(
                    videoId,
                    title,
                    channel,
                    index
                );

            },
            500
        );

        return;

    }


    youtubePlayer.loadVideoById(
        videoId
    );


    playerCard.scrollIntoView({

        behavior:
            "smooth",

        block:
            "start"

    });

}


function showError(
    message
)
{

    resultsStatus.textContent =
        "Error";


    resultsGrid.innerHTML = `

        <div class="error-box">

            ${escapeHTML(message)}

        </div>

    `;

}



function escapeHTML(
    value
)
{

    return String(
        value ?? ""
    )

    .replace(
        /&/g,
        "&amp;"
    )

    .replace(
        /</g,
        "&lt;"
    )

    .replace(
        />/g,
        "&gt;"
    )

    .replace(
        /"/g,
        "&quot;"
    )

    .replace(
        /'/g,
        "&#039;"
    );

}


function cleanText(
    value
)
{

    const div =
        document.createElement(
            "div"
        );


    div.innerHTML =
        value;


    return (
        div.textContent ||
        div.innerText ||
        ""
    );

}



searchButton.addEventListener(
    "click",
    searchYouTube
);


searchInput.addEventListener(
    "keydown",
    function(event)
    {

        if (
            event.key === "Enter"
        ) {

            event.preventDefault();

            searchYouTube();

        }

    }
);



document
    .querySelectorAll(
        "[data-peak-page]"
    )
    .forEach(
        function(link)
        {

            link.addEventListener(
                "click",
                function(event)
                {

                   

                    if (
                        window.parent &&
                        window.parent !== window
                    ) {

                        event.preventDefault();


                        const page =
                            this.dataset.peakPage;


                        if (!page) {
                            return;
                        }


                        window.parent.postMessage(
                            {

                                type:
                                    "PEAK_NAVIGATE",

                                page:
                                    page

                            },
                            "*"
                        );

                    }

                }
            );

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
    function(event)
    {

        event.preventDefault();

        logoutModal.classList.add(
            "show"
        );

    }
);



cancelLogout.addEventListener(
    "click",
    function()
    {

        logoutModal.classList.remove(
            "show"
        );

    }
);


logoutModal.addEventListener(
    "click",
    function(event)
    {

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
    async function()
    {

        confirmLogout.disabled =
            true;


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
                    "music.php",
                    {
                        method:
                            "POST",

                        body:
                            formData
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

            console.error(
                error
            );


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