<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Headers agresivos para limpiar caché
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("ETag: " . md5(time()));

// Opcional: Refrescar sesión o registrar actividad
$_SESSION['last_access'] = time();

// Generar versión única basada en timestamp para forzar recarga de assets
$version = time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="styles_dark.css?v=2" />

    <title>Cálculo Balances Uci</title>
    <link rel="icon" href="icono.png" type="image/png" />
    <meta
      name="description"
      content="Dedicada al cálculo de los balances diarios en nuestra uci."
    />
    <meta name="keywords" content="balance, uci" />
    <meta name="autor" content="José Antonio Márquez García" />
    <script
      src="https://kit.fontawesome.com/2bbb9659be.js"
      crossorigin="anonymous"
    ></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
      rel="stylesheet"
    />

    <link rel="stylesheet" href="styles_dark.css" />
  </head>

  <body>
      
    <button class="switch" id="switch">
      <span><i class="bx bxs-sun"></i></span>
      <span><i class="bx bxs-moon"></i></span>
    </button>
    
    <!-- BOTÓN “SALIR” -->
<form action="logout.php"
      method="post"
      class="logout-form logout-fixed">   
  <button type="submit" class="btn-logout" title="Cerrar sesión">
    <i class="fas fa-sign-out-alt"></i>
    <span class="logout-text">Salir</span>
  </button>
</form>
    <!---------BOXES------->
    <h1 class="title">Balances</h1>
    
    <!-- Recuadro flotante de Box (oculto por defecto) -->
<div id="box-indicador-flotante" style="display:none">
  Box <span id="box-indicador-num"></span>
</div>

    


    <section id="box-navigation">
      <div class="container__nav">
        <nav>
          <ul>
            <li><a href="#" data-box="1">Box 1</a></li>
            <li><a href="#" data-box="2">Box 2</a></li>
            <li><a href="#" data-box="3">Box 3</a></li>
            <li><a href="#" data-box="4">Box 4</a></li>
            <li><a href="#" data-box="5">Box 5</a></li>
            <li><a href="#" data-box="6">Box 6</a></li>
            <li><a href="#" data-box="7">Box 7</a></li>
            <li><a href="#" data-box="8">Box 8</a></li>
            <li><a href="#" data-box="9">Box 9</a></li>
            <li><a href="#" data-box="10">Box 10</a></li>
            <li><a href="#" data-box="11">Box 11</a></li>
            <li><a href="#" data-box="12">Box 12</a></li>
          </ul>
        </nav>
      </div>
    </section>

    <!-- Enlace a balance -->

    <a
      href="https://jolejuma.es/evolucion-uci/app.php"
      class="icono-container"
    >
      <img src="img/evolucion_azul.png" alt="Balance" class="icono azul" />
      <img src="img/evolucion_verde.png" alt="Balance Azul" class="icono verde" />
    </a>
    
     <!---------------CERRAR SESION------------>


    <!----------------DATOS------------------->
    <!-- Contenedor para las cajas centrales -->
    <section id="box-data">
      <div class="container__datos_item">
        <div id="selected-box">
          <h2>Selecciona un Box</h2>
        </div>
        <div class="input-container">
          <div class="input-group">
            <label for="peso_box" class="label">Peso (kg):</label>
            <input
              id="peso_box"
              class="input"
              type="number"
              placeholder="Ingrese peso"
              min="1"
            />
          </div>
          <div class="input-group">
            <label for="horas_desde_ingreso_box" class="label"
              >Horas desde Ingreso:</label
            >
            <input
              id="horas_desde_ingreso_box"
              class="input input__horas"
              type="number"
              placeholder="Horas desde ingreso"
              min="0"
              max="24"
            />
          </div>
        </div>
      </div>
      <div class="button">
        <button id="borrar-datos-principal">Borrar Datos</button>
      </div>
    </section>

    <div class="row">
      <!---------PERDIDAS------->

      <section id="box-losses">
        <div class="container-perdidas-item">
          <h2>Pérdidas</h2>
          <table class="table__perdidas">
            <thead>
              <tr>
                <th id="table__perdidas-concepto">Concepto</th>
                <th id="table__perdidas-valor">Valor</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Diuresis</td>
                <td>
                  <input
                    id="perdida_orina_box"
                    class="input perdida"
                    type="number"
                    placeholder="Diuresis"
                  />
                </td>
              </tr>
              <tr>
                <td>Vómitos, Sudor, Heces <i
                    class="bx bx-calculator calculadora-icon vomitos-icon"
                    data-target="perdida_vomitos_box"
                    data-type="vomitos"
                  ></i></td>
                <td>
                  <input
                    id="perdida_vomitos_box"
                    class="input perdida"
                    type="number"
                    placeholder="Vómitos Sudor Heces"
                  />
                </td>
              </tr>
              <tr>
                <td>Fiebre >37°C</td>
                <td>
                  <input
                    id="fiebre37_horas_box"
                    class="input perdida input__horas"
                    type="number"
                    placeholder="Horas con Fiebre >37°C"
                  />

                  <input
                    id="fiebre37_calculo_box"
                    class="input readonly perdida"
                    type="number"
                    readonly
                    placeholder="Cálculo de Fiebre >37°C"
                  />
                </td>
              </tr>
              <tr>
                <td>Fiebre >38°C</td>
                <td>
                  <input
                    id="fiebre38_horas_box"
                    class="input perdida input__horas"
                    type="number"
                    placeholder="Horas con Fiebre >38°C"
                  />
                  <input
                    id="fiebre38_calculo_box"
                    class="input perdida readonly"
                    type="number"
                    placeholder="Cálculo de Fiebre >38°C"
                    readonly
                  />
                </td>
              </tr>
              <tr>
                <td>Fiebre >39°C</td>
                <td>
                  <input
                    id="fiebre39_horas_box"
                    class="input perdida input__horas"
                    type="number"
                    placeholder="Horas con Fiebre >39°C"
                    min="0"
                    max="24"
                  />
                  <input
                    id="fiebre39_calculo_box"
                    class="input perdida readonly"
                    type="number"
                    placeholder="Cálculo de Fiebre >39°C"
                    readonly
                  />
                </td>
              </tr>
              <tr>
                <td>Rpm >25</td>
                <td>
                  <input
                    id="rpm25_horas_box"
                    class="input perdida input__horas"
                    type="number"
                    placeholder="Horas con Rpm >25"
                    min="0"
                    max="24"
                  />
                  <input
                    id="rpm25_calculo_box"
                    class="input perdida readonly"
                    type="number"
                    readonly
                    placeholder="Cálculo de Rpm>25"
                  />
                </td>
              </tr>
              <tr>
                <td>Rpm >35</td>
                <td>
                  <input
                    id="rpm35_horas_box"
                    class="input perdida input__horas"
                    type="number"
                    placeholder="Horas con Rpm >35"
                    min="0"
                    max="24"
                  />
                  <input
                    id="rpm35_calculo_box"
                    class="input perdida readonly"
                    type="number"
                    readonly
                    placeholder="Cálculo de Rpm>35"
                  />
                </td>
              </tr>
              <tr>
                <td>
                  Sng / C.Residuos
                  <i
                    class="bx bx-calculator calculadora-icon sng-icon"
                    data-target="perdida_sng_box"
                    data-type="sng"
                  ></i>
                </td>
                <td>
                  <input
                    id="perdida_sng_box"
                    class="input perdida"
                    type="number"
                    placeholder="Sng"
                  />
                </td>
              </tr>
              
              <tr>
                <td>Hdfvvc</td>
                <td>
                  <input
                    id="perdida_hdfvvc_box"
                    class="input perdida"
                    type="number"
                    placeholder="Hdfvvc"
                  />
                </td>
              </tr>
              
              <tr>
                <td>
                  Drenajes
                  <i
                    class="bx bx-calculator calculadora-icon drenajes-icon"
                    data-target="perdida_drenajes_box"
                    data-type="drenajes"
                  ></i>
                </td>
                <td>
                  <input
                    id="perdida_drenajes_box"
                    class="input perdida"
                    type="number"
                    placeholder="Drenajes"
                  />
                </td>
              </tr>
              
              <tr>
                <td>Pérdidas Insensibles</td>
                <td>
                  <input
                    id="perdidas_insensibles_box"
                    class="input perdida readonly"
                    type="number"
                    readonly
                    placeholder="Pérdidas Insensibles"
                  />
                </td>
              </tr>
              <tr>
                <td>Cálculo de Vómitos, Sudor, Fiebre, Tqn y Heces</td>
                <td>
                  <input
                    type="number"
                    id="perdida_fuerafluidos_box"
                    class="input perdida readonly"
                    placeholder="Vómitos,Sudor,Fiebre,Tqn"
                    readonly
                  />
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <th>Total Pérdidas</th>
                <td>
                  <input
                    id="total_perdidas_box"
                    class="input perdida readonly"
                    type="number"
                    readonly
                    placeholder="Total Pérdidas"
                  />
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="button">
  <button id="borrar-perdidas">Borrar Pérdidas</button>
</div>
      </section>

      <!-------INGRESOS----------->

      <section id="box-earings">
        <div class="container-ingresos-item">
          <h2 class="title">Ingresos</h2>
          <table class="table__ingresos">
            <thead>
              <tr>
                <th id="table__ingresos-concepto">Concepto</th>
                <th id="table__ingresos-valor">Valor</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Midazolam</td>
                <td>
                  <input
                    id="ingreso_midazolam_box"
                    class="input ingreso"
                    type="number"
                    placeholder="Midazolam"
                  />
                </td>
              </tr>
              <tr>
                <td>Fentanest</td>
                <td>
                  <input
                    id="ingreso_fentanest_box"
                    class="input ingreso"
                    type="number"
                    placeholder="Fentanest"
                  />
                </td>
              </tr>
              <tr>
                <td>Propofol</td>
                <td>
                  <input
                    id="ingreso_propofol_box"
                    class="input ingreso"
                    type="number"
                    placeholder="Propofol"
                  />
                </td>
              </tr>
              <tr>
                <td>Remifentanilo</td>
                <td>
                  <input
                    id="ingreso_remifentanilo_box"
                    class="input ingreso"
                    type="number"
                    placeholder="Remifentanilo"
                  />
                </td>
              </tr>
              <tr>
                <td>Dexdor</td>
                <td>
                  <input
                    id="ingreso_dexdor_box"
                    class="input ingreso"
                    type="number"
                    placeholder="Dexdor"
                  />
                </td>
              </tr>
              <tr>
                <td>Noradrenalina</td>
                <td>
                  <input
                    id="ingreso_noradrenalina_box"
                    class="input ingreso"
                    type="number"
                    placeholder="Noradrenalina"
                  />
                </td>
              </tr>
              <tr>
                <td>Insulina</td>
                <td>
                  <input
                    id="ingreso_insulina_box"
                    class="input ingreso"
                    type="number"
                    placeholder="Insulina"
                  />
                </td>
              </tr>
              <tr>
                <td>Sueroterapia 1</td>
                <td>
                  <input
                    type="number"
                    id="ingreso_sueroterapia1_box"
                    class="input ingreso"
                    placeholder="Sueroterapia 1"
                  />
                </td>
              </tr>
              <tr>
                <td>Sueroterapia 2</td>
                <td>
                  <input
                    type="number"
                    id="ingreso_sueroterapia2_box"
                    class="input ingreso"
                    placeholder="Sueroterapia 2"
                  />
                </td>
              </tr>
              <tr>
                <td>Sueroterapia 3</td>
                <td>
                  <input
                    type="number"
                    id="ingreso_sueroterapia3_box"
                    class="input ingreso"
                    placeholder="Sueroterapia 3"
                  />
                </td>
              </tr>
              <tr>
                <td>
                  Medicación
                  <i
                    class="bx bx-calculator calculadora-icon medicacion-icon"
                    data-target="ingreso_medicacion_box"
                    data-type="medicacion"
                  ></i>
                </td>
                <td>
                  <input
                    type="number"
                    id="ingreso_medicacion_box"
                    class="input ingreso"
                    placeholder="Medicación"
                  />
                </td>
              </tr>
              <tr>
                <td>
                  Sangre/Plasma
                  <i
                    class="bx bx-calculator calculadora-icon sangre-icon"
                    data-target="ingreso_sangreplasma_box"
                    data-type="sangre"
                  ></i>
                </td>
                <td>
                  <input
                    type="number"
                    id="ingreso_sangreplasma_box"
                    class="input ingreso"
                    placeholder="Sangre/Plasma"
                  />
                </td>
              </tr>
              <tr>
                <td>Agua Endógena</td>
                <td>
                  <input
                    id="ingreso_agua_endogena_box"
                    class="input ingreso readonly"
                    type="number"
                    readonly
                    placeholder="Agua Endógena"
                  />
                </td>
              </tr>
              <tr>
                <td>
                  Oral
                  <i
                    class="bx bx-calculator calculadora-icon oral-icon"
                    data-target="ingreso_oral_box"
                    data-type="oral"
                  ></i>
                </td>
                <td>
                  <input
                    type="number"
                    id="ingreso_oral_box"
                    class="input ingreso"
                    placeholder="Oral"
                  />
                </td>
              </tr>
              <tr>
                <td>Enteral</td>
                <td>
                  <input
                    type="number"
                    id="ingreso_enteral_box"
                    class="input ingreso"
                    placeholder="Enteral"
                  />
                </td>
              </tr>
              <tr>
                <td>Parenteral</td>
                <td>
                  <input
                    type="number"
                    id="ingreso_parenteral_box"
                    class="input ingreso"
                    placeholder="Parenteral"
                  />
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <th>Total Ingresos</th>
                <td>
                  <input
                    id="resumen_total_ingresos_box"
                    class="input ingreso readonly"
                    type="number"
                    readonly
                    placeholder="Total Ingresos"
                  />
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="button">
<button id="borrar-ingresos">Borrar Ingresos</button>
</div>
        </div>
      </section>
    </div>

    <!---------------TOTALES Y BALANCES------------>

    <section id="box-summary">
      <div class="container__totales-item">
        <table class="table-summary">
          <tr>
            <th>Total Ingresos</th>
            <td>
              <input
                id="balance_total_ingresos_box" 
                class="input ingreso readonly"
                type="number"
                readonly
                placeholder="Total Ingresos"
              />
            </td>
          </tr>
          <tr>
            <th>Total Pérdidas</th>
            <td>
              <input
                id="balance_total_perdidas_box" 
                class="input perdida readonly"
                type="number"
                readonly
                placeholder="Total Pérdidas"
              />
            </td>
          </tr>
          <tr>
            <th>Balance Total</th>
            <td>
              <input
                id="balance_total_box"
                class="input balance readonly"
                type="number"
                readonly
                placeholder="Balance Total"
              />
            </td>
          </tr>
        </table>
      </div>
    </section>
    
        


    
    

    <!---------------FOOTER------------>

    <footer>
      <p>
        &copy; 2025 Balance. Todos los derechos reservados. <br />C.G.Francisco
        Manuel--M.G.José Antonio--M.M. Francisco Javier
      </p>
     
    </footer>
    <script src="script_dark.js"></script>
    <script src="calculadora.js"></script>
  </body>
</html>
