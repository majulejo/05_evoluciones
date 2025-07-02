<?php
session_start();
//app.php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
date_default_timezone_set('Europe/Madrid');

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Después de verificar la sesión, añade esto antes del HTML:
$username = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="robots" content="noindex, nofollow">
  <meta name="referrer" content="no-referrer-when-downgrade">
  <meta name="description" content="Registro de informes  de Evolución UCI.">
  <meta name="author" content="Equipo de Desarrollo - Evolución UCI">    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
  <link rel="icon" href="img/evo-uci.png?v=3" type="image/png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet" />

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <title>Evolución de Enfermería</title>



  <style>
    :root {
      --fuente: #368f3f;
      --fuente-active: #2e2925;
      --hover: #d9ebd8;
      --borde: #4fa66a;
      --fuente15: #79b47f;
      --btn: #489950;
      --text-area:#ffffff;
      
      --color-fondo: #f4f7fa;
      --color-primario: #2d6b3a;
      --color-secundario: #368f3f;
      --color-hover: #489950;
      --color-texto: #2e2925;
      --color-blanco: #fff;
      --radio: 10px;
    }

    * {
      margin: 0;
      padding: 0;
      outline: none;
      box-sizing: border-box;
    }

    body {
      font-family: "Montserrat", sans-serif;
      margin: 20px;
      padding: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      background-color: var(--color-fondo);
    }

    h1 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 24px;
      color: var(--fuente);
    }

    .box-selector {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 20px;
    }

    .box-selector button {
      flex: 0 0 auto;
      /* Que no estiren ni encojan */
      margin: 4px;
      /* Espaciado uniforme */
    }


    .box-selector button {
      padding: 10px 15px;
      font-size: 14px;
      font-weight: bold;
      cursor: pointer;
      border: none;
      border-radius: 5px;
      background-color: var(--fuente15);
      color: white;
      min-width: 80px;
    }

    .box-selector button:hover {
      background-color: var(--text-area);
      color: var(--fuente);
      transition: all 0.5s ease;
    }

    .box-selector button.active {
      background-color: var(--fuente);
      color: var(--fuente-active);
      box-shadow: 0 0 5px #0000004d;
      transition: all 0.5s ease;
    }

    .formulario {
      width: 100%;
      max-width: 600px;
      margin-bottom: 20px;
    }

    .campo {
      margin-bottom: 15px;
      text-align: center;
    }

    .campo label {
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
      font-size: 14px;
      color: var(--fuente);
    }

    .campo textarea {
      width: 95%;
      min-height: 80px;
      padding: 10px;
      font-size: 14px;
      border: 1px solid var(--borde);
      border-radius: 5px;
      resize: vertical;
      background-color: var(--color-blanco);
      color: var(--fuente);
      outline: none;
      overflow: auto;
      overflow-y: hidden;
    }

    .campo textarea.habilitado {
  background-color: var(--text-area) !important;
  color: var(--fuente) !important;
  cursor: text !important;
  pointer-events: auto !important;
}

.campo textarea:not(:disabled) {
  background-color: var(--color-blanco) !important;
  color: var(--fuente) !important;
  cursor: text !important;
}

    .campo textarea:disabled {
  background-color: var(--color-fondo) !important;
  border: 1px solid var(--borde) !important;
  color: #6c757d !important;
  cursor: not-allowed;
}



    .contador-global {
      text-align: right;
      font-weight: 400;
      font-size: 10px;
      color: var(--fuente);
      margin-top: -10px;
      margin-bottom: 10px;
    }

    /* Contenedores de botones principales y de imprimir */
    .main-actions-container,
    .print-actions-container {
      display: flex;
      justify-content: center;
      /* Centra los botones */
      margin-top: 20px;
      flex-wrap: wrap;
      gap: 10px;
      width: 100%;
      max-width: 600px;
      /* Ancho de referencia */
    }

    .main-actions-container button,
    .print-actions-container button,
    .print-actions-container .btn-alternativo {
      padding: 10px 15px;
      font-size: 14px;
      cursor: pointer;
      border: none;
      border-radius: 5px;
      background-color: var(--btn);
      color: #ffffff;
      flex: 1;
      /* Permite que los botones crezcan y se encojan */
      min-width: 120px;
      /* Mínimo para evitar que se hagan demasiado pequeños */
      font-weight: bold;
      transition: background-color 0.3s ease-in-out;
    }

    .main-actions-container button:hover,
    .print-actions-container button:hover,
    .print-actions-container .btn-alternativo:hover {
      background-color: var(--fuente15);
      color: var(--fuente);
    }

    .print-actions-container .btn-alternativo {
      background-color: white;
      color: var(--fuente);
    }

    /* Estilo para los iconos dentro de los botones en pantallas grandes */
    .main-actions-container button i,
    .print-actions-container button i,
    .eliminar-buttons-row .eliminarInforme i,
    .copiar-btn i {
      font-size: 16px;
      /* Tamaño pequeño para los iconos en desktop */
      margin-right: 8px;
      /* Espacio entre icono y texto */
    }

    /* Estilo general para el resultado del informe */
.resultado {
  margin-top: 20px;
  padding: 20px;
  font-family: "Montserrat", sans-serif;
  font-size: 12px;
  line-height: 1.2;
  display: none;
  background-color: #f5f5f5;
  width: 100%;
  max-width: 700px;
  box-sizing: border-box;
  word-wrap: break-word;
  white-space: pre-wrap;
  border-radius: 10px;
  box-shadow: none !important;
}

/* Cabecera del informe (BOX y turno) */
.resultado .cabecera {
  font-size: 14px;
  font-weight: 700;
  line-height: 1.3;
  margin-bottom: 4px; /* Separación mínima */
}

/* Etiqueta fuerte (label) */
.resultado .label-strong {
  font-weight: 700;
}

/* Contenido del informe */
.resultado p {
  margin: 2px 0 !important;
  line-height: 1.2;
}

/* Para imprimir */
.imprimir-cabecera {
  margin: 0;
  font-weight: 700;
  font-size: 14px;
  line-height: 1.3;
  margin-bottom: 4px; /* Separación mínima */
}

.imprimir-label-strong {
  font-weight: 700;
}

.imprimir-texto-normal {
  font-weight: normal;
}

    }

   
    /* Opcional: Para asegurar que se aplique en la vista previa también */
    .resultado .imprimir-texto-normal {
      font-weight: normal;
    }

    .no-especificado {
      font-style: italic;
      color: #666;
    }


    

    .imprimir-parrafo {
      margin: 0;
      line-height: 1.2;
      /* hace que cada línea quede “muy pegada” a la siguiente */
    }

    


   /* MENSAJE PRINCIPAL (centrado bajo icono) */
#mensaje-box-seleccionado {
  text-align: center;
  font-weight: bold;
  margin-top: 5px;
  margin-bottom: 20px;
  color: var(--fuente);
  background-color: var(--text-area);
  border: 1px solid var(--fuente);
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 16px;
  display: none;
  width: fit-content;
  margin-left: auto;
  margin-right: auto;
  box-shadow: none !important;
}

/* INDICADOR FLOTANTE (a la izquierda) - Versión mejorada */
/* Indicador flotante minimalista */
#box-indicador-flotante {
  position: fixed;
  left: 10px;
  top: 10px;
  z-index: 1000;
  display: none;
  padding: 4px 8px;
  font-size: 12px;
  font-weight: normal;
  color: var(--fuente);
  background-color: transparent; /* Fondo transparente */
  border-radius: 4px;
  text-align: center;
  line-height: 1.3;
  backdrop-filter: blur(2px);
  border: 1px solid var(--fuente);
  max-width: 100px;
}

/* Versión móvil */
@media (max-width: 768px) {
  #box-indicador-flotante {
    font-size: 11px;
    padding: 3px 6px;
    max-width: 80px;
    left: 5px;
    top: 5px;
  }
}

/* Texto del usuario */
#usuario-indicador-flotante {
  font-size: 10px;
  font-weight: normal;
  opacity: 0.8;
  margin-top: 1px;
  display: block;
}

/* Versión móvil para texto usuario */
@media (max-width: 480px) {
  #usuario-indicador-flotante {
    font-size: 9px;
  }
}


    #mensaje-turno {
      text-align: center;
      font-weight: bold;
      margin-top: 10px;
      color: var(--fuente);
      display: none;
      font-size: 14px;
    }

    /* Estilo para el mensaje de confirmación temporal */
    #mensajeConfirmacion {
      text-align: center;
      padding: 10px;
      margin-top: 10px;
      background-color: #d4edda;
      /* Verde claro */
      color: #155724;
      /* Verde oscuro */
      border: 1px solid #c3e6cb;
      border-radius: 5px;
      font-weight: bold;
      display: none;
      /* Oculto por defecto */
      width: 100%;
      max-width: 600px;
      box-sizing: border-box;
    }

    /* Contenedor principal de las filas de select/botones de eliminar */
    .fecha-container {
      font-weight: bold;
      margin-top: 20px;
      /* Separación con los botones de arriba */
      color: var(--fuente);
      position: relative;
      overflow: visible !important;
      z-index: 1000;
      display: flex;
      flex-direction: column;
      /* Apila las filas verticalmente */
      align-items: center;
      /* Centra las filas horizontalmente */
      gap: 10px;
      /* Espacio vertical entre las filas */
      width: 100%;
      max-width: 600px;
      /* Alineado con el formulario y el grupo de botones principal */
    }

    /* Fila para el selector "Seleccionar Informe Guardado" */
    .select-row {
      display: flex;
      justify-content: center;
      /* Centra el selector */
      align-items: center;
      /* Alinea los elementos verticalmente */
      width: 100%;
      max-width: 600px;
      /* Mismo ancho que el contenedor principal de botones */
      gap: 8px;
      /* Espacio entre el icono y el select */
    }

    /* Contenedor para los dos botones de eliminar */
    .eliminar-buttons-row {
      display: flex;
      flex-direction: row;
      /* Coloca los botones en fila */
      justify-content: center;
      /* Centra los botones horizontalmente */
      gap: 10px;
      /* Espacio entre los botones */
      width: 100%;
      max-width: 600px;
      /* Mismo ancho que el contenedor principal de botones */
      flex-wrap: wrap;
      /* Permite que los botones se envuelvan en pantallas pequeñas */
    }

    /* Estilo para el select y los botones de eliminar (sin margin en los lados) */
    #informesGuardados,
    .eliminar-buttons-row .eliminarInforme {
      padding: 10px 15px;
      font-size: 14px;
      cursor: pointer;
      border: none;
      border-radius: 5px;
      background-color: var(--fuente15);
      /* Color de fondo consistente */
      color: var(--fuente);
      /* Color de texto consistente */
      flex: 1;
      /* Permite que los elementos crezcan y se encojan */
      min-width: 140px;
      /* Un mínimo para que no se hagan demasiado pequeños */
      font-weight: bold;
      transition: background-color 0.3s ease-in-out;
      margin: 0;
    }

    #informesGuardados:hover,
    .eliminar-buttons-row .eliminarInforme:hover {
      background-color: var(--btn);
      /* Hover consistente */
      color: #fff;
      /* Color de texto en hover consistente */
    }

    /* mensaje de aviso 1200 caracteres */
    

        #aviso-1200 {
  position: fixed;  /* Cambiado de sticky a fixed */
  top: 20%;     /* Posicionado en la parte inferior */
  left: 50%;        /* Centrado horizontalmente */
  transform: translateX(-50%); /* Ajuste fino para centrado perfecto */
  z-index: 1000;    /* Asegura que esté por encima de otros elementos */
  background-color: #ffefc1;
  color: #b35900;
  font-weight: bold;
  padding: 10px 20px;
  border-left: 5px solid #ffcc00;
  border-radius: 4px;
  font-size: 13px;
  text-align: center;
  max-width: 90%;   /* Para que no sea demasiado ancho en móviles */
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  animation: fadeIn 0.3s ease-in-out;
  display: none;    /* Oculto inicialmente */
}
        
        
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-5px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .contador-aviso {
      color: var(--fuente) !important;
    }

    .contador-alerta {
      color: red !important;
      font-size: 14px;
    }

    /* botón copiar informe */
    .copiar-btn {
      padding: 10px 15px;
      font-size: 14px;
      cursor: pointer;
      border: none;
      border-radius: 5px;
      background-color: var(--btn);
      color: white;
      font-weight: bold;
      transition: background-color 0.3s ease-in-out;
      margin-top: 10px;
      min-width: 120px;
    }

    .copiar-btn:hover {
      background-color: var(--fuente15);
    }

    /* listado informes guardados */
    /* NOTA: #informesGuardados directas son menos efectivas con Choices.js */
    #informesGuardados optgroup {
      font-weight: bold;
      line-height: 1.6;
      color: white;
      background-color: var(--fuente15);
      padding: 4px 12px;
    }

    #informesGuardados option {
      padding: 6px 12px;
      white-space: nowrap;
    }

    /* no especificado */
    .no-especificado {
      font-style: italic;
      color: #666;
      /* gris medio */
    }

    /* Estilo para el select nativo */
    #informesGuardados {
      /* Restablecer estilos de Choices.js que ya no son necesarios */
      -webkit-appearance: none;
      /* Elimina estilos nativos en Webkit */
      -moz-appearance: none;
      /* Elimina estilos nativos en Firefox */
      appearance: none;
      /* Elimina estilos nativos */
      background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%20viewBox%3D%220%200%20292.4%20292.4%22%3E%3Cpath%20fill%3D%22%232e2925%22%20d%3D%22M287%20197.9c-3.6%203.6-7.8%205.4-12.1%205.4s-8.5-1.8-12.1-5.4L146.2%2091.2%2029.6%20197.9c-3.6%203.6-7.8%205.4-12.1%205.4s-8.5-1.8-12.1-5.4c-7.2-7.2-7.2-18.4%200-25.6L134.1%206.5c7.2-7.2%2018.4-7.2%2025.6%200l108.7%20108.7c7.2%207.2%207.2%2018.4%200%2025.6z%22%2F%3E%3C%2Fsvg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      background-size: 12px;
      padding-right: 30px;
      /* Espacio para la flecha */
      text-align: center;
      /* Centra el texto */
      text-align-last: center;
      /* Para IE/Edge */
      padding-left: 30px;
      /* Equilibra el padding derecho para centrar visualmente */
    }

    /* Cuando el select está abierto, la lista de opciones no tendrá scroll */
    #informesGuardados:focus::-webkit-scrollbar,
    #informesGuardados:active::-webkit-scrollbar,
    #informesGuardados:hover::-webkit-scrollbar {
      width: 0;
      background: transparent;
      /* make scrollbar transparent */
    }

    /* Para Firefox */
    #informesGuardados {
      scrollbar-width: none;
      /* Firefox */
    }

    #informesGuardados option {
      white-space: normal;
      /* Permite que el texto se envuelva */
    }

    /* Estilo del icono para el select (ahora oculto por defecto) */
    .select-icon {
      font-size: 18px;
      /* Tamaño del icono */
      color: var(--fuente);
      /* Color del icono */
      display: none;
      /* Ocultar por defecto */
    }

    /* icono_balance */
    .icono-container {
      position: relative;
      display: inline-block;
      width: 30px;
      /* Ajusta el tamaño del icono */
      height: 30px;
      margin-bottom: 10px;
    }

    .icono {
      position: absolute;
      width: 100%;
      height: 100%;
      transition: opacity 0.5s ease-in-out;
      /* Transición suave */
    }

    /* Mostrar el icono verde por defecto */
    .icono.verde {
      opacity: 1;
    }

    /* Ocultar el icono azul por defecto */
    .icono.azul {
      opacity: 0;
    }

    /* Cuando se hace hover, la imagen verde desaparece y la azul aparece */
    .icono-container:hover .verde {
      opacity: 0;
    }

    .icono-container:hover .azul {
      opacity: 1;
    }

    /* Fin icono_balance */


   

    /* =============================== */
    /* FOOTER */
    /* =============================== */

    footer {
      color: var(--fuente);
      font-size: 10px;
      font-weight: bold;
      text-align: center;
      padding: 5px 0px 0px;
      margin: 20px auto 0px;
      width: 100%;
      z-index: 50;
      border-top: 1px solid var(--fuente);
    }

    /* fin del footer */
    
    
    /* MEDIA QUERIES PARA RESPONSIVIDAD */
    @media (max-width: 768px) {

      /* Ocultar los botones de imprimir en móvil */
      .print-actions-container {
        display: none;
      }

      /* Ocultar el botón de copiar informe en móvil */
      #copiarInformeBtn {
        display: none !important;
        /* Se mantiene oculto en móvil, !important para asegurar */
      }

      /* Contenedor de botones principales (Generar, Borrar) */
      .main-actions-container {
        flex-direction: row;
        /* Organizar en fila */
        justify-content: space-evenly;
        /* Espacio entre los elementos */
        width: 100%;
        max-width: 90%;
        /* Ajusta el ancho máximo para móviles */
        align-items: center;
        /* Centra los elementos verticalmente */
        gap: 20px;
        /* Espacio más pequeño entre botones en fila */
      }

      /* Contenedor de botones de eliminar */
      .eliminar-buttons-row {
        flex-direction: row;
        /* Ya está en fila, asegurar */
        justify-content: space-evenly;
        /* Espacio entre los elementos */
        width: 100%;
        max-width: 90%;
        align-items: center;
        gap: 20px;
        /* Espacio más pequeño entre botones en fila */
      }

      /* Ocultar el texto de los botones y mostrar solo los iconos */
      .main-actions-container button,
      .eliminar-buttons-row .eliminarInforme {
        text-indent: -9999px;
        /* Mueve el texto fuera de la vista */
        overflow: hidden;
        /* Oculta el texto que se desborda */
        white-space: nowrap;
        /* Evita que el texto se envuelva */
        padding: 10px;
        /* Ajusta el padding para centrar el icono */
        min-width: 50px;
        /* Haz los botones más pequeños */
        max-width: 60px;
        /* Limita el ancho para que sean más cuadrados */
        display: flex;
        /* Usa flexbox para alinear icono y texto */
        align-items: center;
        justify-content: center;
        /* Centra el contenido horizontalmente */
      }

      /* Estilo para los iconos dentro de los botones en móvil */
      .main-actions-container button i,
      .eliminar-buttons-row .eliminarInforme i {
        font-size: 20px;
        /* Tamaño del icono */
        margin-right: 0;
        /* Elimina el margen a la derecha del icono ya que no hay texto */
        text-indent: 0;
        /* Asegura que el icono no se vea afectado por el text-indent del padre */
      }

      /* Ajuste específico para el icono de "Imprimir en Turno Alternativo" si tiene dos iconos */
      .print-actions-container .btn-alternativo i:first-child {
        margin-right: 4px;
      }

      /* Disminuir tamaño de fuente para el select de informes guardados en móvil */
      #informesGuardados {
        font-size: 11px;
        /* Reduce el tamaño de fuente del select */
      }

      #informesGuardados optgroup {
        font-size: 9px;
        /* Reduce el tamaño de fuente de los grupos de opciones */
      }

      #informesGuardados option {
        font-size: 9px !important;
        /* Reduce el tamaño de fuente de las opciones */
      }

      /* Asegura que el icono del select esté oculto en móvil */
      .select-icon {
        display: none;
      }
    }


  /* =============================== */
/* CSS DE IMPRESIÓN MEJORADO - GARANTIZA UNA SOLA PÁGINA */
/* =============================== */
/* ESTILOS DE IMPRESIÓN CORREGIDOS - Reemplazar en app.php */
@media print {
  @page {
    size: A4 portrait;
    margin: 0;
  }

  body {
    visibility: hidden;
    margin: 0 !important;
    padding: 0 !important;
  }

  body > * {
    display: none !important;
  }

  .resultado {
    visibility: visible !important;
    display: block !important;
    position: absolute;
    left: 3cm;
    right: 0.5cm;
    font-family: "Montserrat", sans-serif;
    font-size: 12px;
    line-height: 1.2;
    background: white !important;
    padding: 0 !important;
    box-sizing: border-box;
    white-space: pre-wrap;
    overflow-wrap: break-word;
    word-wrap: break-word;
  }

  .resultado.diurno-print {
    top: 2cm;
  }

  .resultado.nocturno-print {
    top: auto;
    bottom: 2cm;
  }

  .resultado p {
    margin: 2px 0 !important;
    page-break-inside: avoid;
    line-height: 1.2;
    margin-right: 1.5cm !important;
    box-shadow: none !important;

  }

  .resultado p:first-of-type {
    margin-bottom: 0px !important;
  }

  /* Estilos específicos para la firma */
  .resultado .firma-alineada {
    text-align: right !important;
    margin-top: 4px !important;
    margin-bottom: 0 !important;
    font-size: 12px !important;
    line-height: 1.2 !important;
    page-break-before: avoid !important;
    page-break-after: avoid !important;
    page-break-inside: avoid !important;
  }

  /* Cabecera del informe */
  .resultado .cabecera {
    font-size: 14px !important;
    font-weight: 700 !important;
    line-height: 1.3 !important;
    margin-bottom: 4px !important;
    margin-top: 0 !important;
    page-break-after: avoid !important;
    page-break-inside: avoid !important;
  }

  /* Etiquetas en negrita */
  .resultado .label-strong {
    font-weight: 700 !important;
    font-size: inherit !important;
  }

  /* Texto "sin especificar" */
  .resultado .no-especificado {
    font-style: italic !important;
    color: #666 !important;
    font-size: inherit !important;
  }
}
/* =============================== */
/* FINAL DEL MEDIA PRINT */
/* =============================== */


/* =========================================== */
/*  POSICIÓN Y ESTILO FINAL  #logoutBtn        */
/*  (debe ir al FINAL de la hoja de estilos)   */
/* =========================================== */
#logoutBtn{
  position:fixed !important;   /* saca el botón del flujo y lo fija */
  top:16px   !important;       /* distancia al borde superior       */
  right:20px !important;       /* al borde derecho                  */
  left:auto  !important;
  bottom:auto!important;
  transform:none!important;

  /* apariencia coherente con la paleta verde */
  background:var(--fuente15);
  color:#fff;
  border:1px solid var(--borde);
  padding:8px 14px;
  font:700 .7rem/1 "Montserrat",sans-serif;
  display:flex;
  align-items:center;
  gap:.5rem;
  border-radius:8px;
  box-shadow:0 2px 4px rgba(0,0,0,.15);
  cursor:pointer;
  transition:background .25s, box-shadow .25s;
}

/* icono un poco mayor */
#logoutBtn i{font-size:1.1rem;line-height:1;}

/* hover / focus */
#logoutBtn:hover,
#logoutBtn:focus-visible{
  background:#fff;
  color:var(--fuente);
  box-shadow:0 2px 6px rgba(0,0,0,.25);
  outline:none;
}

/* oculta solo la palabra en pantallas pequeñas */
@media(max-width:480px){
  #logoutBtn span{display:none;}
}

/* ================================== */
/* ================================== */
/*                GOMA  Y FIRMA         */
/* ================================== */
/* ================================== */

/* Estilos para los campos con goma de borrar */
.campo {
  margin-bottom: 15px;
  text-align: center;
  position: relative;
  width: 95%;
  margin-left: auto;
  margin-right: auto;
}

.label-and-clear {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-bottom: 5px;
}

.campo label {
  font-weight: bold;
  display: inline-block;
  margin: 0;
  font-size: 14px;
  color: var(--fuente);
}

.clear-btn {
  background-color: transparent;
  border: none;
  color: #888;
  font-size: 14px;
  cursor: pointer;
  padding: 5px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
}

.clear-btn:hover {
  color: var(--fuente);
  background-color: rgba(0,0,0,0.05);
}

.clear-btn i {
  font-size: 14px;
}

/* Responsive para móviles */
@media (max-width: 768px) {
  .label-and-clear {
    gap: 5px;
  }
  
  .clear-btn {
    width: 20px;
    height: 20px;
  }
  
  .clear-btn i {
    font-size: 12px;
  }
}

#indicador-guardando {
    position: fixed; top: 30%; 
    right: 20px; 
    background: var(--fuente); 
    color: white; 
    padding: 8px 16px; 
    border-radius: 4px; 
    display: none; z-index: 1000;
}

/* ================================== */
/* ================================== */
/* ICONO DE INFORMACIÓN INGRESO     */
/* ================================== */
/* ================================== */


/* Estilo para el icono de información */
.info-icon {
  color: #666;
  font-size: 12px;
  margin-left: 5px;
  cursor: help;
  transition: color 0.3s ease;
}

.info-icon:hover {
  color: var(--fuente);
}

/* Ajuste para el contenedor del label cuando hay icono de info */
.label-and-clear .fa-info-circle {
  color: #666;
  font-size: 12px;
  margin-left: 5px;
  cursor: help;
  transition: color 0.3s ease;
}

.label-and-clear .fa-info-circle:hover {
  color: var(--fuente);
}

/* Responsive para móviles - ocultar icono de info si es necesario */
@media (max-width: 480px) {
  .label-and-clear .fa-info-circle {
    display: none;
  }
}
  </style>
</head>

<body data-username="<?php echo htmlspecialchars($username); ?>">
 

  <h1>Evolución de Enfermería</h1>
  <div id="contenidoApp" style="display: none"></div>
  <div class="box-selector" id="boxSelector"></div>
  

  <a href="https://jolejuma.es/evolucion-uci/index_balance_uci.php" class="icono-container">
    <img src="img/balance_verde.png" alt="Balance" class="icono verde" />
    <img src="img/balance_azul.png" alt="Balance Azul" class="icono azul" />
  </a>
  

  <!-- Mensaje centrado -->
<div id="mensaje-box-seleccionado">
  Ha seleccionado el Box <span id="numero-box-seleccionado-msg"></span>
</div>


<!--INDICADOR - GUARDANDO-->
<div id="indicador-guardando"> Guardando...</div>



<!-- Indicador discreto flotante -->
<!-- Indicador flotante -->
<!-- Indicador Usuario-flotante -->
<div id="box-indicador-flotante">
  Box <span id="numero-box-seleccionado-fijo"></span>
  <div id="usuario-indicador-flotante"></div>
</div>

  <div id="mensajeConfirmacion"></div>
  <form class="formulario" id="formulario">
      
        <!-- Ejemplo para el campo INGRESO -->
  
      <div class="campo">
  <div class="label-and-clear">
    <label for="ingreso">INGRESO</label>
    <button class="clear-btn" onclick="limpiarCampo('ingreso')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
    <i class="fas fa-info-circle" 
       title="Si está vacío, no se imprimirá en el informe" 
       style="color: #666; font-size: 12px; margin-left: 5px; cursor: help;">
    </i>
  </div>
  <textarea id="ingreso" disabled placeholder="Dejar vacío para no imprimir"></textarea>
</div>

    <!-- Ejemplo para el campo NEUROLÓGICO -->
<div class="campo">
  <div class="label-and-clear">
    <label for="neurologico">1. NEUROLÓGICO</label>
    <button class="clear-btn" onclick="limpiarCampo('neurologico')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
  </div>
  <textarea id="neurologico" disabled></textarea>
</div>

<!-- Ejemplo para el campo CARDIOVASCULAR -->
<div class="campo">
  <div class="label-and-clear">
    <label for="cardiovascular">2. CARDIOVASCULAR</label>
    <button class="clear-btn" onclick="limpiarCampo('cardiovascular')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
  </div>
  <textarea id="cardiovascular" disabled></textarea>
</div>

<!-- Ejemplo para el campo   RESPIRATORIO              -->
     <div class="campo">
        <div class="label-and-clear">
<label for="respiratorio">3. RESPIRATORIO</label>
     <button class="clear-btn" onclick="limpiarCampo('respiratorio')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button> 
    </div>
    <textarea id="respiratorio" disabled></textarea>
    </div>
<!-- Ejemplo para el campo       RENAL          -->
    <div class="campo">
        <div class="label-and-clear">
      <label for="renal">4. RENAL</label>
      <button class="clear-btn" onclick="limpiarCampo('renal')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
  </div>
    <textarea id="renal" disabled></textarea>
    </div>
<!-- Ejemplo para el campo  GASTROINTESTINAL               -->
    <div class="campo">
       <div class="label-and-clear">
 <label for="gastrointestinal">5. GASTROINTESTINAL</label>
      <button class="clear-btn" onclick="limpiarCampo('gastrointestinal')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
  </div>
    <textarea id="gastrointestinal" disabled></textarea>
    </div>
<!-- Ejemplo para el campo  NUTRICIONAL/METABÓLICO               -->
    <div class="campo">
       <div class="label-and-clear">
 <label for="nutricional">6. NUTRICIONAL/METABÓLICO</label>
      <button class="clear-btn" onclick="limpiarCampo('nutricional')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
  </div>
    <textarea id="nutricional" disabled></textarea>
    </div>
<!-- Ejemplo para el campo    TERMORREGULACIÓN             -->
    <div class="campo">
        <div class="label-and-clear">
<label for="termorregulacion">7. TERMORREGULACIÓN</label>
      <button class="clear-btn" onclick="limpiarCampo('termorregulacion')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
  </div>
    <textarea id="termorregulacion" disabled></textarea>
    </div>
<!-- Ejemplo para el campo   PIEL              -->
    <div class="campo">
       <div class="label-and-clear">
 <label for="piel">8. PIEL</label>
      <button class="clear-btn" onclick="limpiarCampo('piel')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
        </div>
    <textarea id="piel" disabled></textarea>
    </div>
<!-- Ejemplo para el campo     OTROS            -->
     <!-- Campo 9 -->
  <div class="campo">
  <div class="label-and-clear">
    <label for="otros" class="centered-label">9. OTROS</label>
    <button class="clear-btn" onclick="limpiarCampo('otros')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
    <i class="fas fa-info-circle" 
       title="Si está vacío, no se imprimirá en el informe" 
       style="color: #666; font-size: 12px; margin-left: 5px; cursor: help;">
    </i>
  </div>
  <textarea id="otros" disabled placeholder="Dejar vacío para no imprimir"></textarea>
</div>

 <!-- Ejemplo para el campo    ESPECIAL VIGILANCIA             -->
 <!-- Campo 10 -->
  <div class="campo">
  <div class="label-and-clear">
    <label for="especial" class="centered-label">10. ESPECIAL VIGILANCIA</label>
    <button class="clear-btn" onclick="limpiarCampo('especial')" title="Borrar contenido">
      <i class="fas fa-eraser"></i>
    </button>
    <i class="fas fa-info-circle" 
       title="Si está vacío, no se imprimirá en el informe" 
       style="color: #666; font-size: 12px; margin-left: 5px; cursor: help;">
    </i>
  </div>
  <textarea id="especial" disabled placeholder="Dejar vacío para no imprimir"></textarea>
</div>

<!-- Ejemplo para el campo                 -->
  <!-- Campo Firma -->
  <!-- Campo Firma -->
<div class="campo">
  <label for="firma" class="centered-label">FIRMA</label>
  <textarea id="firma" disabled></textarea>
</div>



    <div class="contador-global">
      <strong>Total de caracteres utilizados:</strong>
      <span id="contador-total">0</span> /
      <span id="total-maximo">1200</span>
    </div>
    <div id="aviso-1200" style="display: none;">
  ¡Atención! Has superado el límite recomendado de 1200 caracteres.
</div>
    
  </form>



  <div class="main-actions-container">
    <button onclick="generarInforme()">
      <i class="fas fa-file-export"></i> Generar Informe
    </button>
    
    
    <button onclick="borrarDatos()">
      <i class="fa-solid fa-arrows-turn-right fa-flip-horizontal"></i>
      Borrar Datos
    </button>
    
    
    <button id="copiarInformeBtn" onclick="copiarInforme()" class="copiar-btn" style="display: none">
      <i class="fas fa-copy"></i> Copiar Informe
    </button>
  </div>

  <div class="print-actions-container">
    <button onclick="imprimirAuto()">
      <i class="fas fa-print"></i> Imprimir
    </button>
    <button class="btn-alternativo" onclick="imprimirAlternativo()">
      <i class="fas fa-print"></i>
      <i class="fas fa-exchange-alt"></i> Imprimir en Turno Alternativo
    </button>
  </div>

  <div class="fecha-container">
    <div class="select-row">
      <select id="informesGuardados" onchange="cargarInformeDesdeLista(this)">
        <option value="">-- Seleccionar Informe Guardado --</option>
      </select>


    </div>
    <div class="eliminar-buttons-row">
      <button class="eliminarInforme" onclick="eliminarInforme()">
        <i class="fas fa-trash-can"></i> Eliminar Informe
      </button>
      <button class="eliminarInforme" onclick="eliminarInformesDeBox()">
        <i class="fas fa-broom"></i> Eliminar Informes del Box
      </button>
    </div>
  </div>

  <div class="resultado" id="resultado"></div>

  <!-- área oculta que usaremos solo para imprimir -->
  <div id="printArea" style="display:none"></div>


  <div id="mensaje-turno"></div>
  <!-- Botón de cerrar sesión -->
  <button id="logoutBtn" onclick="cerrarSesion()">
    <i class="fa-solid fa-lock"></i>
    <span>CERRAR SESIÓN</span>
  </button>
  <footer>
    <p>
      &copy; 2025 Informe Evolución Enfermería. Todos los derechos
      reservados.
      <br>C.G.Francisco Manuel--M.G.José Antonio--M.M. Francisco Javier<br>
    </p>

  </footer>
  </div>
  <!-- ==================   JS principal   ================== -->

  <script>
// Variables globales
let selectedBox = null;
let currentUserId = null;
let currentInformeId = null;
let currentInformeBox = null;
let debounceTimer;
let firmaCompartida = "";

const DEBOUNCE_MS = 800;
const todosLosCampos = [
  "ingreso", "neurologico", "cardiovascular", "respiratorio", "renal",
  "gastrointestinal", "nutricional", "termorregulacion",
  "piel", "otros", "especial", "firma"
];

const camposCondicionales = ['ingreso', 'otros', 'especial'];
const camposBorrables = todosLosCampos.filter(c => c !== 'firma');

// Polyfill para compatibilidad
if(typeof campos === 'undefined') {
    window.campos = todosLosCampos;
}

// Funciones principales
async function cargarFirmaDesdeServidor() {
    if (!currentUserId) return;
    
    try {
        const response = await fetch('get_user_signature.php', {
            credentials: 'same-origin'
        });
        const data = await response.json();
        
        if (data.success && data.firma) {
            firmaCompartida = data.firma;
            localStorage.setItem(`firma_${currentUserId}`, firmaCompartida);
            
            const campoFirma = document.getElementById('firma');
            if (campoFirma) {
                campoFirma.value = firmaCompartida;
            }
        }
    } catch (error) {
        console.error("Error al cargar firma del servidor:", error);
    }
}

function setupAutosaveListeners() {
    todosLosCampos.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    if (selectedBox) saveDraft();
                }, DEBOUNCE_MS);
            });
        }
    });
}

function limpiarCampo(id) {
    if (id === 'firma') return;

    event.preventDefault();
    event.stopPropagation();
    
    const campo = document.getElementById(id);
    if (!campo) return;
    
    const estadoOriginal = {
        disabled: campo.disabled,
        value: campo.value,
        backgroundColor: campo.style.backgroundColor,
        color: campo.style.color
    };
    
    campo.value = "";
    actualizarContadorTotal();
    
    campo.disabled = estadoOriginal.disabled;
    if (!campo.disabled) {
        campo.style.backgroundColor = estadoOriginal.backgroundColor || 'var(--text-area)';
        campo.style.color = estadoOriginal.color || 'var(--fuente)';
    }
    
    guardarDraftSinDeshabilitar();
}

async function guardarDraftSinDeshabilitar() {
    if (!selectedBox || !currentUserId) return;
    
    try {
        const datos = {};
        todosLosCampos.forEach(id => {
            const el = document.getElementById(id);
            if (id === 'firma') {
                datos[id] = firmaCompartida || '';
            } else {
                datos[id] = el ? el.value.trim() : '';
            }
        });
        
        const payload = {
            box: selectedBox,
            datos: datos,
            user_id: currentUserId
        };
        
        await fetch('save_draft.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
    } catch (error) {
        console.error("Error al guardar draft:", error);
    }
}

function limpiarCampos(soloDeshabilitar = false) {
    if (!soloDeshabilitar) {
        camposBorrables.forEach(id => {
            const campo = document.getElementById(id);
            if (campo) campo.value = "";
        });
    }
    deshabilitarCampos();
}

async function eliminarDraft(boxNumber) {
    try {
        const response = await fetch('clear_draft.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ box: boxNumber })
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || "Error al eliminar");
    } catch (error) {
        console.error("Error al eliminar draft:", error.message);
    }
}

function deshabilitarCampos() {
    todosLosCampos.forEach(campoId => {
        const textarea = document.getElementById(campoId);
        if (textarea) {
            textarea.disabled = true;
            textarea.placeholder = "Seleccione un Box o informe guardado";
        }
    });
}

async function limpiarDatosIniciales() {
    if (!currentUserId) return;

    for (let i = 1; i <= 12; i++) {
        sessionStorage.removeItem(`autosave_${currentUserId}_box${i}`);
        await eliminarDraft(i);
    }
}

// DOMContentLoaded
document.addEventListener("DOMContentLoaded", async () => {
    const boxSelector = document.getElementById("boxSelector");
    if (!boxSelector) {
        console.error("Elemento #boxSelector no encontrado.");
        return;
    }
    
    setupAutosaveListeners();

    for (let i = 1; i <= 12; i++) {
        const btn = document.createElement("button");
        btn.textContent = `Box ${i}`;
        btn.onclick = async () => {
            console.log(`Clic en Box ${i}`);
            await selectBox(i);
        };
        boxSelector.appendChild(btn);
    }

    todosLosCampos.forEach(campoId => {
        const textarea = document.getElementById(campoId);
        if (textarea) {
            textarea.disabled = true;
            textarea.placeholder = "Seleccione un Box o informe guardado";
            textarea.addEventListener('input', actualizarContadorTotal);
        }
    });

    try {
        const r = await fetch("check_session.php", { credentials: 'same-origin' });
        if (!r.ok) throw new Error("Error en la respuesta del servidor");
        
        const text = await r.text();
        let d = {};
        
        try {
            d = text ? JSON.parse(text) : {};
        } catch (e) {
            console.error("Error parsing JSON:", e);
            throw new Error("Invalid JSON response");
        }

        if (!d.authenticated || !d.user_id) {
            throw new Error("Sesión inválida o user_id faltante");
        }

        currentUserId = d.user_id;
        await cargarFirmaDesdeServidor();

        const firmaGuardada = localStorage.getItem(`firma_${currentUserId}`);
        if (firmaGuardada) {
            firmaCompartida = firmaGuardada;
            const campoFirma = document.getElementById('firma');
            if (campoFirma) {
                campoFirma.value = firmaCompartida;
            }
        }
        
        sessionStorage.setItem('currentUserId', currentUserId);

        const lastBox = sessionStorage.getItem('lastSelectedBox');
        if (lastBox) {
            console.log(`Cargando último Box seleccionado: ${lastBox}`);
            await selectBox(parseInt(lastBox, 10));
        } else {
            deshabilitarCampos();
        }

        document.getElementById("contenidoApp").style.display = "block";
        cargarListadoInformesGuardados();
    } catch (error) {
        console.error("Error al verificar la sesión:", error);
        sessionStorage.removeItem("currentUserId");
        window.location.href = "index.html";
    }
    
    const campoFirma = document.getElementById('firma');
    if (campoFirma) {
        firmaCompartida = campoFirma.value;
        campoFirma.addEventListener('input', function() {
            firmaCompartida = this.value;
            actualizarContadorTotal();
        });
    }

    document.querySelectorAll('.clear-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopImmediatePropagation();
        });
    });
});

async function cerrarSesion() {
    if (selectedBox) await saveDraft();
    if (currentUserId) {
        Object.keys(sessionStorage)
            .filter(k => k.startsWith(`autosave_${currentUserId}_`))
            .forEach(k => sessionStorage.removeItem(k));
    }
    sessionStorage.removeItem("currentUserId");
    window.location.href = "index.html";
}

function saveLocal(boxNumber) {
    if (!boxNumber || !currentUserId) return;
    const datos = {};
    todosLosCampos.forEach(id => {
        const campo = document.getElementById(id);
        if (id === 'firma') {
            datos[id] = firmaCompartida || '';
        } else {
            datos[id] = campo ? campo.value.trim() : '';
        }
    });
    localStorage.setItem(`autosave_${currentUserId}_box${boxNumber}`, JSON.stringify(datos));
}

function actualizarContadorTotal() {
    let total = 0;
    todosLosCampos.forEach(id => {
        const el = document.getElementById(id);
        if (el && el.value) {
            total += el.value.trim().length;
        }
    });
    
    const contador = document.getElementById('contador-total');
    const aviso = document.getElementById('aviso-1200');
    
    if (contador) {
        contador.textContent = total;
        
        const LIMITE = 1200;
        if (total > LIMITE) {
            contador.classList.add('contador-alerta');
            if (aviso) {
                aviso.style.display = 'block';
                const exceso = total - LIMITE;
                aviso.textContent = `⚠️ Has superado el límite recomendado en ${exceso} caracteres (${total}/${LIMITE}).`;
            }
        } else {
            contador.classList.remove('contador-alerta');
            if (aviso) {
                aviso.style.display = 'none';
            }
        }
    }
}

function habilitarCampos() {
    console.log("Habilitando campos...");
    
    todosLosCampos.forEach(campoId => {
        const textarea = document.getElementById(campoId);
        if (textarea) {
            textarea.disabled = false;
            textarea.style.backgroundColor = 'var(--text-area)';
            textarea.style.color = 'var(--fuente)';
            textarea.style.cursor = 'text';
            textarea.removeAttribute('placeholder');
            
            if (campoId === 'firma') {
                textarea.removeEventListener('input', firmaInputHandler);
                textarea.addEventListener('input', firmaInputHandler);
            }
        }
    });
}

function firmaInputHandler() {
    const nuevaFirma = this.value;
    firmaCompartida = nuevaFirma;
    actualizarContadorTotal();
    
    if (currentUserId) {
        localStorage.setItem(`firma_${currentUserId}`, firmaCompartida);
        
        fetch('save_user_signature.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                user_id: currentUserId,
                firma: firmaCompartida 
            })
        }).catch(error => {
            console.error("Error al guardar firma en servidor:", error);
        });
    }
}

async function borrarDatos() {
    if (!confirm("¿Realmente quieres borrar todo el contenido (excepto la firma)?")) {
        return;
    }

    try {
        const firmaActual = document.getElementById('firma').value;
        
        camposBorrables.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                campo.value = "";
            }
        });

        const campoFirma = document.getElementById('firma');
        if (campoFirma) {
            campoFirma.value = firmaActual;
        }

        if (selectedBox) {
            await saveDraft();
        }
        
        actualizarContadorTotal();
        
        const resultado = document.getElementById('resultado');
        if (resultado) {
            resultado.style.display = 'none';
        }
        
        const botonCopiar = document.getElementById('copiarInformeBtn');
        if (botonCopiar) {
            botonCopiar.style.display = 'none';
        }
        
        console.log("Datos borrados correctamente, firma mantenida");
        
    } catch (error) {
        console.error("Error al borrar datos:", error);
        alert("Error al borrar los datos. Inténtalo de nuevo.");
    }
}

function copiarInforme() {
    const el = document.getElementById('resultado');
    if (!el || el.style.display === 'none') {
        alert('No hay informe generado para copiar.');
        return;
    }

    const html = el.innerHTML;
    const txt = el.innerText;

    function tryRichCopy() {
        try {
            const item = new ClipboardItem({
                'text/html': new Blob([html], { type: 'text/html' }),
                'text/plain': new Blob([txt], { type: 'text/plain' })
            });
            
            return navigator.clipboard.write([item])
                .then(() => {
                    alert('✅ Informe copiado con formato al portapapeles.');
                    return true;
                })
                .catch(() => false);
        } catch (e) {
            return Promise.resolve(false);
        }
    }

    function fallbackCopy() {
        try {
            const ta = document.createElement('textarea');
            ta.value = txt;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            ta.setSelectionRange(0, 99999);
            
            const success = document.execCommand('copy');
            document.body.removeChild(ta);
            
            if (success) {
                alert('📋 Informe copiado al portapapeles (texto plano).');
            } else {
                throw new Error('execCommand falló');
            }
        } catch (e) {
            alert('❌ No se pudo copiar el informe. Selecciona manualmente el texto.');
        }
    }

    if (navigator.clipboard && navigator.clipboard.write) {
        tryRichCopy().then(success => {
            if (!success) {
                fallbackCopy();
            }
        });
    } else {
        fallbackCopy();
    }
}


//==================================================================
//=====================================================================
// Reemplaza la función generarInforme() en tu app.php con esta versión corregida:

        async function generarInforme() {
    // Guardar draft antes de generar informe
    if (selectedBox) {
        await saveDraft();
    }

    // Verificar que hay box seleccionado y usuario autenticado
    if (!selectedBox || !currentUserId) {
        alert("Selecciona un Box y asegúrate de estar autenticado.");
        return;
    }

    try {
        // Recoger datos de todos los campos
        const datos = {};
        todosLosCampos.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campoId === 'firma') {
                datos[campoId] = firmaCompartida || '';
            } else {
                datos[campoId] = campo ? campo.value.trim() : '';
            }
        });
        
        // Generar y mostrar el HTML inmediatamente
        const html = generarHTMLDesdeDatos(datos);
        const divResultado = document.getElementById('resultado');
        divResultado.innerHTML = html;
        divResultado.style.display = 'block';
        
        // Mostrar botón de copiar
        const botonCopiar = document.getElementById('copiarInformeBtn');
        if (botonCopiar) {
            botonCopiar.style.display = 'inline-block';
        }

        // ⭐ LÓGICA CORREGIDA: NO crear nuevo informe si es un rescatado sin cambios
        let debeCrearNuevoInforme = true;
        let informeIdFinal = currentInformeId;
        
        if (currentInformeId) {
            console.log(`🔍 Verificando cambios en informe existente: ${currentInformeId}`);
            
            try {
                const response = await fetch(`get_report.php?id=${encodeURIComponent(currentInformeId)}`, {
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const informeOriginal = await response.json();
                    if (informeOriginal.success) {
                        // Verificar si ha sido modificado
                        const hayCambios = informeHaSidoModificado(informeOriginal.datos);
                        
                        if (!hayCambios) {
                            console.log("✅ No hay cambios - mantener informe original");
                            alert("Informe generado. No se han detectado cambios respecto al informe original.");
                            debeCrearNuevoInforme = false;
                        } else {
                            console.log("📝 Se detectaron cambios - crear nuevo informe");
                            informeIdFinal = crypto.randomUUID(); // Solo generar nuevo ID si hay cambios
                        }
                    }
                }
            } catch (error) {
                console.log("⚠️ No se pudo verificar informe original, creando nuevo...");
                informeIdFinal = crypto.randomUUID();
            }
        } else {
            // No hay informe previo, crear nuevo
            informeIdFinal = crypto.randomUUID();
            console.log("🆕 Creando nuevo informe desde cero");
        }
        
        // Solo guardar en servidor si necesitamos crear nuevo informe
        if (debeCrearNuevoInforme) {
            const payload = {
                id: informeIdFinal,
                user_id: currentUserId,
                box: selectedBox,
                datos: datos
            };

            console.log("💾 Enviando payload al servidor:", payload);

            const response = await fetch("save_report.php", {
                method: "POST",
                credentials: 'same-origin',
                headers: { 
                    "Content-Type": "application/json",
                    "Cache-Control": "no-cache"
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                currentInformeId = informeIdFinal;
                alert("Nuevo informe generado con éxito.");
                cargarListadoInformesGuardados();
            } else {
                console.error("Error del servidor:", result.message);
                alert("Error guardando el informe: " + (result.message || "Error desconocido"));
            }
        }
        
    } catch (error) {
        console.error("Error en generarInforme():", error);
        alert("No se pudo conectar con el servidor: " + error.message);
    }
    
    verificarEstadoBotones();
}


//============================================================

async function saveDraft() {
    if (!selectedBox || !currentUserId) {
        console.error("No se puede guardar: box o user_id no definidos");
        return false;
    }
    
    try {
        const datos = {};
        todosLosCampos.forEach(id => {
            const campo = document.getElementById(id);
            if (id === 'firma') {
                datos[id] = firmaCompartida || '';
            } else {
                datos[id] = campo ? campo.value.trim() : '';
            }
        });

        const payload = {
            box: selectedBox,
            datos: datos,
            user_id: currentUserId
        };

        const response = await fetch('save_draft.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const text = await response.text();
        if (!text) {
            throw new Error('Respuesta vacía del servidor');
        }

        const result = JSON.parse(text);
        
        if (!result.success) {
            throw new Error(result.message || "Error al guardar");
        }
        
        return true;
    } catch (error) {
        console.error("Error al guardar draft:", error);
        return false;
    }
}

async function selectBox(boxNumber) {
    try {
        if (!boxNumber || isNaN(boxNumber)) {
            throw new Error("Número de box inválido");
        }

        if (!currentUserId) {
            const errorMsg = "Usuario no autenticado. ID de usuario no disponible.";
            console.error(errorMsg);
            alert(errorMsg);
            return;
        }

        console.log(`Seleccionando Box ${boxNumber}`);

        if (selectedBox && selectedBox !== boxNumber) {
            console.log(`Guardando draft del Box ${selectedBox} antes de cambiar`);
            await saveDraft();
        }

        const indicador = document.getElementById("indicador-guardando");
        if (indicador) {
            indicador.style.display = "block";
            indicador.textContent = "Cargando...";
        }

        document.querySelectorAll('.box-selector button').forEach(btn => {
            btn.classList.remove('active');
        });

        const botonSeleccionado = document.querySelector(`.box-selector button:nth-child(${boxNumber})`);
        if (botonSeleccionado) {
            botonSeleccionado.classList.add('active');
        }

        selectedBox = boxNumber;
        sessionStorage.setItem('lastSelectedBox', boxNumber);

        mostrarIndicadorFlotante(boxNumber);
        const boxMsg = document.getElementById('mensaje-box-seleccionado');
        if (boxMsg) {
            document.getElementById('numero-box-seleccionado-msg').textContent = boxNumber;
            boxMsg.style.display = 'block';
        }

        habilitarCampos();

        console.log(`Cargando datos para Box ${boxNumber}`);
        const serverDraft = await cargarDraftServidor(boxNumber);
        
        if (serverDraft) {
            console.log("Datos cargados desde servidor:", serverDraft);
            actualizarCamposConDatos(serverDraft, true);
        } else {
            console.log("No hay datos guardados, limpiando campos...");
            camposBorrables.forEach(id => {
                const campo = document.getElementById(id);
                if (campo) campo.value = "";
            });
        }

        const campoFirma = document.getElementById('firma');
        if (campoFirma && firmaCompartida) {
            campoFirma.value = firmaCompartida;
        }

        if (indicador) {
            indicador.style.display = "none";
        }

        actualizarContadorTotal();

    } catch (error) {
        console.error("Error al cambiar de box:", error);
        alert(`Error: ${error.message}`);
        
        const indicador = document.getElementById("indicador-guardando");
        if (indicador) {
            indicador.style.display = "none";
        }
    }
}

async function cargarDraftServidor(boxNumber) {
    try {
        const res = await fetch(`get_draft.php?box=${boxNumber}`, {
            credentials: "same-origin"
        });
        
        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status}`);
        }
        
        const text = await res.text();
        if (!text) {
            throw new Error("Respuesta vacía del servidor");
        }
        
        const js = JSON.parse(text);
        
        if (!js.success) {
            console.warn("Servidor reportó error:", js.message);
            return null;
        }
        
        return js.datos;
    } catch (e) {
        console.error("[ERROR] Al cargar draft del servidor:", e);
        return null;
    }
}

function hasDatosSinGuardar() {
    return todosLosCampos.some(id => {
        const el = document.getElementById(id);
        return el && el.value.trim() !== "";
    });
}

function getCurrentFormData() {
    const datos = {};
    todosLosCampos.forEach(id => {
        const el = document.getElementById(id);
        datos[id] = el ? el.value.trim() : '';
    });
    return datos;
}

function actualizarCamposConDatos(datos, mantenerFirma = false) {
    if (!datos || typeof datos !== 'object') {
        console.error("Datos inválidos recibidos:", datos);
        return;
    }

    todosLosCampos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            if (id === 'firma' && mantenerFirma) {
                campo.value = firmaCompartida || '';
            } else if (id !== 'firma') {
                const valor = datos[id] || "";
                campo.value = (id !== 'nutricional' && valor === "6") ? "" : valor;
            }
        }
    });
    actualizarContadorTotal();
}

function cargarListadoInformesGuardados() {
    fetch('list_reports.php', { credentials: 'same-origin' })
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.reports)) {
                const select = document.getElementById('informesGuardados');
                select.innerHTML = '<option value="">Seleccione un informe...</option>';
                
                data.reports.forEach(informe => {
                    const option = document.createElement('option');
                    option.value = informe.id;
                    
                    if (!informe.fecha || !informe.hora || informe.fecha.includes('Sin')) {
                        option.textContent = `Box ${informe.box} - Recién guardado`;
                    } else {
                        option.textContent = `Box ${informe.box} - ${informe.fecha} ${informe.hora}`;
                    }
                    
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error al cargar listado:', error));
}

// =========================================================
async function cargarInformeDesdeLista(sel) {
    if (selectedBox) {
        await saveDraft();
    }
    
    const id = sel.value;
    if (!id) return;
    
    try {
        const response = await fetch(`get_report.php?id=${encodeURIComponent(id)}`, {
            credentials: 'same-origin'
        });
        
        if (!response.ok) throw new Error('Error de red');
        
        const data = await response.json();
        
        if (!data.success) {
            console.error('Error del servidor:', data.message);
            alert(data.message || 'Error al cargar el informe');
            return;
        }
        
        habilitarCampos();
        
        // Cargar datos en los campos
        todosLosCampos.forEach(campoId => {
            const textarea = document.getElementById(campoId);
            if (textarea && data.datos[campoId]) {
                if (campoId === 'firma') {
                    firmaCompartida = data.datos[campoId];
                    textarea.value = firmaCompartida;
                    if (currentUserId) {
                        localStorage.setItem(`firma_${currentUserId}`, firmaCompartida);
                    }
                } else {
                    textarea.value = data.datos[campoId];
                }
            }
        });
        
        // MANTENER EL ID ORIGINAL DEL INFORME - NO GENERAR NUEVO
        selectedBox = data.box;
        currentInformeId = data.id; // Usar el ID original del informe rescatado
        currentInformeBox = data.box;
        
        // Actualizar interfaz visual
        document.querySelectorAll('.box-selector button').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`.box-selector button:nth-child(${data.box})`)?.classList.add('active');
        
        // Generar y mostrar el informe
        const html = generarHTMLDesdeDatos(data.datos);
        const resultadoDiv = document.getElementById('resultado');
        resultadoDiv.innerHTML = html;
        resultadoDiv.style.display = 'block';
        
        // Mostrar botones de acción
        document.getElementById('copiarInformeBtn').style.display = 'inline-block';
        
        mostrarIndicadorFlotante(data.box);
        document.getElementById('numero-box-seleccionado-msg').textContent = data.box;
        document.getElementById('mensaje-box-seleccionado').style.display = 'block';
        
        actualizarContadorTotal();
        verificarEstadoBotones();
        
        // Mensaje informativo
        console.log(`Informe ${data.id} rescatado del Box ${data.box}. Listo para copiar/imprimir.`);
        
    } catch (error) {
        console.error('Error al cargar informe:', error);
        alert('No se pudo cargar el informe. Por favor, inténtalo de nuevo.');
    }
}


//============================================================

// MEJORAR la función para detectar cambios (más precisa):
function informeHaSidoModificado(datosOriginales) {
    const datosActuales = {};
    todosLosCampos.forEach(id => {
        const campo = document.getElementById(id);
        if (id === 'firma') {
            datosActuales[id] = firmaCompartida || '';
        } else {
            datosActuales[id] = campo ? campo.value.trim() : '';
        }
    });
    
    // Comparar cada campo con normalización
    for (let campo of todosLosCampos) {
        const original = (datosOriginales[campo] || '').trim();
        const actual = (datosActuales[campo] || '').trim();
        
        if (original !== actual) {
            console.log(`🔍 Cambio detectado en campo '${campo}':`);
            console.log(`   Original: "${original}"`);
            console.log(`   Actual: "${actual}"`);
            return true;
        }
    }
    
    console.log("✅ No se detectaron cambios en ningún campo");
    return false;
}

// =====================================================
// FUNCIONES DE IMPRESIÓN SIMPLIFICADAS
// =====================================================

async function imprimirAuto() {
    if (selectedBox) await saveDraft();
    
    const resultado = document.getElementById('resultado');
    if (!resultado || resultado.style.display === 'none') {
        alert('No hay informe generado para imprimir.');
        return;
    }
    
    // Determinar turno actual
    const horaActual = new Date().getHours();
    const esTurnoDiurnoActual = horaActual >= 8 && horaActual < 20;
    
    // Limpiar clases previas y aplicar clase según turno actual
    resultado.classList.remove('diurno-print', 'nocturno-print');
    
    if (esTurnoDiurnoActual) {
        resultado.classList.add('diurno-print');
    } else {
        resultado.classList.add('nocturno-print');
    }
    
    // Pequeña pausa para asegurar que las clases se apliquen
    setTimeout(() => {
        window.print();
    }, 100);
}


// Función para imprimir en turno alternativo
async function imprimirAlternativo() {
    if (selectedBox) await saveDraft();
    
    const resultado = document.getElementById('resultado');
    if (!resultado || resultado.style.display === 'none') {
        alert('No hay informe generado para imprimir.');
        return;
    }
    
    // Determinar turno actual
    const horaActual = new Date().getHours();
    const esTurnoDiurnoActual = horaActual >= 8 && horaActual < 20;
    
    // Guardar contenido original
    const contenidoOriginal = resultado.innerHTML;
    const clasesOriginales = resultado.className;
    
    // Cambiar el texto del turno y aplicar clase correspondiente
    let nuevoContenido = contenidoOriginal;
    
    resultado.classList.remove('diurno-print', 'nocturno-print');
    
    if (esTurnoDiurnoActual) {
        // Estamos en turno diurno → imprimir como nocturno
        nuevoContenido = nuevoContenido.replace('Turno de 8 a 20 horas', 'Turno de 20 a 8 horas');
        resultado.classList.add('nocturno-print');
    } else {
        // Estamos en turno nocturno → imprimir como diurno  
        nuevoContenido = nuevoContenido.replace('Turno de 20 a 8 horas', 'Turno de 8 a 20 horas');
        resultado.classList.add('diurno-print');
    }
    
    // Aplicar el nuevo contenido
    resultado.innerHTML = nuevoContenido;
    
    // Pequeña pausa antes de imprimir
    setTimeout(() => {
        window.print();
        
        // Restaurar contenido y clases originales
        setTimeout(() => {
            resultado.innerHTML = contenidoOriginal;
            resultado.className = clasesOriginales;
        }, 1000);
    }, 100);
}

// Funciones auxiliares para compatibilidad
function imprimirTurnoAlternativo() {
    imprimirAlternativo();
}

function imprimir(alternativo = false) {
    if (alternativo) {
        imprimirAlternativo();
    } else {
        imprimirAuto();
    }
}



// =====================================================
// FUNCIÓN PARA GENERAR HTML (SIN CAMBIOS)
// =====================================================

function generarHTMLDesdeDatos(datos, forzarTurno = null) {
    // Si se especifica un turno forzado, usarlo; si no, usar la hora actual
    let turnoTexto;
    if (forzarTurno !== null) {
        turnoTexto = forzarTurno ? 'Turno de 8 a 20 horas' : 'Turno de 20 a 8 horas';
    } else {
        const h = new Date().getHours();
        turnoTexto = (h >= 8 && h < 20) ? 'Turno de 8 a 20 horas' : 'Turno de 20 a 8 horas';
    }
    
    let html = `<p class="cabecera">BOX ${selectedBox} – ${turnoTexto}</p>`;
    
    // Procesar todos los campos excepto la firma
    todosLosCampos.forEach(campoId => {
        if (campoId === 'firma') return; // Saltar la firma, se procesará al final
        
        const etiqueta = document.querySelector(`label[for='${campoId}']`).innerText;
        
        if (camposCondicionales.includes(campoId)) {
            // CAMPOS CONDICIONALES: Solo se imprimen si tienen contenido
            const valor = datos[campoId]?.trim();
            if (valor && valor !== '') {
                html += `<p><span class="label-strong">${etiqueta}:</span> ${valor}</p>`;
            }
            // Si está vacío, no se añade nada al HTML
        } else {
            // CAMPOS NORMALES: Siempre se imprimen
            const valor = datos[campoId]?.trim() || '<span class="no-especificado">Sin especificar</span>';
            html += `<p><span class="label-strong">${etiqueta}:</span> ${valor}</p>`;
        }
    });

    // Agregar la firma al final con alineación a la derecha
    const valorFirma = firmaCompartida?.trim() || datos.firma?.trim() || '<span class="no-especificado">Sin especificar</span>';
    html += `<p class="firma-alineada"><span class="label-strong">FIRMADO:</span> ${valorFirma}</p>`;

    return html;
}


// =====================================================
// FUNCIÓN DE DEPURACIÓN MEJORADA
// =====================================================

function debugCompleto() {
    console.log("=== DEBUG COMPLETO ===");
    
    // 1. Verificar informe actual
    const resultado = document.getElementById('resultado');
    console.log("1. Informe visible:", resultado && resultado.style.display !== 'none');
    console.log("   currentInformeId:", currentInformeId);
    console.log("   selectedBox:", selectedBox);
    
    // 2. Verificar turno
    const ahora = new Date();
    const hora = ahora.getHours();
    const esTurnoDiurno = hora >= 8 && hora < 20;
    console.log("2. Hora actual:", `${hora}:${ahora.getMinutes()}`);
    console.log("   Es turno diurno:", esTurnoDiurno);
    
    // 3. Verificar clases CSS
    if (resultado) {
        console.log("3. Clases del resultado:", resultado.className);
        console.log("   Tiene nocturno-print:", resultado.classList.contains('nocturno-print'));
    }
    
    // 4. Verificar datos actuales
    const datosActuales = {};
    todosLosCampos.forEach(id => {
        const campo = document.getElementById(id);
        datosActuales[id] = campo ? campo.value.trim() : '';
    });
    console.log("4. Datos actuales:", datosActuales);
    
    console.log("=== FIN DEBUG ===");
}


//==============================================

// FUNCIONES JAVASCRIPT PARA ELIMINAR INFORMES
// Reemplaza estas funciones en tu app.php

async function eliminarInforme() {
    console.log("eliminarInforme: Iniciando eliminación");
    console.log("currentInformeId:", currentInformeId);
    
    if (!currentInformeId) {
        alert("No hay informe seleccionado para eliminar");
        return;
    }
    
    if (!confirm("¿Estás seguro de que quieres eliminar este informe?")) {
        return;
    }

    // Guardar firma actual antes de eliminar
    const firmaActual = document.getElementById('firma').value;

    try {
        console.log("Enviando petición de eliminación para ID:", currentInformeId);
        
        const response = await fetch("delete_reports.php", {
            method: "POST",
            credentials: 'same-origin',
            headers: { 
                "Content-Type": "application/json",
                "Cache-Control": "no-cache"
            },
            body: JSON.stringify({ 
                id: currentInformeId 
            })
        });

        console.log("Respuesta recibida:", response.status, response.statusText);

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();
        console.log("Resultado de eliminación:", result);

        if (result.success) {
            alert("Informe eliminado correctamente");
            
            // Limpiar variables
            currentInformeId = null;
            currentInformeBox = null;
            
            // Ocultar el informe mostrado
            const resultadoDiv = document.getElementById('resultado');
            if (resultadoDiv) {
                resultadoDiv.style.display = 'none';
            }
            
            // Ocultar botón copiar
            const botonCopiar = document.getElementById('copiarInformeBtn');
            if (botonCopiar) {
                botonCopiar.style.display = 'none';
            }
            
            // Recargar lista de informes
            cargarListadoInformesGuardados();
            
            // Restaurar firma
            document.getElementById('firma').value = firmaActual;
            
        } else {
            throw new Error(result.message || "Error desconocido");
        }

    } catch (error) {
        console.error("Error al eliminar informe:", error);
        alert("Error al eliminar el informe: " + error.message);
        
        // Restaurar firma incluso en caso de error
        document.getElementById('firma').value = firmaActual;
    }
}


// =============================================



async function eliminarInformesDeBox() {
    console.log("eliminarInformesDeBox: Iniciando eliminación");
    console.log("selectedBox:", selectedBox);
    
    // Guardar draft antes de eliminar
    if (selectedBox) {
        await saveDraft();
    }
    
    if (!selectedBox) {
        alert("Selecciona primero un Box.");
        return;
    }
    
    if (!confirm(`¿Estás seguro de que quieres eliminar TODOS los informes del Box ${selectedBox}?`)) {
        return;
    }

    try {
        console.log("Enviando petición de eliminación para Box:", selectedBox);
        
        const response = await fetch("delete_box_reports.php", {
            method: "POST",
            credentials: "same-origin",
            headers: { 
                "Content-Type": "application/json",
                "Cache-Control": "no-cache"
            },
            body: JSON.stringify({ 
                box: selectedBox 
            })
        });

        console.log("Respuesta recibida:", response.status, response.statusText);

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();
        console.log("Resultado de eliminación del box:", result);

        if (result.success) {
            alert(result.message || `Informes del Box ${selectedBox} eliminados correctamente`);
            
            // Limpiar variables
            currentInformeId = null;
            currentInformeBox = null;
            
            // Borrar datos del formulario (pero mantener firma)
            borrarDatos();
            
            // Recargar lista de informes
            cargarListadoInformesGuardados();
            
        } else {
            throw new Error(result.message || "Error desconocido");
        }

    } catch (error) {
        console.error("Error al eliminar informes del box:", error);
        alert("Error al eliminar informes del box: " + error.message);
    }
}

// =============================================



function mostrarIndicadorFlotante(boxNumber) {
    const indicador = document.getElementById("box-indicador-flotante");
    if (!indicador || !boxNumber) return;

    const nombreUsuario = document.body.dataset.username || '';
    
    let nombreMostrar = nombreUsuario;
    if (window.innerWidth <= 768) {
        nombreMostrar = nombreUsuario.length > 8 ? 
            nombreUsuario.substring(0, 6) + '..' : 
            nombreUsuario;
    }

    indicador.innerHTML = `
        <span>BOX ${boxNumber}</span>
        <span id="usuario-indicador-flotante">${nombreMostrar}</span>
    `;
    
    indicador.style.display = "block";
}

function actualizarPosicionBoxIndicador() {
    const indicador = document.getElementById("box-indicador-flotante");
    if (!indicador) return;

    const isMobile = window.innerWidth <= 768;
    const offset = isMobile ? 5 : 20;
    
    indicador.style.top = isMobile ? 
        `${offset}px` : 
        `${(window.scrollY || document.documentElement.scrollTop) + offset}px`;
}

window.addEventListener('load', actualizarPosicionBoxIndicador);
window.addEventListener('scroll', actualizarPosicionBoxIndicador);
window.addEventListener('resize', function() {
    actualizarPosicionBoxIndicador();
    if (selectedBox) mostrarIndicadorFlotante(selectedBox);
});

let inactivityTime = function () {
    let time;
    window.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onkeypress = resetTimer;
    document.onscroll = resetTimer;
    document.onclick = resetTimer;

    function logout() {
        alert("Sesión cerrada por inactividad.");
        window.location.href = "logout.php";
    }

    function resetTimer() {
        clearTimeout(time);
        time = setTimeout(logout, 300000);
    }
};
inactivityTime();

function verificarEstadoBotones() {
    const resultado = document.getElementById('resultado');
    const botonCopiar = document.getElementById('copiarInformeBtn');
    
    if (resultado && resultado.style.display !== 'none' && resultado.innerHTML.trim()) {
        if (botonCopiar) {
            botonCopiar.style.display = 'inline-block';
        }
    } else {
        if (botonCopiar) {
            botonCopiar.style.display = 'none';
        }
    }
}

window.addEventListener('beforeunload', async (e) => {
    try {
        if (selectedBox && hasDatosSinGuardar()) {
            const guardado = await saveDraft();
            if (!guardado) {
                e.preventDefault();
                e.returnValue = 'Tienes cambios sin guardar. ¿Seguro que quieres salir?';
                return e.returnValue;
            }
        }
    } catch (error) {
        console.error("Error al guardar antes de salir:", error);
        e.preventDefault();
        e.returnValue = 'Error al guardar los cambios. ¿Seguro que quieres salir?';
        return e.returnValue;
    }
});

// =====================================================
// AUTO-GUARDADO COMPATIBLE - AGREGAR AL FINAL DEL SCRIPT
// NO modificar funciones existentes, solo agregar esto
// =====================================================

// Variables para el auto-guardado
let autoSaveEnabled = false;
let autoSaveTimer = null;
let forceTimer = null;
let isPageClosing = false;

// =====================================================
// FUNCIÓN DE AUTO-GUARDADO PRINCIPAL
// =====================================================
async function autoSaveDraftNew() {
    if (!selectedBox || !currentUserId || isPageClosing) {
        return false;
    }
    
    try {
        const datos = {};
        todosLosCampos.forEach(id => {
            const campo = document.getElementById(id);
            if (id === 'firma') {
                datos[id] = firmaCompartida || '';
            } else {
                datos[id] = campo ? campo.value.trim() : '';
            }
        });

        const payload = {
            box: selectedBox,
            datos: datos,
            user_id: currentUserId,
            timestamp: Date.now()
        };

        const response = await fetch('save_draft.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            keepalive: true
        });

        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                console.log(`💾 Auto-guardado: Box ${selectedBox} (${result.caracteres || 0} chars)`);
                
                // Backup en localStorage
                localStorage.setItem(`backup_${currentUserId}_box${selectedBox}`, JSON.stringify({
                    datos: datos,
                    timestamp: Date.now()
                }));
                
                return true;
            }
        }
        
        throw new Error('Error en respuesta del servidor');
        
    } catch (error) {
        console.error('❌ Error auto-guardado:', error);
        
        // Guardar en localStorage como emergencia
        try {
            const datos = {};
            todosLosCampos.forEach(id => {
                const campo = document.getElementById(id);
                datos[id] = campo ? campo.value.trim() : '';
            });
            
            localStorage.setItem(`backup_${currentUserId}_box${selectedBox}`, JSON.stringify({
                datos: datos,
                timestamp: Date.now(),
                backup: true
            }));
            
            console.log('💾 Backup de emergencia guardado');
        } catch (e) {
            console.error('❌❌ Falló backup:', e);
        }
        
        return false;
    }
}

// =====================================================
// FUNCIÓN PARA GUARDAR AL CERRAR NAVEGADOR
// =====================================================
function saveOnPageClose() {
    if (!selectedBox || !currentUserId) return;
    
    isPageClosing = true;
    
    const datos = {};
    todosLosCampos.forEach(id => {
        const campo = document.getElementById(id);
        if (id === 'firma') {
            datos[id] = firmaCompartida || '';
        } else {
            datos[id] = campo ? campo.value.trim() : '';
        }
    });

    const payload = {
        box: selectedBox,
        datos: datos,
        user_id: currentUserId,
        timestamp: Date.now()
    };

    // Método 1: sendBeacon (más confiable para cerrar)
    if (navigator.sendBeacon) {
        const formData = new FormData();
        formData.append('data', JSON.stringify(payload));
        const sent = navigator.sendBeacon('save_draft.php', formData);
        console.log('📡 sendBeacon enviado:', sent);
    }
    
    // Método 2: fetch con keepalive (backup)
    fetch('save_draft.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        keepalive: true
    }).catch(() => {
        console.log('💾 Fetch falló, guardando en localStorage');
        localStorage.setItem(`backup_${currentUserId}_box${selectedBox}`, JSON.stringify({
            datos: datos,
            timestamp: Date.now(),
            fromClose: true
        }));
    });
}

// =====================================================
// FUNCIÓN PARA ACTIVAR AUTO-GUARDADO EN CAMPOS
// =====================================================
function activateAutoSave() {
    if (autoSaveEnabled) return;
    
    console.log('🚀 Activando auto-guardado...');
    autoSaveEnabled = true;
    
    // Agregar listeners a todos los campos
    todosLosCampos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            // Al escribir - con delay
            campo.addEventListener('input', () => {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    autoSaveDraftNew();
                }, 1000);
            });
            
            // Al cambiar - inmediato
            campo.addEventListener('change', () => {
                clearTimeout(autoSaveTimer);
                autoSaveDraftNew();
            });
            
            // Al perder foco - inmediato
            campo.addEventListener('blur', () => {
                clearTimeout(autoSaveTimer);
                autoSaveDraftNew();
            });
        }
    });
    
    // Guardado forzado cada 15 segundos
    if (forceTimer) clearInterval(forceTimer);
    forceTimer = setInterval(() => {
        if (selectedBox && !isPageClosing) {
            console.log('⏰ Guardado periódico forzado');
            autoSaveDraftNew();
        }
    }, 15000);
}

// =====================================================
// FUNCIÓN PARA CARGAR DATOS CON BACKUP
// =====================================================
async function loadDraftWithBackup(boxNumber) {
    try {
        // Primero intentar cargar desde servidor
        const response = await fetch(`get_draft.php?box=${boxNumber}`, {
            credentials: "same-origin"
        });
        
        if (response.ok) {
            const result = await response.json();
            if (result.success && result.datos) {
                console.log('✅ Datos cargados desde servidor');
                return result.datos;
            }
        }
        
        // Si no hay datos en servidor, buscar en localStorage
        const backupKey = `backup_${currentUserId}_box${boxNumber}`;
        const backup = localStorage.getItem(backupKey);
        
        if (backup) {
            try {
                const parsedBackup = JSON.parse(backup);
                console.log('🔄 Datos recuperados desde backup localStorage');
                
                // Sincronizar con servidor
                setTimeout(() => autoSaveDraftNew(), 2000);
                
                return parsedBackup.datos;
            } catch (e) {
                console.error('Error al parsear backup:', e);
            }
        }
        
        console.log('ℹ️ No hay datos guardados para este box');
        return null;
        
    } catch (error) {
        console.error('❌ Error al cargar draft:', error);
        return null;
    }
}

// =====================================================
// EVENTOS DE CIERRE DE PÁGINA
// =====================================================
window.addEventListener('beforeunload', (e) => {
    console.log('🚨 beforeunload - guardando datos...');
    saveOnPageClose();
});

window.addEventListener('unload', () => {
    console.log('🚨 unload - guardando datos...');
    saveOnPageClose();
});

window.addEventListener('pagehide', () => {
    console.log('🚨 pagehide - guardando datos...');
    saveOnPageClose();
});

// Para móviles - cuando se oculta la página
document.addEventListener('visibilitychange', () => {
    if (document.hidden && selectedBox) {
        console.log('👁️ Página oculta - guardando...');
        autoSaveDraftNew();
    }
});

// =====================================================
// MODIFICAR LA FUNCIÓN selectBox EXISTENTE
// =====================================================
// Interceptar la función selectBox original para agregar funcionalidad
const originalSelectBox = window.selectBox;
window.selectBox = async function(boxNumber) {
    console.log(`📦 Interceptando selectBox(${boxNumber})`);
    
    // Llamar a la función original primero
    await originalSelectBox(boxNumber);
    
    // Luego activar auto-guardado y cargar datos con backup
    activateAutoSave();
    
    // Intentar cargar datos con sistema de backup
    const datosConBackup = await loadDraftWithBackup(boxNumber);
    if (datosConBackup) {
        console.log('📋 Aplicando datos recuperados...');
        todosLosCampos.forEach(id => {
            const campo = document.getElementById(id);
            if (campo && datosConBackup[id]) {
                if (id === 'firma') {
                    firmaCompartida = datosConBackup[id];
                    campo.value = firmaCompartida;
                } else {
                    campo.value = datosConBackup[id];
                }
            }
        });
        
        // Actualizar contador
        if (typeof actualizarContadorTotal === 'function') {
            actualizarContadorTotal();
        }
    }
};

// =====================================================
// FUNCIÓN DE DEBUG
// =====================================================
function debugAutoSave() {
    console.log('=== DEBUG AUTO-GUARDADO ===');
    console.log('selectedBox:', selectedBox);
    console.log('currentUserId:', currentUserId);
    console.log('autoSaveEnabled:', autoSaveEnabled);
    console.log('isPageClosing:', isPageClosing);
    
    if (selectedBox && currentUserId) {
        const backup = localStorage.getItem(`backup_${currentUserId}_box${selectedBox}`);
        if (backup) {
            const data = JSON.parse(backup);
            console.log('Backup local:', new Date(data.timestamp).toLocaleTimeString());
        } else {
            console.log('No hay backup local');
        }
    }
    console.log('========================');
}

// Exponer funciones para debugging
window.debugAutoSave = debugAutoSave;
window.autoSaveDraftNew = autoSaveDraftNew;

console.log('🚀 Sistema de auto-guardado compatible inicializado');
</script>
</body>
</html>