-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 05-07-2025 a las 23:33:23
-- Versión del servidor: 10.11.10-MariaDB
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u724879249_data`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `constantes_vitales`
--

CREATE TABLE `constantes_vitales` (
  `id` int(11) NOT NULL,
  `box` int(11) NOT NULL,
  `numero_box` int(11) NOT NULL,
  `hora` varchar(5) NOT NULL,
  `fecha_hoja` date NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `fr` int(11) DEFAULT NULL,
  `temperatura` decimal(3,1) DEFAULT NULL,
  `fc` int(11) DEFAULT NULL,
  `ta_sistolica` int(11) DEFAULT NULL,
  `ta_diastolica` int(11) DEFAULT NULL,
  `sat_o2` int(11) DEFAULT NULL,
  `glucemia` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_oxigenacion`
--

CREATE TABLE `datos_oxigenacion` (
  `id` int(11) NOT NULL,
  `box` int(11) NOT NULL DEFAULT 1,
  `numero_box` varchar(10) NOT NULL,
  `hora` time NOT NULL,
  `fecha` date NOT NULL,
  `p_neumo` int(11) DEFAULT NULL,
  `oxigenacion` varchar(20) DEFAULT NULL,
  `sat_o2` tinyint(3) DEFAULT NULL,
  `eva_escid` varchar(50) DEFAULT NULL,
  `glucemia` int(11) DEFAULT NULL,
  `insulina_iv` decimal(4,1) DEFAULT NULL,
  `insulina` decimal(5,1) DEFAULT NULL,
  `tipo_insulina` varchar(20) DEFAULT NULL,
  `observacion_insulina` varchar(100) DEFAULT NULL,
  `modo_insulina` enum('subcutanea','iv','mixta') DEFAULT 'subcutanea',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oxigenacion`
--

CREATE TABLE `oxigenacion` (
  `id` int(11) NOT NULL,
  `box` int(11) NOT NULL,
  `hora` time NOT NULL,
  `p_neumo` int(11) DEFAULT NULL COMMENT 'Presión neumática 0-100',
  `oxigenacion` varchar(20) DEFAULT NULL COMMENT 'VMI, VMNI, O2, Sin especificar',
  `sat_o2` tinyint(3) DEFAULT NULL,
  `eva_escid` varchar(100) DEFAULT NULL COMMENT 'EVA/ESCID/RASS: 5, +, -2, 7/+, 8/+/-2, etc',
  `insulina` varchar(50) DEFAULT NULL COMMENT 'Insulina: 6, 10, 15+5, etc',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `fecha_modificacion` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `oxigenacion_datos`
--

CREATE TABLE `oxigenacion_datos` (
  `id` int(11) NOT NULL,
  `numero_box` int(11) NOT NULL COMMENT 'Número del box (1-12)',
  `box` varchar(10) NOT NULL,
  `hora` varchar(5) NOT NULL,
  `p_neumo` int(11) DEFAULT NULL,
  `tipo_oxigenacion` varchar(10) DEFAULT NULL,
  `eva_escid` int(11) DEFAULT NULL,
  `sat_o2` tinyint(3) DEFAULT NULL COMMENT 'Saturación de oxígeno 0-100%',
  `glucemia` int(11) DEFAULT NULL COMMENT 'Glucemia en mg/dL',
  `rass` int(11) DEFAULT NULL,
  `insulina` decimal(4,1) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `oxigenacion` varchar(20) DEFAULT NULL COMMENT 'VMI, VMNI, O2, Sin especificar',
  `created_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Fecha de modificación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `numero_box` varchar(10) NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `edad` int(11) DEFAULT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `numero_historia` varchar(100) DEFAULT NULL,
  `numero_hoja` varchar(50) DEFAULT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `fecha_alta` datetime DEFAULT NULL,
  `estado` enum('activo','alta') DEFAULT 'activo',
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_boxes`
--

CREATE TABLE `pacientes_boxes` (
  `id` int(11) NOT NULL,
  `numero_box` int(11) NOT NULL,
  `nombre_paciente` varchar(255) DEFAULT NULL,
  `fecha_ingreso` datetime DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes_historial`
--

CREATE TABLE `pacientes_historial` (
  `id` int(11) NOT NULL,
  `box` int(11) NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `edad` int(11) NOT NULL,
  `peso` decimal(5,2) DEFAULT NULL,
  `numero_historia` varchar(50) NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `fecha_alta` datetime NOT NULL,
  `motivo_alta` varchar(255) DEFAULT 'Alta médica',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `constantes_vitales`
--
ALTER TABLE `constantes_vitales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_box_hora_fecha` (`numero_box`,`hora`,`fecha_hoja`),
  ADD KEY `idx_box_fecha` (`numero_box`,`fecha_hoja`),
  ADD KEY `box_fecha` (`box`,`fecha_registro`);

--
-- Indices de la tabla `datos_oxigenacion`
--
ALTER TABLE `datos_oxigenacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_box_fecha_hora` (`numero_box`,`fecha`,`hora`);

--
-- Indices de la tabla `oxigenacion`
--
ALTER TABLE `oxigenacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_box_hora` (`box`,`hora`),
  ADD KEY `idx_box` (`box`),
  ADD KEY `idx_hora` (`hora`);

--
-- Indices de la tabla `oxigenacion_datos`
--
ALTER TABLE `oxigenacion_datos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_box_hora` (`box`,`hora`),
  ADD KEY `idx_fecha` (`fecha_registro`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_box_estado` (`numero_box`,`estado`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `pacientes_boxes`
--
ALTER TABLE `pacientes_boxes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_box` (`numero_box`),
  ADD KEY `idx_numero_box` (`numero_box`),
  ADD KEY `idx_activo` (`activo`);

--
-- Indices de la tabla `pacientes_historial`
--
ALTER TABLE `pacientes_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `box` (`box`),
  ADD KEY `numero_historia` (`numero_historia`),
  ADD KEY `fecha_alta` (`fecha_alta`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `constantes_vitales`
--
ALTER TABLE `constantes_vitales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `datos_oxigenacion`
--
ALTER TABLE `datos_oxigenacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `oxigenacion`
--
ALTER TABLE `oxigenacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `oxigenacion_datos`
--
ALTER TABLE `oxigenacion_datos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_boxes`
--
ALTER TABLE `pacientes_boxes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pacientes_historial`
--
ALTER TABLE `pacientes_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
