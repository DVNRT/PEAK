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

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = intval($_SESSION["usuario_id"]);
$nombreUsuario = $_SESSION["nombre"] ?? "";

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

<title>𝐍𝐎𝐓𝐀𝐒</title>

<link
    rel="icon"
    type="image/png"
    href="../data/descarga.png"
>

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



.main {

    width: 100%;
    margin-left: 0;
    padding:
        30px
        40px
        120px
        40px;

    min-height: 100vh;

    overflow-x: hidden;
    padding-bottom: 150px;
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



.notes-layout {

    display: grid;

    grid-template-columns:
        250px
        minmax(0,1fr);

    gap: 20px;

    align-items: start;
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

.notes-sidebar {

    padding: 17px;

    position: sticky;

    top: 25px;
}

.notes-search {

    width: 100%;

    padding: 12px 13px;

    border-radius: 12px;

    outline: none;

    border:
        1px solid
        rgba(129,235,153,.14);

    color: var(--white);

    background:
        rgba(5,52,32,.55);

    font-family: inherit;

    font-size: 11px;

    margin-bottom: 15px;
}

.notes-search::placeholder {

    color: #7da88d;
}

.notes-search:focus {

    border-color:
        rgba(53,212,119,.45);

    box-shadow:
        0 0 0 3px
        rgba(53,212,119,.07);
}

.notes-menu {

    display: flex;

    flex-direction: column;

    gap: 7px;
}

.note-filter {

    width: 100%;

    padding: 11px 12px;

    border: none;

    border-radius: 12px;

    text-align: left;

    color: #8db99d;

    background:
        transparent;

    font-family: inherit;

    font-size: 11px;

    cursor: pointer;

    transition: .2s ease;
}

.note-filter:hover {

    color: white;

    background:
        rgba(32,213,162,.08);
}

.note-filter.active {

    color: white;

    background:
        linear-gradient(
            145deg,
            rgba(34,206,103,.55),
            rgba(5,112,61,.45)
        );

    box-shadow:
        inset 0 0 0 1px
        rgba(196,255,203,.08);
}

.notes-side-title {

    color: var(--white);

    font-size: 14px;

    margin-bottom: 10px;
}



.notes-area {

    min-width: 0;
}

.notes-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0,1fr));

    gap: 15px;
}

.note-card {

    position: relative;

    min-height: 190px;

    padding: 18px;

    border-radius: 19px;

    border:
        1px solid
        var(--border);

    background:
        linear-gradient(
            145deg,
            rgba(5,59,36,.48),
            rgba(3,35,22,.45)
        );

    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);

    box-shadow:
        0 15px 45px
        rgba(0,0,0,.22);

    overflow: hidden;

    cursor: pointer;

    transition: .25s ease;
}

.note-card:hover {

    transform:
        translateY(-3px);

    border-color:
        rgba(129,235,153,.30);

    box-shadow:
        0 18px 50px
        rgba(0,0,0,.30);
}

.note-card.pinned {

    border-color:
        rgba(155,225,93,.35);
}

.note-card::before {

    content: "";

    position: absolute;

    left: 0;
    top: 0;
    bottom: 0;

    width: 4px;

    background:
        var(--green-light);
}

.note-card.color-lime::before {
    background: var(--lime);
}

.note-card.color-turquoise::before {
    background: var(--turquoise);
}

.note-card.color-green::before {
    background: var(--green-light);
}

.note-card.color-mint::before {
    background: var(--mint);
}

.note-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 12px;
}

.note-title {

    color: var(--white);

    font-size: 25px;

    font-weight: 800;

    line-height: 1.3;

    word-break: break-word;
}

.pin {

    flex-shrink: 0;

    color: var(--lime);

    font-size: 13px;

    opacity: .85;
}

.note-content {

    color: #032a0d;

    font-size: 21px;

    line-height: 1.6;

    white-space: pre-wrap;

    word-break: break-word;

    display: -webkit-box;

    -webkit-line-clamp: 5;

    -webkit-box-orient: vertical;

    overflow: hidden;
}

.note-footer {

    position: absolute;

    left: 18px;
    right: 18px;
    bottom: 14px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;
}

.note-date {

    color: #093117;

    font-size: 15px;
}

.note-actions {

    display: flex;

    gap: 5px;
}

.note-action {

    width: 27px;
    height: 27px;

    display: flex;

    align-items: center;
    justify-content: center;

    border: none;

    border-radius: 8px;

    color: #e7f1ea;

    background:
        rgba(255,255,255,.05);

    cursor: pointer;

    transition: .2s ease;
}

.note-action:hover {

    color: white;

    background:
        rgba(32,213,162,.13);
}

.note-action.delete:hover {

    background:
        rgba(220,50,50,.15);

    color: #ffbebe;
}


.empty-notes {

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

.empty-notes h3 {

    color: var(--white);

    font-size: 16px;

    margin-bottom: 7px;
}

.empty-notes p {

    color: var(--muted);

    font-size: 11px;
}




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

    width: min(570px, 100%);

    max-height: 90vh;

    overflow-y: auto;

    padding: 25px;

    border-radius: 24px;

    background:
        linear-gradient(
            145deg,
            rgba(33,128,20,.74),
            rgba(8,70,30,.64)
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
.form-group textarea {

    width: 100%;

    padding: 12px 13px;

    border-radius: 12px;

    outline: none;

    border:
        1px solid
        rgba(129,235,153,.14);

    color: #0a591a;

    background:
        rgba(190,255,183,.45);

    font-family: inherit;

    font-size: 13px;

    transition: .2s ease;
}

.form-group textarea {

    min-height: 170px;

    resize: vertical;

    line-height: 1.5;
}

.form-group input:focus,
.form-group textarea:focus {

    border-color:
        rgba(53,212,119,.55);

    box-shadow:
        0 0 0 3px
        rgba(53,212,119,.08);
}

.color-options {

    display: flex;

    gap: 9px;
}

.color-option {

    width: 34px;
    height: 34px;

    border-radius: 10px;

    border:
        2px solid
        transparent;

    cursor: pointer;

    transition: .2s ease;
}

.color-option:hover {

    transform:
        translateY(-2px);
}

.color-option.selected {

    border-color:
        white;

    box-shadow:
        0 0 15px
        rgba(255,255,255,.25);
}

.color-green {
    background:
        linear-gradient(
            120deg,
            rgba(135, 225, 145, 0.55),
            rgba(49, 135, 95, 0.55)
        );
}

.color-lime {
    background:
        linear-gradient(
            120deg,
            rgba(154, 208, 132, 0.55),
            rgba(101, 135, 49, 0.55)
        );
}

.color-turquoise {
    background:
        linear-gradient(
            120deg,
            rgba(135, 225, 199, 0.55),
            rgba(49, 135, 102, 0.55)
        );
}

.color-mint {
    background:
        linear-gradient(
            120deg,
            rgba(100, 178, 98, 0.55),
            rgba(39, 112, 45, 0.55)
        );
}

.modal-options {

    display: flex;

    align-items: center;

    gap: 8px;

    color: #c9e8cf;

    font-size: 11px;
}

.modal-options input {

    accent-color:
        var(--green-light);
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

    transition: .2s ease;
}

.btn-secondary:hover {

    color: white;

    background:
        rgba(255,255,255,.08);
}




@media (max-width: 1200px) {

    .notes-grid {

        grid-template-columns:
            repeat(2, minmax(0,1fr));
    }
}

@media (max-width: 1050px) {

    .notes-layout {

        grid-template-columns: 1fr;
    }

    .notes-sidebar {

        position: static;
    }

    .notes-menu {

        display: grid;

        grid-template-columns:
            repeat(3, minmax(0,1fr));
    }
}

@media (max-width: 850px) {

    .main {

        width: 100%;

        margin-left: 0;

        padding:
            22px
            25px
            150px
            25px;
    }

    .topbar {

        align-items: flex-start;
    }
}

@media (max-width: 650px) {

    .main {

        width: 100%;

        margin-left: 0;

        padding:
            18px
            15px
            150px
            15px;
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

    .notes-menu {

        grid-template-columns: 1fr;
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

<body>


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
     MAIN
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <div class="welcome">

            <h1>
                Mis notas
            </h1>

            <p>
                Guarda tus ideas, apuntes y recordatorios importantes.
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
                ▤
            </div>

            <div>

                <h2>
                    Notas
                </h2>

                <p>
                    Organiza tus apuntes de forma rápida y sencilla.
                </p>

            </div>

        </div>


        <button
            type="button"
            class="btn-primary"
            onclick="openNewNote()"
        >
            + Nueva nota
        </button>

    </div>


    <section class="notes-layout">


        <aside class="glass-card notes-sidebar">

            <div class="notes-side-title">
                Mis notas
            </div>


            <input
                type="text"
                id="searchNotes"
                class="notes-search"
                placeholder="Buscar notas..."
                oninput="renderNotes()"
            >


            <div class="notes-menu">

                <button
                    type="button"
                    class="note-filter active"
                    data-filter="all"
                    onclick="changeFilter('all', this)"
                >
                    Todas
                </button>


                <button
                    type="button"
                    class="note-filter"
                    data-filter="pinned"
                    onclick="changeFilter('pinned', this)"
                >
                    Fijadas
                </button>


                <button
                    type="button"
                    class="note-filter"
                    data-filter="recent"
                    onclick="changeFilter('recent', this)"
                >
                    Recientes
                </button>

            </div>

        </aside>


        <div class="notes-area">

            <div
                class="notes-grid"
                id="notesGrid"
            ></div>

        </div>

    </section>

</main>


<!-- =====================================================
     MODAL NOTA
===================================================== -->

<div
    class="modal"
    id="noteModal"
>

    <div class="modal-box">


        <div class="modal-header">

            <h3 id="modalTitle">
                Nueva nota
            </h3>


            <button
                type="button"
                class="close-modal"
                onclick="closeNoteModal()"
            >
                ×
            </button>

        </div>


        <div class="form-group">

            <label>
                Título
            </label>

            <input
                type="text"
                id="noteTitle"
                placeholder="Ej. Ideas para estudiar"
                maxlength="100"
            >

        </div>


        <div class="form-group">

            <label>
                Contenido
            </label>

            <textarea
                id="noteContent"
                placeholder="Escribe tu nota aquí..."
            ></textarea>

        </div>


        <div class="form-group">

            <label>
                Color
            </label>


            <div class="color-options">

                <button
                    type="button"
                    class="color-option color-green selected"
                    data-color="green"
                    onclick="selectColor('green', this)"
                ></button>


                <button
                    type="button"
                    class="color-option color-lime"
                    data-color="lime"
                    onclick="selectColor('lime', this)"
                ></button>


                <button
                    type="button"
                    class="color-option color-turquoise"
                    data-color="turquoise"
                    onclick="selectColor('turquoise', this)"
                ></button>


                <button
                    type="button"
                    class="color-option color-mint"
                    data-color="mint"
                    onclick="selectColor('mint', this)"
                ></button>

            </div>

        </div>


        <div class="modal-options">

            <input
                type="checkbox"
                id="notePinned"
            >

            <label for="notePinned">
                Fijar esta nota
            </label>

        </div>


        <div class="form-actions">

            <button
                type="button"
                class="btn-secondary"
                onclick="closeNoteModal()"
            >
                Cancelar
            </button>


            <button
                type="button"
                class="btn-primary"
                onclick="saveNote()"
            >
                Guardar nota
            </button>

        </div>

    </div>

</div>


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


<script>


const NOTES_KEY =
    "peakNotes_<?= $usuario_id ?>";


let notes = [];

let editingId = null;

let selectedColor = "green";

let currentFilter = "all";



const noteModal =
    document.getElementById("noteModal");

const noteTitle =
    document.getElementById("noteTitle");

const noteContent =
    document.getElementById("noteContent");

const notePinned =
    document.getElementById("notePinned");

const notesGrid =
    document.getElementById("notesGrid");

const searchNotes =
    document.getElementById("searchNotes");

const modalTitle =
    document.getElementById("modalTitle");


function loadNotes()
{

    try {

        const saved =
            localStorage.getItem(
                NOTES_KEY
            );


        if (saved) {

            notes =
                JSON.parse(saved);

        } else {

            notes = [];
        }

    } catch (error) {

        notes = [];
    }


    renderNotes();
}



function saveNotes()
{

    localStorage.setItem(
        NOTES_KEY,
        JSON.stringify(notes)
    );
}


function openNewNote()
{

    editingId = null;

    modalTitle.textContent =
        "Nueva nota";

    noteTitle.value =
        "";

    noteContent.value =
        "";

    notePinned.checked =
        false;

    selectColor(
        "green",
        document.querySelector(
            '[data-color="green"]'
        )
    );

    noteModal.classList.add(
        "show"
    );

    setTimeout(
        function() {

            noteTitle.focus();

        },
        100
    );
}


function editNote(id)
{

    const note =
        notes.find(
            item =>
                item.id === id
        );


    if (!note) {
        return;
    }


    editingId =
        id;


    modalTitle.textContent =
        "Editar nota";

    noteTitle.value =
        note.title;

    noteContent.value =
        note.content;

    notePinned.checked =
        note.pinned;

    selectColor(
        note.color,
        document.querySelector(
            `[data-color="${note.color}"]`
        )
    );


    noteModal.classList.add(
        "show"
    );
}



function saveNote()
{

    const title =
        noteTitle.value.trim();

    const content =
        noteContent.value.trim();


    if (
        title === "" &&
        content === ""
    ) {

        noteTitle.focus();

        return;
    }


    const now =
        new Date().toISOString();


    if (
        editingId !== null
    ) {

        const index =
            notes.findIndex(
                item =>
                    item.id === editingId
            );


        if (index !== -1) {

            notes[index].title =
                title || "Sin título";

            notes[index].content =
                content;

            notes[index].color =
                selectedColor;

            notes[index].pinned =
                notePinned.checked;

            notes[index].updatedAt =
                now;
        }

    } else {

        notes.unshift({

            id:
                Date.now(),

            title:
                title || "Sin título",

            content:
                content,

            color:
                selectedColor,

            pinned:
                notePinned.checked,

            createdAt:
                now,

            updatedAt:
                now

        });
    }


    saveNotes();

    closeNoteModal();

    renderNotes();
}



function deleteNote(id, event)
{

    if (event) {
        event.stopPropagation();
    }


    const note =
        notes.find(
            item =>
                item.id === id
        );


    if (!note) {
        return;
    }


    if (
        !confirm(
            "¿Quieres eliminar esta nota?"
        )
    ) {

        return;
    }


    notes =
        notes.filter(
            item =>
                item.id !== id
        );


    saveNotes();

    renderNotes();
}



function togglePin(id, event)
{

    if (event) {
        event.stopPropagation();
    }


    const note =
        notes.find(
            item =>
                item.id === id
        );


    if (!note) {
        return;
    }


    note.pinned =
        !note.pinned;

    note.updatedAt =
        new Date().toISOString();


    saveNotes();

    renderNotes();
}


function selectColor(
    color,
    element
)
{

    selectedColor =
        color;


    document
        .querySelectorAll(
            ".color-option"
        )
        .forEach(
            button =>
                button.classList.remove(
                    "selected"
                )
        );


    if (element) {

        element.classList.add(
            "selected"
        );
    }
}


function changeFilter(
    filter,
    element
)
{

    currentFilter =
        filter;


    document
        .querySelectorAll(
            ".note-filter"
        )
        .forEach(
            button =>
                button.classList.remove(
                    "active"
                )
        );


    element.classList.add(
        "active"
    );


    renderNotes();
}



function formatDate(
    date
)
{

    if (!date) {
        return "";
    }


    const d =
        new Date(date);


    const day =
        String(
            d.getDate()
        ).padStart(
            2,
            "0"
        );


    const month =
        String(
            d.getMonth() + 1
        ).padStart(
            2,
            "0"
        );


    const year =
        d.getFullYear();


    return (
        day +
        "/" +
        month +
        "/" +
        year
    );
}


function escapeHTML(
    text
)
{

    const div =
        document.createElement(
            "div"
        );


    div.textContent =
        text;


    return div.innerHTML;
}



function renderNotes()
{

    let filtered =
        [...notes];


    const search =
        searchNotes.value
            .trim()
            .toLowerCase();


    if (search !== "") {

        filtered =
            filtered.filter(
                note =>
                    note.title
                        .toLowerCase()
                        .includes(search)
                    ||
                    note.content
                        .toLowerCase()
                        .includes(search)
            );
    }


    if (
        currentFilter ===
        "pinned"
    ) {

        filtered =
            filtered.filter(
                note =>
                    note.pinned
            );
    }


    if (
        currentFilter ===
        "recent"
    ) {

        filtered.sort(
            (a, b) =>
                new Date(
                    b.updatedAt
                )
                -
                new Date(
                    a.updatedAt
                )
        );
    }


    if (
        currentFilter ===
        "all"
    ) {

        filtered.sort(
            (a, b) => {

                if (
                    a.pinned &&
                    !b.pinned
                ) {

                    return -1;
                }

                if (
                    !a.pinned &&
                    b.pinned
                ) {

                    return 1;
                }

                return new Date(
                    b.updatedAt
                )
                -
                new Date(
                    a.updatedAt
                );
            }
        );
    }


    if (
        filtered.length === 0
    ) {

        notesGrid.innerHTML = `

            <div class="glass-card empty-notes">

                <div class="empty-icon">
                    ▤
                </div>

                <h3>
                    ${
                        notes.length === 0
                            ? "Todavía no tienes notas"
                            : "No encontramos notas"
                    }
                </h3>

                <p>
                    ${
                        notes.length === 0
                            ? "Crea tu primera nota para comenzar."
                            : "Prueba con otro término de búsqueda."
                    }
                </p>

            </div>

        `;

        return;
    }


    notesGrid.innerHTML =
        filtered.map(
            note => `

                <article
                    class="
                        note-card
                        color-${escapeHTML(note.color)}
                        ${note.pinned ? "pinned" : ""}
                    "
                    onclick="editNote(${note.id})"
                >

                    <div class="note-top">

                        <div class="note-title">

                            ${escapeHTML(
                                note.title
                            )}

                        </div>


                        ${
                            note.pinned
                                ? `
                                    <div class="pin">
                                        ◆
                                    </div>
                                `
                                : ""
                        }

                    </div>


                    <div class="note-content">

                        ${escapeHTML(
                            note.content ||
                            "Sin contenido."
                        )}

                    </div>


                    <div class="note-footer">

                        <span class="note-date">

                            ${formatDate(
                                note.updatedAt
                            )}

                        </span>


                        <div class="note-actions">

                            <button
                                type="button"
                                class="note-action"
                                onclick="
                                    togglePin(
                                        ${note.id},
                                        event
                                    )
                                "
                                title="Fijar"
                            >
                                ◆
                            </button>


                            <button
                                type="button"
                                class="note-action"
                                onclick="
                                    editNote(
                                        ${note.id}
                                    );
                                    event.stopPropagation();
                                "
                                title="Editar"
                            >
                                ✎
                            </button>


                            <button
                                type="button"
                                class="note-action delete"
                                onclick="
                                    deleteNote(
                                        ${note.id},
                                        event
                                    )
                                "
                                title="Eliminar"
                            >
                                ×
                            </button>

                        </div>

                    </div>

                </article>

            `
        ).join("");
}



function closeNoteModal()
{

    noteModal.classList.remove(
        "show"
    );
}



noteModal.addEventListener(
    "click",
    function(event) {

        if (
            event.target ===
            noteModal
        ) {

            closeNoteModal();
        }

    }
);



document.addEventListener(
    "keydown",
    function(event) {

        if (
            event.key === "Escape"
        ) {

            closeNoteModal();
        }

    }
);


noteContent.addEventListener(
    "keydown",
    function(event) {

        if (
            event.ctrlKey &&
            event.key === "Enter"
        ) {

            saveNote();
        }

    }
);



loadNotes();


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



if (logoutButton) {

    logoutButton.addEventListener(
        "click",
        function(event) {

            event.preventDefault();

            if (logoutModal) {

                logoutModal.classList.add(
                    "show"
                );
            }

        }
    );
}


if (cancelLogout) {

    cancelLogout.addEventListener(
        "click",
        function() {

            logoutModal.classList.remove(
                "show"
            );

        }
    );
}


if (logoutModal) {

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
}


if (confirmLogout) {

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
                        window.location.href,
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
}

</script>

</body>

</html>