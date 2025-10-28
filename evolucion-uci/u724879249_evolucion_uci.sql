-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 20-06-2025 a las 12:30:43
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
-- Base de datos: `u724879249_evolucion_uci`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `datos_balance`
--

CREATE TABLE `datos_balance` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `box_number` varchar(50) DEFAULT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp(),
  `peso_box` float DEFAULT NULL,
  `horas_desde_ingreso_box` float DEFAULT NULL,
  `perdida_orina_box` float DEFAULT NULL,
  `perdida_vomitos_box` float DEFAULT NULL,
  `fiebre37_horas_box` float DEFAULT NULL,
  `fiebre37_calculo_box` float DEFAULT NULL,
  `fiebre38_horas_box` float DEFAULT NULL,
  `fiebre38_calculo_box` float DEFAULT NULL,
  `fiebre39_horas_box` float DEFAULT NULL,
  `fiebre39_calculo_box` float DEFAULT NULL,
  `rpm25_horas_box` float DEFAULT NULL,
  `rpm25_calculo_box` float DEFAULT NULL,
  `rpm35_horas_box` float DEFAULT NULL,
  `rpm35_calculo_box` float DEFAULT NULL,
  `perdida_sng_box` float DEFAULT NULL,
  `perdida_hdfvvc_box` float DEFAULT NULL,
  `perdida_drenajes_box` float DEFAULT NULL,
  `perdidas_insensibles_box` float DEFAULT NULL,
  `perdida_fuerafluidos_box` float DEFAULT NULL,
  `total_perdidas_box` float DEFAULT NULL,
  `ingreso_midazolam_box` float DEFAULT NULL,
  `ingreso_fentanest_box` float DEFAULT NULL,
  `ingreso_propofol_box` float DEFAULT NULL,
  `ingreso_remifentanilo_box` float DEFAULT NULL,
  `ingreso_dexdor_box` float DEFAULT NULL,
  `ingreso_noradrenalina_box` float DEFAULT NULL,
  `ingreso_insulina_box` float DEFAULT NULL,
  `ingreso_sueroterapia1_box` float DEFAULT NULL,
  `ingreso_sueroterapia2_box` float DEFAULT NULL,
  `ingreso_sueroterapia3_box` float DEFAULT NULL,
  `ingreso_medicacion_box` float DEFAULT NULL,
  `ingreso_sangreplasma_box` float DEFAULT NULL,
  `ingreso_agua_endogena_box` float DEFAULT NULL,
  `ingreso_oral_box` float DEFAULT NULL,
  `ingreso_enteral_box` float DEFAULT NULL,
  `ingreso_parenteral_box` float DEFAULT NULL,
  `resumen_total_ingresos_box` float DEFAULT NULL,
  `balance_total_ingresos_box` float DEFAULT NULL,
  `balance_total_perdidas_box` float DEFAULT NULL,
  `balance_total_box` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `drafts`
--

CREATE TABLE `drafts` (
  `user_id` int(11) NOT NULL,
  `box` int(11) NOT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`datos`)),
  `timestamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informes`
--

CREATE TABLE `informes` (
  `id` int(11) NOT NULL,
  `box` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` varchar(100) NOT NULL,
  `neurologico` text DEFAULT NULL,
  `cardiovascular` text DEFAULT NULL,
  `respiratorio` text DEFAULT NULL,
  `renal` text DEFAULT NULL,
  `gastrointestinal` text DEFAULT NULL,
  `nutricional` text DEFAULT NULL,
  `termorregulacion` text DEFAULT NULL,
  `piel` text DEFAULT NULL,
  `otros` text DEFAULT NULL,
  `especial` text DEFAULT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `last_patient`
--

CREATE TABLE `last_patient` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `datos` text NOT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reports`
--

CREATE TABLE `reports` (
  `id` varchar(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `box` int(11) NOT NULL,
  `datos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`datos`)),
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_password`
--

CREATE TABLE `user_password` (
  `id` int(11) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `firma` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `datos_balance`
--
ALTER TABLE `datos_balance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_user_box` (`usuario_id`,`box_number`),
  ADD UNIQUE KEY `uq_usuario_box` (`usuario_id`,`box_number`);

--
-- Indices de la tabla `drafts`
--
ALTER TABLE `drafts`
  ADD PRIMARY KEY (`user_id`,`box`),
  ADD UNIQUE KEY `unique_user_box` (`user_id`,`box`);

--
-- Indices de la tabla `informes`
--
ALTER TABLE `informes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `last_patient`
--
ALTER TABLE `last_patient`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `box` (`box`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indices de la tabla `user_password`
--
ALTER TABLE `user_password`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`) USING BTREE;

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `datos_balance`
--
ALTER TABLE `datos_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `informes`
--
ALTER TABLE `informes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `last_patient`
--
ALTER TABLE `last_patient`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `user_password`
--
ALTER TABLE `user_password`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `datos_balance`
--
ALTER TABLE `datos_balance`
  ADD CONSTRAINT `datos_balance_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
