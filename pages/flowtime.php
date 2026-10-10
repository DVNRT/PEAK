<?php

session_start();


$embedded =
    isset($_GET["embedded"]) &&
    $_GET["embedded"] === "1";

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

<title>𝐅𝐋𝐎𝐖𝐓𝐈𝐌𝐄</title>

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


.flow-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        minmax(260px, 330px);

    gap: 20px;

    align-items: start;

    width: 100%;
}


.timer-card {

    min-height: 525px;

    padding: 30px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    text-align: center;

    position: relative;

    overflow: hidden;
}

.timer-card::before {

    content: "";

    position: absolute;

    width: 330px;
    height: 330px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(53,212,119,.13),
            transparent 70%
        );

    pointer-events: none;
}

.timer-state {

    position: relative;

    color: var(--green-light);

    font-family: Minecraft;

    font-size: 21px;

    margin-bottom: 17px;
}

.timer-state.rest {

    color: var(--lime);
}

.timer-circle {

    position: relative;

    width: min(360px, 70vw);
    height: min(360px, 70vw);

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    background:

        radial-gradient(
            circle,
            rgba(4,27,18,.90) 56%,
            rgba(10,83,47,.35) 57%,
            rgba(10,83,47,.20) 67%,
            transparent 68%
        );

    border:
        2px solid
        rgba(53,212,119,.23);

    box-shadow:

        0 0 45px
        rgba(37,217,111,.12),

        inset 0 0 45px
        rgba(37,217,111,.07);
}

.timer-circle.active {

    animation:
        timerGlow 3s ease-in-out infinite;
}

@keyframes timerGlow {

    0%, 100% {
        box-shadow:
            0 0 45px
            rgba(37,217,111,.10),
            inset 0 0 45px
            rgba(37,217,111,.05);
    }

    50% {
        box-shadow:
            0 0 65px
            rgba(37,217,111,.20),
            inset 0 0 55px
            rgba(37,217,111,.09);
    }
}

.timer-time {

    position: relative;

    color: var(--white);

    font-family: Minecrafter;

    font-weight: lighter;

    font-size: clamp(48px, 8vw, 78px);

    line-height: 1;

    letter-spacing: 2px;
}

.timer-caption {

    position: absolute;

    bottom: 72px;

    color: var(--muted);

    font-size: 11px;

    letter-spacing: 1px;
}

.timer-controls {

    position: relative;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 10px;

    margin-top: 28px;

    flex-wrap: wrap;
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

    background:
        rgba(32,213,162,.10);

    color: white;
}

.btn-danger {

    padding: 12px 17px;

    border-radius: 12px;

    border:
        1px solid
        rgba(255,120,120,.16);

    color: #ffcaca;

    background:
        rgba(220,50,50,.08);

    cursor: pointer;

    transition: .2s ease;
}

.btn-danger:hover {

    background:
        rgba(220,50,50,.15);
}


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


.mode-box {

    padding: 14px;

    border-radius: 14px;

    background:
        rgba(29,125,69,.15);

    border:
        1px solid
        rgba(129,235,153,.09);

    margin-bottom: 10px;
}

.mode-box strong {

    display: block;

    color: var(--white);

    font-size: 12px;

    margin-bottom: 6px;
}

.mode-box span {

    color: var(--muted);

    font-size: 10px;

    line-height: 1.5;
}

.setting-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    padding: 12px;

    border-radius: 13px;

    background:
        rgba(32,213,162,.07);

    border:
        1px solid
        rgba(129,235,153,.08);

    margin-bottom: 8px;
}

.setting-info {

    min-width: 0;
}

.setting-info strong {

    display: block;

    color: var(--white);

    font-size: 12px;
}

.setting-info span {

    display: block;

    color: var(--muted);

    font-size: 9px;

    margin-top: 4px;
}

.setting-input {

    width: 70px;

    padding: 9px 8px;

    border-radius: 10px;

    outline: none;

    border:
        1px solid
        rgba(129,235,153,.14);

    color: #0a591a;

    background:
        rgba(190,255,183,.45);

    font-family: inherit;

    font-size: 13px;

    text-align: center;
}

.setting-input:focus {

    border-color:
        rgba(53,212,119,.55);

    box-shadow:
        0 0 0 3px
        rgba(53,212,119,.08);
}


.switch {

    position: relative;

    width: 43px;
    height: 24px;

    flex-shrink: 0;
}

.switch input {

    opacity: 0;

    width: 0;
    height: 0;
}

.slider {

    position: absolute;

    inset: 0;

    cursor: pointer;

    border-radius: 20px;

    background:
        rgba(255,255,255,.10);

    border:
        1px solid
        rgba(129,235,153,.14);

    transition: .25s ease;
}

.slider::before {

    content: "";

    position: absolute;

    width: 16px;
    height: 16px;

    left: 3px;
    top: 3px;

    border-radius: 50%;

    background: #9bc5a8;

    transition: .25s ease;
}

.switch input:checked + .slider {

    background:
        rgba(30,219,117,.65);
}

.switch input:checked + .slider::before {

    transform: translateX(19px);

    background: white;
}

.status-card {

    padding: 19px;

    text-align: center;
}

.status-dot {

    width: 10px;
    height: 10px;

    display: inline-block;

    border-radius: 50%;

    background: #78aa8b;

    margin-right: 7px;
}

.status-dot.active {

    background: var(--lime);

    box-shadow:
        0 0 12px
        rgba(155,225,93,.7);
}

.status-text {

    color: var(--muted);

    font-size: 11px;
}


#flowMini {

    position: fixed;

    right: 25px;
    bottom: 25px;

    width: 230px;

    padding: 14px;

    z-index: 999;

    display: none;

    background:
        linear-gradient(
            145deg,
            rgba(5,59,36,.94),
            rgba(2,35,22,.94)
        );

    border:
        1px solid
        rgba(129,235,153,.25);

    border-radius: 18px;

    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);

    box-shadow:
        0 20px 60px
        rgba(0,0,0,.45),

        0 0 25px
        rgba(37,217,111,.10);
}

.mini-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 8px;
}

.mini-state {

    color: var(--green-light);

    font-size: 10px;

    font-weight: 800;
}

.mini-time {

    color: var(--white);

    font-family: Minecrafter;

    font-size: 25px;

    text-align: center;

    margin: 5px 0 10px;
}

.mini-buttons {

    display: flex;

    gap: 6px;
}

.mini-btn {

    flex: 1;

    padding: 7px;

    border: none;

    border-radius: 9px;

    color: white;

    background:
        rgba(255,255,255,.07);

    cursor: pointer;

    font-family: inherit;

    font-size: 10px;
}

.mini-btn:hover {

    background:
        rgba(32,213,162,.16);
}


@media (max-width: 1050px) {

    .flow-layout {

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

    .right-column {

        grid-template-columns: 1fr;
    }

    .timer-card {

        min-height: 470px;
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

    .page-title h2 {

        font-size: 23px;
    }

    .timer-card {

        padding: 20px;

        min-height: 430px;
    }

    .timer-circle {

        width: min(300px, 72vw);
        height: min(300px, 72vw);
    }

    .timer-caption {

        bottom: 55px;
    }

    #flowMini {

        right: 12px;
        bottom: 12px;

        width: 205px;
    }
}

@media (max-width: 450px) {

    .main {

        width: calc(100% - 104px);

        margin-left: 104px;

        padding-left: 3px;

        padding-right: 6px;
    }

    .timer-card {

        min-height: 390px;

        padding: 14px;
    }

    .timer-circle {

        width: 260px;
        height: 260px;
    }

    .timer-time {

        font-size: 47px;
    }

    .timer-state {

        font-size: 16px;
    }

    .timer-controls {

        margin-top: 20px;
    }

    .btn-primary,
    .btn-secondary,
    .btn-danger {

        padding: 10px 12px;

        font-size: 11px;
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



body.embedded {

    min-height: 100%;

    height: 100%;

    overflow-x: hidden;

    overflow-y: auto;

    padding-bottom: 170px;

    scroll-padding-bottom: 180px;
}


body.embedded .sidebar {

    display: none;
}



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


body.embedded .flow-layout {

    width: 100%;

    grid-template-columns:
        minmax(0, 1fr)
        minmax(235px, 290px);

    gap: 16px;
}


body.embedded .timer-card {

    min-height: 500px;

    padding: 24px;
}


body.embedded .timer-circle {

    width:
        min(340px, 62vw);

    height:
        min(340px, 62vw);
}


body.embedded .right-column {

    min-width: 0;
}



@media (max-width: 1100px) {

    body.embedded .flow-layout {

        grid-template-columns: 1fr;
    }

    body.embedded .right-column {

        display: grid;

        grid-template-columns:
            repeat(
                2,
                minmax(0,1fr)
            );

        gap: 14px;
    }

    body.embedded .timer-card {

        min-height: 470px;
    }
}


@media (max-width: 800px) {

    body.embedded .main {

        width: 100%;

        margin-left: 0;

        padding:
            20px
            16px
            175px
            16px;
    }

    body.embedded .topbar {

        align-items: flex-start;

        gap: 12px;
    }

    body.embedded .welcome h1 {

        font-size: 35px;
    }

    body.embedded .welcome p {

        font-size: 14px;
    }

    body.embedded .page-title h2 {

        font-size: 25px;
    }

    body.embedded .timer-card {

        min-height: 440px;

        padding: 20px;
    }

    body.embedded .timer-circle {

        width:
            min(310px, 66vw);

        height:
            min(310px, 66vw);
    }

    body.embedded .timer-time {

        font-size:
            clamp(
                43px,
                8vw,
                68px
            );
    }
}


@media (max-width: 600px) {

    body.embedded {

        padding-bottom: 185px;
    }

    body.embedded .main {

        padding:
            16px
            10px
            185px
            10px;
    }

    body.embedded .welcome h1 {

        font-size: 28px;
    }

    body.embedded .welcome p {

        font-size: 11px;
    }

    body.embedded .profile-name {

        display: none;
    }

    body.embedded .profile-circle {

        width: 38px;

        height: 38px;
    }

    body.embedded .page-header {

        align-items: flex-start;

        flex-direction: column;

        gap: 11px;
    }

    body.embedded .page-title h2 {

        font-size: 22px;
    }

    body.embedded .page-title p {

        font-size: 10px;
    }

    body.embedded .timer-card {

        min-height: 400px;

        padding: 15px;
    }

    body.embedded .timer-circle {

        width:
            min(275px, 73vw);

        height:
            min(275px, 73vw);
    }

    body.embedded .timer-time {

        font-size: 48px;
    }

    body.embedded .timer-caption {

        bottom: 52px;

        font-size: 8px;
    }

    body.embedded .timer-state {

        font-size: 16px;

        margin-bottom: 10px;
    }

    body.embedded .timer-controls {

        margin-top: 18px;

        gap: 6px;
    }

    body.embedded .btn-primary,
    body.embedded .btn-secondary,
    body.embedded .btn-danger {

        padding:
            9px 11px;

        font-size: 10px;
    }

    body.embedded .right-column {

        display: grid;

        grid-template-columns: 1fr;
    }
}


@media (max-width: 450px) {

    body.embedded .main {

        padding:
            14px
            6px
            190px
            6px;
    }

    body.embedded .welcome h1 {

        font-size: 24px;
    }

    body.embedded .welcome p {

        font-size: 9px;
    }

    body.embedded .page-title-icon {

        width: 42px;

        height: 42px;

        font-size: 19px;
    }

    body.embedded .page-title h2 {

        font-size: 19px;
    }

    body.embedded .timer-card {

        min-height: 370px;

        padding: 10px;
    }

    body.embedded .timer-circle {

        width: 245px;

        height: 245px;
    }

    body.embedded .timer-time {

        font-size: 42px;
    }

    body.embedded .timer-caption {

        bottom: 47px;

        font-size: 7px;
    }

    body.embedded .timer-controls {

        margin-top: 15px;
    }
}



body.embedded #flowMini {

    display: none !important;
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
            class="nav-item active"
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


<main class="main">



    <div class="topbar">

        <div class="welcome">

            <h1>
                Mi Flowtime
            </h1>

            <p>
                Concéntrate, trabaja y descansa sin perder tu ritmo.
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


    <section class="flow-layout">



        <div class="glass-card timer-card">

            <div
                class="timer-state"
                id="timerState"
            >
                Estado Flow
            </div>


            <div
                class="timer-circle"
                id="timerCircle"
            >

                <div
                    class="timer-time"
                    id="timerDisplay"
                >
                    65:00
                </div>


                <div class="timer-caption">
                    MINUTOS RESTANTES
                </div>

            </div>


            <div class="timer-controls">

                <button
                    type="button"
                    class="btn-primary"
                    id="mainButton"
                    onclick="mainTimerAction()"
                >
                    Iniciar Flow
                </button>


                <button
                    type="button"
                    class="btn-secondary"
                    id="skipButton"
                    onclick="skipRest()"
                    style="display:none;"
                >
                    Saltar descanso
                </button>


                <button
                    type="button"
                    class="btn-danger"
                    id="stopButton"
                    onclick="stopFlow()"
                    style="display:none;"
                >
                    Detener
                </button>

            </div>

        </div>



        <aside class="right-column">



            <div class="glass-card status-card">

                <div class="side-title">

                    <h3>
                        Estado actual
                    </h3>

                </div>


                <div class="status-text">

                    <span
                        class="status-dot"
                        id="statusDot"
                    ></span>

                    <span id="statusText">
                        Flow detenido
                    </span>

                </div>

            </div>


            <div class="glass-card side-card">

                <div class="side-title">

                    <h3>
                        Configuración
                    </h3>

                    <span>
                        Flowtime
                    </span>

                </div>


                <div class="setting-row">

                    <div class="setting-info">

                        <strong>
                            Tiempo Flow
                        </strong>

                        <span>
                            Minutos de concentración
                        </span>

                    </div>


                    <input
                        class="setting-input"
                        type="number"
                        id="flowMinutes"
                        min="1"
                        max="180"
                        value="65"
                    >

                </div>


                <div class="setting-row">

                    <div class="setting-info">

                        <strong>
                            Descanso
                        </strong>

                        <span>
                            Descanso ligero
                        </span>

                    </div>


                    <input
                        class="setting-input"
                        type="number"
                        id="restMinutes"
                        min="1"
                        max="120"
                        value="20"
                    >

                </div>


                <div class="setting-row">

                    <div class="setting-info">

                        <strong>
                            Auto-descanso
                        </strong>

                        <span>
                            Iniciar descanso al terminar
                        </span>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            id="autoRest"
                            checked
                        >

                        <span class="slider"></span>

                    </label>

                </div>


                <button
                    type="button"
                    class="btn-primary"
                    style="width:100%; margin-top:8px;"
                    onclick="saveSettings()"
                >
                    Guardar configuración
                </button>

            </div>


            <div class="glass-card side-card">

                <div class="side-title">

                    <h3>
                        ¿Cómo funciona?
                    </h3>

                </div>


                <div class="mode-box">

                    <strong>
                        Estado Flow
                    </strong>

                    <span>
                        Dedica tu tiempo a estudiar,
                        trabajar o completar una actividad
                        sin interrupciones
                    </span>

                </div>


                <div class="mode-box">

                    <strong>
                        Descanso ligero
                    </strong>

                    <span>
                        Cuando termine el Flow puedes
                        tomar un descanso antes de comenzar
                        otra sesión
                    </span>

                </div>

            </div>

        </aside>

    </section>

</main>



<div id="flowMini">

    <div class="mini-top">

        <span
            class="mini-state"
            id="miniState"
        >
            Estado Flow
        </span>

        <span>
            ◷
        </span>

    </div>


    <div
        class="mini-time"
        id="miniTime"
    >
        65:00
    </div>


    <div class="mini-buttons">

        <button
            class="mini-btn"
            onclick="mainTimerAction()"
            id="miniAction"
        >
            Iniciar
        </button>


        <button
            class="mini-btn"
            onclick="stopFlow()"
        >
            Detener
        </button>

    </div>

</div>


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


document
    .querySelectorAll("[data-peak-page]")
    .forEach(function(link) {

        link.addEventListener(
            "click",
            function(event) {

                if (
                    window.parent &&
                    window.parent !== window &&
                    document.body.classList.contains("embedded")
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

    });



const STORAGE_KEY = "peakFlowtime";


let defaultState = {

    activo: false,

    modo: "flow",

    segundosRestantes: 65 * 60,

    pausado: false,

    autoRest: true,

    inicioTimestamp: null,

    pausaTimestamp: null,

    inicioSegundos: 65 * 60,

    flowMinutes: 65,

    restMinutes: 20
};


let state = cargarEstado();

let timerInterval = null;



const timerDisplay =
    document.getElementById("timerDisplay");

const miniTime =
    document.getElementById("miniTime");

const timerState =
    document.getElementById("timerState");

const miniState =
    document.getElementById("miniState");

const timerCircle =
    document.getElementById("timerCircle");

const mainButton =
    document.getElementById("mainButton");

const skipButton =
    document.getElementById("skipButton");

const stopButton =
    document.getElementById("stopButton");

const flowMinutes =
    document.getElementById("flowMinutes");

const restMinutes =
    document.getElementById("restMinutes");

const autoRest =
    document.getElementById("autoRest");

const statusText =
    document.getElementById("statusText");

const statusDot =
    document.getElementById("statusDot");

const flowMini =
    document.getElementById("flowMini");

const miniAction =
    document.getElementById("miniAction");



function cargarEstado()
{
    try {

        const saved =
            localStorage.getItem(
                STORAGE_KEY
            );

        if (!saved) {

            return {
                ...defaultState
            };
        }

        const parsed =
            JSON.parse(saved);

        return {
            ...defaultState,
            ...parsed
        };

    } catch (error) {

        return {
            ...defaultState
        };
    }
}



function guardarEstado()
{
    localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify(state)
    );
}


function cargarConfiguracion()
{
    flowMinutes.value =
        state.flowMinutes;

    restMinutes.value =
        state.restMinutes;

    autoRest.checked =
        state.autoRest;
}



function saveSettings()
{

    const newFlow =
        parseInt(flowMinutes.value);

    const newRest =
        parseInt(restMinutes.value);


    if (
        isNaN(newFlow) ||
        newFlow < 1
    ) {

        flowMinutes.value =
            state.flowMinutes;

        return;
    }


    if (
        isNaN(newRest) ||
        newRest < 1
    ) {

        restMinutes.value =
            state.restMinutes;

        return;
    }


    state.flowMinutes =
        Math.min(newFlow, 180);

    state.restMinutes =
        Math.min(newRest, 120);

    state.autoRest =
        autoRest.checked;


    if (!state.activo) {

        state.modo =
            "flow";

        state.segundosRestantes =
            state.flowMinutes * 60;
    }


    guardarEstado();

    actualizarInterfaz();

}


function iniciarFlow()
{

    if (state.modo !== "flow") {
        return;
    }


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
        Date.now();


    guardarEstado();

    iniciarIntervalo();

    actualizarInterfaz();
}



function pausarFlow()
{

    actualizarTiempo();


    state.pausado =
        true;

    state.activo =
        true;

    state.pausaTimestamp =
        Date.now();


    guardarEstado();

    actualizarInterfaz();
}



function reanudarFlow()
{

    state.pausado =
        false;

    state.inicioTimestamp =
        Date.now();

    state.pausaTimestamp =
        null;

    state.activo =
        true;


    guardarEstado();

    iniciarIntervalo();

    actualizarInterfaz();
}



function stopFlow()
{

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


    guardarEstado();

    detenerIntervalo();

    actualizarInterfaz();
}


function terminarModo()
{

    if (
        state.modo === "flow"
    ) {

        if (
            state.autoRest
        ) {

            iniciarDescanso();

        } else {

            state.activo =
                false;

            state.segundosRestantes =
                state.flowMinutes * 60;

            guardarEstado();

            detenerIntervalo();

            actualizarInterfaz();
        }

        return;
    }


    if (
        state.modo === "rest"
    ) {

        iniciarNuevoFlow();
    }
}



function iniciarDescanso()
{

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


    guardarEstado();

    iniciarIntervalo();

    actualizarInterfaz();
}



function iniciarNuevoFlow()
{

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


    guardarEstado();

    iniciarIntervalo();

    actualizarInterfaz();
}



function skipRest()
{

    if (
        state.modo !== "rest"
    ) {

        return;
    }


    iniciarNuevoFlow();
}


function actualizarTiempo()
{

    if (
        !state.activo ||
        state.pausado
    ) {

        return;
    }


    if (
        !state.inicioTimestamp
    ) {

        state.inicioTimestamp =
            Date.now();

        return;
    }


    const ahora =
        Date.now();


    const segundosPasados =
        Math.floor(
            (
                ahora -
                state.inicioTimestamp
            ) / 1000
        );


    const inicial =
        state.modo === "flow"
            ? state.flowMinutes * 60
            : state.restMinutes * 60;


    const nuevosSegundos =
        inicial -
        segundosPasados;


    state.segundosRestantes =
        Math.max(
            0,
            nuevosSegundos
        );


    if (
        state.segundosRestantes <= 0
    ) {

        terminarModo();
    }
}

function iniciarIntervalo()
{

    detenerIntervalo();


    timerInterval =
        setInterval(
            function() {

                actualizarTiempo();

                actualizarInterfaz();

            },
            500
        );
}


function detenerIntervalo()
{

    if (
        timerInterval
    ) {

        clearInterval(
            timerInterval
        );

        timerInterval =
            null;
    }
}

function mainTimerAction()
{

    if (
        !state.activo
    ) {

        iniciarFlow();

        return;
    }


    if (
        state.pausado
    ) {

        reanudarFlow();

        return;
    }


    pausarFlow();
}


function formatTime(seconds)
{

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
        String(minutes).padStart(2, "0")
        +
        ":"
        +
        String(remaining).padStart(2, "0")
    );
}



function actualizarInterfaz()
{

    actualizarTiempo();


    const formatted =
        formatTime(
            state.segundosRestantes
        );


    timerDisplay.textContent =
        formatted;

    miniTime.textContent =
        formatted;



    if (
        state.modo === "flow"
    ) {

        timerState.textContent =
            state.pausado
                ? "Flow pausado"
                : "Estado Flow";

        miniState.textContent =
            state.pausado
                ? "Flow pausado"
                : "Estado Flow";

        timerState.classList.remove(
            "rest"
        );

        timerCircle.classList.remove(
            "rest"
        );

        skipButton.style.display =
            "none";

    } else {

        timerState.textContent =
            state.pausado
                ? "Descanso pausado"
                : "Descanso ligero";

        miniState.textContent =
            state.pausado
                ? "Descanso pausado"
                : "Descanso ligero";

        timerState.classList.add(
            "rest"
        );

        skipButton.style.display =
            "inline-block";
    }



    if (
        !state.activo
    ) {

        statusText.textContent =
            "Flow detenido";

        statusDot.classList.remove(
            "active"
        );

        timerCircle.classList.remove(
            "active"
        );

        mainButton.textContent =
            "Iniciar Flow";

        stopButton.style.display =
            "none";

        flowMini.style.display =
            "none";

        miniAction.textContent =
            "Iniciar";

    } else {

        statusDot.classList.add(
            "active"
        );

        timerCircle.classList.add(
            "active"
        );



        if (
            document.body.classList.contains(
                "embedded"
            )
        ) {

            flowMini.style.display =
                "none";

        } else {

            flowMini.style.display =
                "block";
        }


        stopButton.style.display =
            "inline-block";


        if (
            state.pausado
        ) {

            statusText.textContent =
                "Temporizador pausado";

            mainButton.textContent =
                "Continuar";

            miniAction.textContent =
                "Continuar";

        } else {

            statusText.textContent =
                state.modo === "flow"
                    ? "Concentración activa"
                    : "Descanso activo";

            mainButton.textContent =
                "Pausar";

            miniAction.textContent =
                "Pausar";
        }
    }


    guardarEstado();
}



function recuperarTemporizador()
{

    cargarConfiguracion();

    actualizarTiempo();

    actualizarInterfaz();


    if (
        state.activo &&
        !state.pausado
    ) {

        iniciarIntervalo();
    }
}



document.addEventListener(
    "visibilitychange",
    function() {

        if (
            !document.hidden
        ) {

            state =
                cargarEstado();

            cargarConfiguracion();

            actualizarTiempo();

            actualizarInterfaz();


            if (
                state.activo &&
                !state.pausado
            ) {

                iniciarIntervalo();
            }
        }

    }
);


window.addEventListener(
    "storage",
    function(event) {

        if (
            event.key === STORAGE_KEY
        ) {

            state =
                cargarEstado();

            cargarConfiguracion();

            actualizarInterfaz();
        }

    }
);



recuperarTemporizador();



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
                    "flowtime.php<?= $embedded ? '?embedded=1' : '' ?>",
                    {
                        method: "POST",
                        body: formData
                    }
                );


            const data =
                await response.json();


            if (data.success) {

                window.top.location.href =
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