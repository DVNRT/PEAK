-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-10-2026 a las 15:46:29
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `peak`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` varchar(50) DEFAULT 'evento',
  `dia_semana` tinyint(4) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `usuario_id`, `titulo`, `fecha`, `hora_inicio`, `hora_fin`, `descripcion`, `tipo`, `dia_semana`, `fecha_creacion`) VALUES
(1, 5, 'ESTUDIO', '2026-10-03', '22:11:00', '22:15:00', 'wwww', 'evento', NULL, '2026-10-03 14:18:52'),
(2, 5, 'ESTUDIO', '2026-10-04', '22:00:00', '00:00:00', 'yea', 'evento', NULL, '2026-10-03 14:21:31'),
(3, 5, 'ESTUDIO', '2026-10-05', '22:22:00', '22:22:00', 'yea', 'evento', NULL, '2026-10-03 14:22:16'),
(4, 5, 'ESTUDIO', '2026-10-06', '22:22:00', '22:22:00', 'yea', 'evento', NULL, '2026-10-03 14:22:30'),
(5, 5, 'tarea', '2026-10-07', '00:00:00', '00:00:00', '', 'tarea', NULL, '2026-10-03 14:28:44'),
(6, 5, 'tarea', '2026-10-08', '00:00:00', '00:00:00', '', 'tarea', NULL, '2026-10-03 14:28:53'),
(7, 5, 'tarea', '2026-10-09', '00:00:00', '00:00:00', '', 'tarea', NULL, '2026-10-03 14:29:01'),
(8, 1, 'escuela', '2026-10-08', '00:00:00', '00:00:00', '', 'evento', NULL, '2026-10-07 12:24:20'),
(9, 1, 'escuela', '2026-10-09', '00:00:00', '00:00:00', '', 'evento', NULL, '2026-10-07 12:24:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `peak_daily_activity`
--

CREATE TABLE `peak_daily_activity` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `app_opens` int(11) NOT NULL DEFAULT 0,
  `app_minutes` int(11) NOT NULL DEFAULT 0,
  `exercise_done` tinyint(1) NOT NULL DEFAULT 0,
  `exercise_minutes` int(11) NOT NULL DEFAULT 0,
  `water_glasses` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `peak_daily_activity`
--

INSERT INTO `peak_daily_activity` (`id`, `usuario_id`, `fecha`, `app_opens`, `app_minutes`, `exercise_done`, `exercise_minutes`, `water_glasses`) VALUES
(1, 3, '2026-09-30', 9, 0, 1, 30, 2),
(24, 1, '2026-09-30', 32, 0, 1, 30, 4),
(39, 2, '2026-09-30', 16, 1, 1, 30, 2),
(125, 4, '2026-09-30', 5, 8, 0, 0, 0),
(149, 5, '2026-09-30', 1, 0, 0, 0, 0),
(151, 5, '2026-10-03', 20, 3, 1, 30, 9),
(208, 1, '2026-10-03', 20, 3, 1, 30, 8),
(264, 1, '2026-10-07', 3, 6, 0, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `peak_user_progress`
--

CREATE TABLE `peak_user_progress` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nivel` int(11) NOT NULL DEFAULT 1,
  `xp_componentes` tinyint(4) NOT NULL DEFAULT 0,
  `ejercicio_progreso` int(11) NOT NULL DEFAULT 0,
  `ejercicio_nivel` int(11) NOT NULL DEFAULT 1,
  `hidratacion_progreso` int(11) NOT NULL DEFAULT 0,
  `hidratacion_nivel` int(11) NOT NULL DEFAULT 1,
  `hidratacion_ultima_subida` date DEFAULT NULL,
  `estudio_progreso` int(11) NOT NULL DEFAULT 0,
  `estudio_nivel` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `peak_user_progress`
--

INSERT INTO `peak_user_progress` (`id`, `usuario_id`, `nivel`, `xp_componentes`, `ejercicio_progreso`, `ejercicio_nivel`, `hidratacion_progreso`, `hidratacion_nivel`, `hidratacion_ultima_subida`, `estudio_progreso`, `estudio_nivel`) VALUES
(1, 5, 1, 0, 0, 1, 0, 1, NULL, 0, 1),
(10, 1, 1, 1, 20, 1, 0, 2, '2026-10-03', 9, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `fecha_registro`, `password`) VALUES
(1, 'Keiry Garcia', 'keiidg1983@gmail.com', '2026-09-29 01:30:16', '$2y$10$2nskZK7bp4k3IfJ/Ry3Gu..RRe/xG29h.VvBBx/mJ/lXzaBhBPFxy'),
(2, 'Walkin Rodrigez', 'WalkinRodriguez@gmail.com', '2026-09-29 15:22:14', '$2y$10$crKyp7gFawy3U9YB1jG91e2mmqSq115tyeg3th3hpBM2HsCGHtsP6'),
(3, 'Lorena Scarlett', 'LorenaScarlett@gmail.com', '2026-09-30 00:02:29', '$2y$10$jX1KtNk5NdLFllDWzsQiVe/l6K8UHap7JgFAIjgtU241zi206P6nW'),
(4, 'Ismael', 'IsmaelSantiago@gmail.com', '2026-09-30 14:01:53', '$2y$10$i.gVtbRiuPXC7Pb69x3up.QrH0sgPuYVvLVvKSqL0q/5AesiinONG'),
(5, 'Gailin', 'gailinHernandez@gmail.com', '2026-09-30 15:39:09', '$2y$10$3xpL4UHuMg13lnk9m6CJUu3A10/tXfOH/CjOIjrI90UCBieWe9wma');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `peak_daily_activity`
--
ALTER TABLE `peak_daily_activity`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_fecha` (`usuario_id`,`fecha`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `peak_user_progress`
--
ALTER TABLE `peak_user_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_unico` (`usuario_id`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `peak_daily_activity`
--
ALTER TABLE `peak_daily_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;

--
-- AUTO_INCREMENT de la tabla `peak_user_progress`
--
ALTER TABLE `peak_user_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
