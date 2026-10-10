<?php

require_once __DIR__ . "/../backend/config/database.php";

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($nombre === "" || $correo === "" || $password === "") {

        $mensaje = "Eyy! TODOS los campos son obligatorios.";
        $tipoMensaje = "error";

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = "El correo electrónico no es válido.";
        $tipoMensaje = "error";

    } elseif (strlen($password) < 8) {

        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
        $tipoMensaje = "error";

    } else {

        $sql = "SELECT id FROM usuarios WHERE correo = ?";

        $stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error en SELECT: " . $conn->error);
}

$stmt->bind_param("s", $correo);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            $mensaje = "Este correo ya está registrado.";
            $tipoMensaje = "error";

            $stmt->close();

        } else {

            $stmt->close();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios (nombre, correo, password)
                    VALUES (?, ?, ?)";

            $stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error en INSERT: " . $conn->error);
}

$stmt->bind_param("sss", $nombre, $correo, $passwordHash);
            if ($stmt->execute()) {

                $stmt->close();
                $conn->close();

                header("Location: login.php?registro=exitoso");
                exit;

            } else {

                $mensaje = "Woops! Error al registrar el usuario.";
                $tipoMensaje = "error";

                $stmt->close();
            }
        }
    }
}

?>


<!DOCTYPE html>
<html lang="es">


<head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <title>𝐏𝐄𝐀𝐊</title>
     
<link rel="icon" type="image/png" href="..\data\descarga.png">


</head>



<style>

body {
    overflow-x: hidden;
    align-items: center;
    font-family: Arial, sans-serif;
    background-color: #052d07;
}

.Panel_registro {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    height: 400px;
    width: 900px;
    padding: 40px;
    background: linear-gradient(to bottom, #0b2b227d, #1f6b3872);
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
}

.titulo_registro h1 {
    transform: translate(2%, 90%);
    font-family: Minecrafter;
    font-size: 106px;
    font-weight: lighter;
    color: #cde3cb;
    text-align: left;
    margin-top: 50px;
    animation: pageReveal 1s ease-in-out;
}



@keyframes pageReveal {
    0% {
        opacity: 0;
         transform: translate(2%, 0%);
    }
    100% {
        opacity: 1;
        transform: translate(2%, 90%);
    }
}






 .registro .input_registro {
    transform: translate(90%, -460%);
    align-items: left;
    font-family: Mochi Green ;
    color: #0B2B22;
    width: 50%;
    padding: 10px;
    margin-bottom: 20px ;
    border: none;
    border-radius: 5px;
    background-color: #dbe0d8;
    font-size: 16px;
    animation: inputReveal 1.8s ease-in-out;
}


@keyframes inputReveal {
    0% {
        opacity: 0;
        transform: translate(90%, -500%);
           margin-bottom: 3px ;
    }
    100% {
        opacity: 1;
        transform: translate(90%, -460%);
           margin-bottom: 20px ;
    }
}




.registro  {
    
    align-items: right;
}


#videobackground {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: -1;
    -webkit-filter: opacity(0.5);
    filter: opacity(0.5);
}





.titulo h2 {
    
    transform: translate(47%, -410%);
    font-family: Minecrafter;
    font-size: 40px;
    font-weight: lighter;
    color: #cde3cb;
    text-align: left;
    animation: titleReveal 1.9s ease-in-out;

  

}

@keyframes titleReveal {
    0% {
        opacity: 0;
        transform: translate(47%, -450%);
    }
    100% {
        opacity: 1;
        transform: translate(47%, -410%);
    }
}





.input_registro:focus {
    outline: none;
    background-color: #30642b;
    color: #cde3cb;
    box-shadow: 0 0 5px #e0ecdf;

}









.boton_registro button {
    
    transform: translate(90%, -440%);
    font-family: Mochi Green;
    color: #cde3cb;
    width: 52%;
    padding: 10px;
    border: none;
    border-radius: 5px;
    background-color: #0B2B22;
    font-size: 16px;
    animation: buttonReveal 2s ease-in-out;
}

@keyframes buttonReveal {
    0% {
        opacity: 0;
        transform: translate(90%, -480%);
    }
    100% {
        opacity: 1;
        transform: translate(90%, -440%);
    }
} 



.boton_registro button:hover {

    transition: ease-in-out 0.3s;
    color: #0B2B22;
    background-color: #cde3cb;
    cursor: pointer;
}




.sesion h2 {
    font-weight: lighter;
    color: #cde3cb;
    font-family: Minecraft;
    transform:translate(48%, -590%);
    animation: sessionReveal 2s ease-in-out;
}

@keyframes sessionReveal {
    0% {
        opacity: 0;
        transform: translate(48%, -630%);
    }
    100% {
        opacity: 1;
        transform: translate(48%, -590%);
    } 
}

.sesion a {
    color: #6F8F3D;
    text-decoration: underline;
    text-shadow: 0px 0px 5px #00000049;
}

.sesion a:hover {
    color: #cde3cb;
}



.mensaje {
    position: absolute;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 10px 20px;
    border-radius: 5px;
    font-family: Arial, sans-serif;
    text-align: center;
    z-index: 10;
}

.mensaje.error {
    background-color: #8b2e2e;
    color: white;
}


    </style>










<header>






</header>







<body>

 <video autoplay muted loop id="videobackground">
    <source src="..\data\bggg.mp4" type="video/mp4">
    </video>
   





<div class="Panel_registro">

<?php if ($mensaje !== ""): ?>

    <div class="mensaje <?php echo $tipoMensaje; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>

<?php endif; ?>



    <div class="titulo_registro">


      <h1>

         PEAK
      </h1>

    </div>  


      <div class="titulo">

          
          <h2>
            
            Registro
          </h2>

     </div>





    <div class="registro">

     <form method="POST" action="" class="registro">

    <input 
        type="text" 
        name="nombre"
        placeholder="Nombre" 
        class="input_registro"
        required
    >

    <input 
        type="email" 
        name="correo"
        placeholder="Correo Electronico" 
        class="input_registro"
        required
    >

    <input 
        type="password" 
        name="password"
        placeholder="Contraseña" 
        class="input_registro"
        required
    >

     <div class="boton_registro">
        <button type="submit">Registrarse</button>
    </div>

</form>


    </div>


 
   

    <div class="sesion">
        
        <h2>
            
         ¿Ya tienes una cuenta? <a href="login.php"> 
            INICIA SESION!!! </a>
        </h2>

    </div>

</div>








</body>

















</html>
