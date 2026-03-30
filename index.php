<?php 

session_start();
include 'php/connection.php';
include 'php/input_validation.inc.php';
include 'php/users.php';
include 'php/modal.php';
include 'php/activity-log.php';

$user = new user;

#
# LANDING LINKS MANAGERS
#

if (isset($_SESSION['loggedin'])) {
    sendToRespectivePortals();
}

function sendToRespectivePortals() {
    $user = new user;

    if ($user->isStudent()) {
        header('location: student');
    } else if ($user->isTeacher()) {
        header('location: teacher');
    } else if ($user->isFaculty()) {
        header('location: faculty');
    }
}

#
# THE LOG IN FEATURE
#

if (isset($_POST['login'])) {
    prepareUserLogin();
}

function prepareUserLogin() {
    $con = connect();

    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = validateInput($_POST['email']);
        $password = validateInput($_POST['password']);
    } else {
        failedLogin();
    }

    if (isUserEmailCorrect($email) && isUserPasswordCorrect($email, $password)) {
        loginUser();
        header('location: index.php');
        exit(0);
    } else {
        failedLogin();
    }
}

function failedLogin() {
    $_SESSION['execution'] = 'failedLogin';
    $_SESSION['loginTries']--;
    $_SESSION['lastAttempt'] = time();
    header('location: ?failedLogin');
    exit(0);
}

if (isset($_SESSION['lastAttempt']) && $_SESSION['lastAttempt'] + 60 <= time()) {
    $_SESSION['loginTries'] = 3;
    unset($_SESSION['lastAttempt']);
} 

if (!isset($_SESSION['loginTries'])) {
    $_SESSION['loginTries'] = 3;
}

function setAllSessions() {
    $con = connect();

    $email = $_POST['email'];

    $sql = "SELECT * FROM user_credentials WHERE email = ?";  
    $stmt = $con->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $_SESSION['id'] = $row['id'];
    if (isset($_SESSION['invalidLogin'])) {
        session_unset($_SESSION['invalidLogin']);  
    }  
}

function setUserStateToLogin() {
    $_SESSION['loggedin'] = true;
}

function loginUser() {
    setAllSessions();
    setUserStateToLogin();
    
    $activityLog = new activityLog;
    $activityLog->recordActivityLog('has logged in.');
    $_SESSION['invalidLogin'] = false;
    $_SESSION['loginTries'] = 3;
}

function isUserPasswordCorrect($email, $password) {
    $con = connect();

    $sql = "SELECT * FROM user_credentials WHERE email = ?";  
    $stmt = $con->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return password_verify($password, $row['password']);
}

function isUserEmailCorrect($email) {
    $con = connect();

    $sql = "SELECT * FROM user_credentials WHERE email = ?";  
    $stmt = $con->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows;  
}

?>

<!DOCTYPE html>
<html lang="es" UTF="8">
<head>
    <link rel="shortcut icon" href="logo-ufhec.ico" type="image/x-icon">
    <link rel="stylesheet" href="js/aos/dist/aos.css">
    <link rel="stylesheet" href="styles/query.css">
    <link rel="stylesheet" href="styles/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="styles/modal.css">
    <link rel="stylesheet" href="styles/index.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido | UFHEC</title>
    <style>
        /* Estilos adicionales para mejorar la sección de historia */
        .history-section-enhanced {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .history-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .history-card:hover {
            transform: translateY(-5px);
        }
        
        .history-card-inner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 2rem;
            padding: 2rem;
        }
        
        .history-image-container {
            flex: 0 0 200px;
            text-align: center;
        }
        
        .history-image-container img {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #8B0000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        
        .history-image-container img:hover {
            transform: scale(1.05);
        }
        
        .history-text-container {
            flex: 1;
        }
        
        .history-text-container h3 {
            color: #8B0000;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            border-left: 4px solid #8B0000;
            padding-left: 1rem;
        }
        
        .history-text-container p {
            line-height: 1.8;
            color: #333;
            text-align: justify;
            font-size: 1rem;
        }
        
        .history-timeline {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 2rem;
            gap: 1rem;
        }
        
        .timeline-item {
            flex: 1;
            min-width: 200px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .timeline-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .timeline-year {
            font-size: 2rem;
            font-weight: bold;
            color: #8B0000;
            display: block;
        }
        
        .timeline-text {
            font-size: 0.9rem;
            color: #555;
            margin-top: 0.5rem;
        }
        
        .history-highlight {
            background: linear-gradient(120deg, #f9f9f9 0%, #f0f0f0 100%);
            padding: 1.5rem;
            border-radius: 15px;
            margin-top: 1.5rem;
            border-left: 4px solid #8B0000;
        }
        
        .history-highlight p {
            margin: 0;
            font-style: italic;
            color: #555;
        }
        
        @media (max-width: 768px) {
            .history-card-inner {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }
            
            .history-text-container h3 {
                border-left: none;
                border-bottom: 3px solid #8B0000;
                padding-bottom: 0.5rem;
            }
            
            .history-image-container img {
                width: 140px;
                height: 140px;
            }
            
            .timeline-item {
                min-width: 150px;
            }
        }
    </style>
</head>
<body>

    <div id="loader">
        <div class="container">
            <div class="wrapper">
                <img src="assets\images\Logo-Horizontalufh.png" alt="UFHEC LOGO">
                <h4 class="loader-main-text">El sistema está cargando. <br> Espere por favor...</h4>
                <p class="loader-sub-text">Programación III</p>
                <i class="spinner fas fa-circle-notch fa-spin"></i>
            </div>
        </div>
    </div>
    
    <header>
        <nav>
            <div class="container">
                <div class="ausms-top">
                    <div class="au-logo-top">
                        <img src="assets\images\Logo-Horizontalufh.png" alt="UFHEC Logo">
                    </div>
                    <div class="ausms-top-texter">
                        <h2>Universidad UFHEC</h2>
                        <h4>Sistema de gestión estudiantil</h4>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <section id="loginUI">
        <div class="container">
            <div class="loginUI-wrapper">
                
                <div class="loginUI-left">
                    <div class="loginUI-left-image">
                        <img src="assets/images/LOGO-UFHEC-u-150x150.png" alt="UFHEC">
                    </div>
                    <div class="loginUI-left-text">
                        <h2>¡Es seguro y confiable!</h2>
                        <p>No compartas tu información con terceros.</p>
                    </div>
                </div>

                <div class="loginUI-right">
                    <div class="loginUI-right-text">
                        <h2>Acceso</h2>
                    </div>
                    <div class="loginUI-right-login-panel">
                        <div class="container">
                            <?php
                            if (isset($_SESSION['execution']) && $_SESSION['execution'] == 'failedLogin') {
                                ?>
                                    <h3 class="incorrect-creds"><i class="fas fa-exclamation-circle"></i> Credenciales incorrectas, intenta de nuevo.</h3>
                                    <p class="attempt-text">Intentos restantes: <?php echo $_SESSION['loginTries'] ?></p>
                                <?php
                                unset($_SESSION['execution']);
                            }
                            ?>
                            <form action="index.php" method="post">
                                <div class="input-contain">
                                    <label>Email</label>
                                    <div class="input-wrap">
                                        <i class="fas fa-user"></i>
                                        <input type="text" name="email">
                                    </div>
                                </div>
                                <div class="input-contain">
                                    <label>Contraseña</label>
                                    <div class="input-wrap">
                                        <i class="fas fa-key"></i>
                                        <input id="password" type="password" name="password">
                                        <i id="showPasswordButton" class="fas fa-lock"></i>
                                    </div>
                                </div>
                                <div class="loginUI-buttons">
                                    <?php 
                                    if ($_SESSION['loginTries'] != 0) {
                                        ?>
                                            <input type="submit" name="login" value="INGRESAR">
                                        <?php
                                    } else {
                                        ?>
                                            <button type="button" class="attempt-reach"><i class="fas fa-ban"></i> Espera 1 minuto</button>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section id="home-of-the-chiefs">
        <div class="container">
            <div class="hogar-de-los-leones-wrapper">
                <div data-aos="fade-up" data-aos-duration="5000" class="hogar-de-los-leones-images">
                    <img src="assets\images\home of the chiefs\logo leone.jfif" alt="Logo Leones">
                </div>
                <div data-aos="fade-up" class="Hogar-de-los-Leones-del-Escogido-texter">
                    <h1>Universidad UFHEC</h1>
                    <h3>Hogar de los Leones del Escogido</h3>
                </div>
            </div>
        </div>
    </section>

    <section id="about-us">
        <div class="container">
            <div class="about-us-texter">
                <h1>Universidad UFHEC</h1>
                <p>La Universidad UFHEC cuenta con más de 7 campus en todo el país</p>
            </div>
            <div class="about-us-wrapper">
                <!-- Campus cards (mantener igual) -->
                <div data-aos="fade-up" data-aos-duration="100" class="about-card">
                    <div class="about-card-top">
                        <img src="assets\images\campuses\Campus Metropolitano.jpg" alt="Campus Metropolitano">
                    </div>
                    <div class="about-card-content">
                        <div class="container">
                            <h2>Campus Metropolitano</h2>
                            <p>Distrito Nacional S.D.</p>
                        </div>
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-duration="500" class="about-card">
                    <div class="about-card-top">
                        <img src="assets\images\campuses\Campus Santo Domingo herrera.jpg" alt="Campus Herrera">
                    </div>
                    <div class="about-card-content">
                        <div class="container">
                            <h2>Campus Herrera</h2>
                            <p>Santo Domingo Herrera</p>
                        </div>
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-duration="700" class="about-card">
                    <div class="about-card-top">
                        <img src="assets\images\campuses\Campus La Romana.jpg" alt="Campus La Romana">
                    </div>
                    <div class="about-card-content">
                        <div class="container">
                            <h2>Campus La Romana</h2>
                            <p>La Romana</p>
                        </div>
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-duration="1200" class="about-card">
                    <div class="about-card-top">
                        <img src="assets\images\campuses\Campus Baní.jpg" alt="Campus Baní">
                    </div>
                    <div class="about-card-content">
                        <div class="container">
                            <h2>Campus Baní</h2>
                            <p>Baní</p>
                        </div>
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-duration="1500" class="about-card">
                    <div class="about-card-top">
                        <img src="assets\images\campuses\herrera 2 campus.jfif" alt="Campus Herrera II">
                    </div>
                    <div class="about-card-content">
                        <div class="container">
                            <h2>Campus Herrera II</h2>
                            <p>Santo Domingo Oeste</p>
                        </div>
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-duration="1700" class="about-card">
                    <div class="about-card-top">
                        <img src="assets\images\campuses\campus san juan.jpg" alt="Campus San Juan">
                    </div>
                    <div class="about-card-content">
                        <div class="container">
                            <h2>Campus San Juan</h2>
                            <p>San Juan de la Maguana</p>
                        </div>
                    </div>
                </div>

                <div data-aos="fade-up" data-aos-duration="2000" class="about-card">
                    <div class="about-card-top">
                        <img src="assets\images\campuses\Campus Las Américas.png" alt="Campus Las Américas">
                    </div>
                    <div class="about-card-content">
                        <div class="container">
                            <h2>Campus Las Américas</h2>
                            <p>Santo Domingo Este</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN DE HISTORIA MEJORADA -->
    <section id="au-history">
        <div class="container">
            <div class="ufhec-history-wrapper">
                <div class="ufhec-history-title">
                    
    <h1> Nuestra Historia</h1>
    <p>Más de 30 años formando profesionales de excelencia</p>
</div>
                    <p style="text-align: center; color: #666; margin-top: 1rem;">Conoce el legado de excelencia académica</p>
                </div>
                
                <div class="history-section-enhanced">
                    <!-- Card 1: Fundador -->
                    <div class="history-card" data-aos="fade-right" data-aos-duration="2000">
                        <div class="history-card-inner">
                            <div class="history-image-container">
                                <img src="assets\images\Francisco Henríquez y Carvajal.jpg" alt="Francisco Henríquez y Carvajal">
                            </div>
                            <div class="history-text-container">
                                <h3>Dr. Francisco Henríquez y Carvajal</h3>
                                <p>La Universidad UFHEC, la número 28 de las más de cincuenta instituciones de Educación Superior en la República Dominicana, 
                                adquiere su nombre a sugerencia de su fundador el Dr. Carlos Cornielle, rindiendo homenaje a la valía intelectual, 
                                patriotismo y legado educativo de uno de los profesionales más sólidos que haya producido el país: Federico Henríquez.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline de hitos -->
                    <div class="history-timeline" data-aos="fade-up" data-aos-duration="1500">
                        <div class="timeline-item">
                            <span class="timeline-year">1988</span>
                            <span class="timeline-text">Fundación patrocinadora</span>
                        </div>
                        <div class="timeline-item">
                            <span class="timeline-year">1991</span>
                            <span class="timeline-text">Decreto No. 57-91</span>
                        </div>
                        <div class="timeline-item">
                            <span class="timeline-year">1991</span>
                            <span class="timeline-text">Personalidad jurídica</span>
                        </div>
                        <div class="timeline-item">
                            <span class="timeline-year">2024</span>
                            <span class="timeline-text">7+ campus</span>
                        </div>
                    </div>

                    <!-- Card 2: Historia detallada -->
                    <div class="history-card" data-aos="fade-left" data-aos-duration="2000">
                        <div class="history-card-inner">
                            <div class="history-text-container">
                                <h3>Nuestro Compromiso</h3>
                                <p>La Universidad Federico Henríquez y Carvajal (UFHEC) surge como una institución de educación superior comprometida 
                                con la formación integral del ser humano, inspirada en los ideales de su fundador, el Dr. Carlos Cornielle. Su nombre 
                                refleja un legado basado en el conocimiento, el compromiso social y la ética profesional.</p>
                                
                                <div class="history-highlight">
                                    <p><strong>Misión:</strong> Formar profesionales competentes, con sentido crítico y compromiso ético frente a los desafíos de la sociedad contemporánea.</p>
                                </div>
                                
                                <p style="margin-top: 1rem;">Desde sus inicios, la UFHEC ha concebido la educación como un proceso ético y social, estrechamente vinculado 
                                con la realidad económica, política, cultural e ideológica de la sociedad. La expansión de sus sedes en distintas regiones 
                                del país evidencia su compromiso con el acceso a la educación, especialmente para sectores que históricamente han tenido 
                                menos oportunidades.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Filosofía -->
                    <div class="history-card" data-aos="fade-right" data-aos-duration="2000">
                        <div class="history-card-inner">
                            <div class="history-text-container">
                                <h3>Filosofía Institucional</h3>
                                <p>En la actualidad, la UFHEC se define como una comunidad de aprendizaje fundamentada en principios cristianos, 
                                orientada a cultivar las ciencias, promover las artes y fortalecer los valores culturales. Su filosofía institucional 
                                enfatiza la formación de ciudadanos íntegros, guiados por valores como la solidaridad, la justicia, la tolerancia y el servicio.</p>
                                
                                <div class="history-highlight">
                                    <p><strong>Visión:</strong> Ser una institución inclusiva, comprometida con el desarrollo humano y social, especialmente en favor de los más necesitados.</p>
                                </div>
                            </div>
                            <div class="history-image-container">
                                <img src="assets/images/arellanoold.JPG" alt="Campus Histórico UFHEC">
                            </div>
                        </div>
                    </div>

                    <!-- Datos clave -->
                    <div class="history-card" data-aos="fade-up" data-aos-duration="1500">
                        <div class="history-card-inner">
                            <div class="text-container" style="text-align: center;">
                                <h3>Datos Clave</h3>
                                <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; margin-top: 1rem;">
                                    <div>
                                        <i class="fas fa-calendar-alt" style="font-size: 2rem; color: #8B0000;"></i>
                                        <p><strong>Fundación:</strong><br>12 de febrero de 1991</p>
                                    </div>
                                    <div>
                                        <i class="fas fa-university" style="font-size: 2rem; color: #8B0000;"></i>
                                        <p><strong>Decreto:</strong><br>No. 57-91</p>
                                    </div>
                                    <div>
                                        <i class="fas fa-globe" style="font-size: 2rem; color: #8B0000;"></i>
                                        <p><strong>Campus:</strong><br>7+ en todo el país</p>
                                    </div>
                                    <div>
                                        <i class="fas fa-graduation-cap" style="font-size: 2rem; color: #8B0000;"></i>
                                        <p><strong>Estudiantes:</strong><br>Miles egresados</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section data-aos="flip-left" data-aos-duration="1300" id="au-tagline">
        <div class="container">
            <h1>Ciencia, Tecnología e Innovación para el Futuro</h1>
        </div>
    </section>
    
</body>

<footer>
    <div class="container">
        <div class="foot-title">
            <img src="assets\images\Logo-Horizontalufh.png" alt="UFHEC Logo">
        </div>
        <div class="foot-wrapper">
            <div class="foot-left">
                <ul>
                    <h3>Desarrollador</h3>
                    <li>Ramón Martínez</li>
                </ul>
            </div>
            <div class="foot-middle">
                <ul>
                    <h3>Docente</h3>
                    <li>FAUSTO LORENZO</li>
                </ul>            
            </div>
            <div class="foot-right">
                <ul>
                    <h3>Lenguaje</h3>
                    <li><i class="fas fa-elephant"></i> <i class="fab fa-js"></i> <i class="fab fa-jquery"></i></li>
                </ul>
                <ul>
                    <h3>System Design</h3>
                    <li><i class="fab fa-figma"></i> <i class="fab fa-html5"></i> <i class="fab fa-sass"></i></li>
                </ul>
                <ul>
                    <h3>Database</h3>
                    <li><i class="fas fa-database"></i> MySQL</li>
                </ul>
                <ul>
                    <h3>System Info</h3>
                    <li><i class="fas fa-code"></i> 19,000+ Lines</li>
                </ul>               
            </div>
        </div>
        <div class="foot-bottom">
            <p>2025 - 2026</p>
        </div>
    </div>
</footer>

</html>
<script src="js/aos/dist/aos.js"></script>
<script> AOS.init(); </script>
<script src="js/jquery-3.5.1.min.js"></script>
<script>

$(window).on('load', function() {
    $("#loader").fadeOut("slowest");
});

$(document).ready(function () {
    $('#home-of-the-chiefs').ready(() => {
        var currentNumber = 0;
        let time = setInterval(() => {
            changeBackground();
        }, 3000);

        function changeBackground() {
            let images = [
                'Johnny-Cueto.jpg',
                'juadores.jfif',
                '-lista-jugadores.jpg',
                'logo leone.jfif'
            ];

            if (currentNumber == images.length - 1) {
                currentNumber = 0;
            } else {
                currentNumber++;
            }

            let backgroundImage = images[currentNumber];
            $('#home-of-the-chiefs').css('background-image', `url('assets/images/home of the chiefs/${backgroundImage}')`);
        }
    });

    $('#showPasswordButton').click(function() {
        if (isPasswordHidden()) {
            makePasswordVisible();
        } else {
            makePasswordHidden();
        }

        function makePasswordHidden() {
            $('#password').attr('type', 'password');
            $('#showPasswordButton')
                .removeClass('fa-unlock')
                .addClass('fa-lock')
                .css('color', 'red')
                .css('transform', 'rotate(-360deg)');
        }

        function makePasswordVisible() {
            $('#password').attr('type', 'text');
            $('#showPasswordButton')
                .removeClass('fa-lock')
                .addClass('fa-unlock')
                .css('color', 'green')
                .css('transform', 'rotate(360deg)');
        }

        function isPasswordHidden() {
            return $('#password').attr('type') == 'password';
        }
    });
});

</script>