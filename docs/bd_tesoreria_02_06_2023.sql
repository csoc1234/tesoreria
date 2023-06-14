-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: mariadb
-- Generation Time: Jun 02, 2023 at 03:18 PM
-- Server version: 10.5.11-MariaDB-1:10.5.11+maria~focal
-- PHP Version: 7.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bd_tesoreria`
--

-- --------------------------------------------------------

--
-- Table structure for table `auditorias`
--

CREATE TABLE `auditorias` (
  `id` bigint(60) UNSIGNED NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `proceso` varchar(245) DEFAULT NULL COMMENT 'descripción del proceso realizado',
  `accion` varchar(145) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `tabla` varchar(45) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bancos`
--

CREATE TABLE `bancos` (
  `id` int(11) UNSIGNED NOT NULL,
  `descripcion` varchar(245) DEFAULT NULL,
  `nit` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `bancos`
--

INSERT INTO `bancos` (`id`, `descripcion`, `nit`, `created_at`, `updated_at`) VALUES
(1, 'Banco de Bogotá', NULL, NULL, '2021-06-03 02:15:15'),
(2, 'Banco Popular', NULL, NULL, NULL),
(3, 'Banco CorpBanca', NULL, NULL, NULL),
(4, 'Bancolombia', NULL, NULL, NULL),
(5, 'Citibank', NULL, NULL, NULL),
(6, 'Banco GNB Sudameris', NULL, NULL, NULL),
(7, 'BBVA Colombia', NULL, NULL, NULL),
(8, 'Banco de Occidente', NULL, NULL, NULL),
(9, 'Banco Caja Social', NULL, NULL, NULL),
(10, 'Davivienda', NULL, NULL, NULL),
(11, 'Scotiabank', NULL, NULL, NULL),
(12, 'Banagrario', NULL, NULL, NULL),
(13, 'AV Villas', NULL, NULL, NULL),
(14, 'Banco Credifinanciera', NULL, NULL, NULL),
(15, 'Bancamía S.A.', NULL, NULL, NULL),
(16, 'Banco W S.A.', NULL, NULL, NULL),
(17, 'Bancoomeva', NULL, NULL, NULL),
(18, 'Finandina', NULL, NULL, NULL),
(19, 'Banco Falabella S.A.', NULL, NULL, NULL),
(20, 'Banco Pichincha S.A.', NULL, NULL, NULL),
(21, 'Banco Santander', NULL, NULL, NULL),
(22, 'Banco Mundo Mujer', NULL, NULL, NULL),
(23, 'Banco Multibank', NULL, NULL, NULL),
(24, 'Banco Serfinanzas', NULL, NULL, NULL),
(25, 'Corficolombiana', NULL, NULL, NULL),
(26, 'Banca de Inversión Bancolombia', NULL, NULL, NULL),
(27, 'BNP Paribas', NULL, NULL, NULL),
(28, 'Giros y Finanzas', NULL, NULL, NULL),
(29, 'Tuya', NULL, NULL, NULL),
(30, 'Leasing Bancoldex', NULL, NULL, NULL),
(31, 'Financiera DANN Regional', NULL, NULL, NULL),
(32, 'Credifamilia', NULL, NULL, NULL),
(33, 'CREZCAMOS', NULL, NULL, NULL),
(34, 'FINANCIERA JURISCOOP C.F.', NULL, NULL, NULL),
(35, 'Bancoldex', NULL, NULL, NULL),
(36, 'Findeter', NULL, NULL, NULL),
(37, 'Financiera de Desarrollo Nacional S.A', NULL, NULL, NULL),
(38, 'Finagro', NULL, NULL, NULL),
(39, 'Icetex', NULL, NULL, NULL),
(40, 'Fogafin', NULL, NULL, NULL),
(41, 'Fondo Nacional del Ahorro', NULL, NULL, NULL),
(42, 'Fogacoop', NULL, NULL, NULL),
(43, 'Fondo Nacional de Garantías', NULL, NULL, NULL),
(44, 'Banco de la República', NULL, NULL, NULL),
(45, 'CREDIBANCO', NULL, NULL, NULL),
(46, 'ACH Colombia S.A.', NULL, NULL, NULL),
(47, 'MOVII S.A.', NULL, NULL, NULL),
(48, 'TECNIPAGOS', NULL, NULL, '2021-05-31 21:49:39'),
(49, 'Coink S.A.', NULL, NULL, '2021-06-03 02:15:35'),
(50, 'Grupo Aval Acciones Y Valores S.A.', NULL, NULL, NULL),
(51, 'Grupo De Inversiones Suramericana S.A.', NULL, NULL, NULL),
(52, 'Grupo Bolívar S.A.', NULL, NULL, NULL),
(53, 'BancoCoomeva', NULL, NULL, NULL),
(54, 'Otro', NULL, NULL, NULL),
(60, 'FINDETER 1', NULL, '2021-06-10 13:41:25', '2021-06-10 13:41:25');

-- --------------------------------------------------------

--
-- Table structure for table `creditos`
--

CREATE TABLE `creditos` (
  `id` int(11) UNSIGNED NOT NULL,
  `tipo_credito_id` int(10) UNSIGNED NOT NULL,
  `num_ordenanza` varchar(45) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `valor` decimal(40,3) NOT NULL,
  `valor_prestado` decimal(40,3) NOT NULL DEFAULT 0.000,
  `valor_pagado` decimal(40,3) NOT NULL DEFAULT 0.000,
  `fecha` date NOT NULL,
  `estado` varchar(10) NOT NULL DEFAULT 'Activo',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `creditos_bancos`
--

CREATE TABLE `creditos_bancos` (
  `id` int(10) UNSIGNED NOT NULL,
  `credito_id` int(11) UNSIGNED NOT NULL,
  `banco_id` int(11) UNSIGNED NOT NULL,
  `linea` varchar(45) DEFAULT NULL,
  `descripcion` varchar(250) DEFAULT NULL,
  `spread` varchar(45) DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `num_annos` int(5) NOT NULL,
  `periodo_gracia` int(11) NOT NULL,
  `valor` decimal(40,3) DEFAULT NULL,
  `valor_prestado` decimal(19,3) NOT NULL DEFAULT 0.000,
  `num_dias` int(11) NOT NULL,
  `tasa_ref` varchar(45) DEFAULT NULL COMMENT 'DTF - depósitos a término fijo (DTF)\r\nEl IBR es un precio de referencia de corto plazo',
  `tasa_ref_valor` decimal(40,3) NOT NULL,
  `tipo_credito_id` tinyint(4) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `creditos_desembolsos`
--

CREATE TABLE `creditos_desembolsos` (
  `id` int(11) UNSIGNED NOT NULL,
  `credito_banco_id` int(11) UNSIGNED NOT NULL,
  `banco_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `credito_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `descripcion` varchar(250) DEFAULT NULL,
  `valor` decimal(40,3) NOT NULL,
  `tipo_desembolso` tinyint(4) DEFAULT NULL,
  `cuotas_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `proyecciones_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `es_independiente` tinyint(1) NOT NULL DEFAULT 0,
  `condiciones_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `truncar_tasa_ea` tinyint(1) NOT NULL DEFAULT 0,
  `separar_interes_capital` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `generos`
--

CREATE TABLE `generos` (
  `id` int(10) UNSIGNED NOT NULL,
  `descripcion` varchar(245) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `generos`
--

INSERT INTO `generos` (`id`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'Femenino', '2021-03-03 00:09:43', '2021-03-03 00:09:43'),
(2, 'Masculino', '2021-03-03 00:09:43', '2021-03-03 00:09:43');

-- --------------------------------------------------------

--
-- Table structure for table `permisos`
--

CREATE TABLE `permisos` (
  `id` int(10) UNSIGNED NOT NULL,
  `descripcion` varchar(250) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `orden` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `permisos`
--

INSERT INTO `permisos` (`id`, `descripcion`, `slug`, `orden`, `created_at`, `updated_at`) VALUES
(1, 'Crear créditos simulados', 'PERMISO_CREAR_CREDITO_SIMULADO', 0, '2021-04-22', '2021-04-22'),
(2, 'Editar creditos simulados', 'PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO', 0, '2021-04-22', '2021-04-22'),
(3, 'Ver listado de créditos simulados', 'PERMISO_LISTAR_CREDITOS_SIMULADOS', 0, '2021-04-23', '2021-04-23'),
(4, 'Ver líneas credito SAP', 'PERMISO_VER_CREDITOS_SAP', 0, '2021-04-23', '2021-04-23'),
(5, 'Borrar creditos simulados', 'PERMISO_BORRAR_CREDITO_SIMULADO', 0, '2021-04-23', '2021-04-23'),
(6, 'Registrar usuarios', 'PERMISO_REGISTRAR_USUARIOS', 0, '2021-05-31', '2021-05-31'),
(7, 'Editar usuarios', 'PERMISO_EDITAR_USUARIOS', 0, '2021-05-31', '2021-05-31'),
(8, 'Activar/Desactivar usuarios', 'PERMISO_ACTIVAR_DESACTIVAR_USUARIOS', 0, '2021-05-31', '2021-05-31'),
(9, 'Parametrizar datos (registrar, editar, listar)', 'PERMISO_PARAMETRIZAR_DATOS', 0, '2021-06-07', '2021-06-07'),
(10, 'Crear creditos bancarios simulados', 'PERMISO_CREAR_CREDITO_BANCARIO_SIMULADO_SIMULADO', 0, '2021-06-07', '2021-06-07'),
(12, 'Ver listado de creditos  bancarios simulados', 'PERMISO_VER_LISTADO_CREDITOS_BANCARIOS', 0, '2021-06-07', '2021-06-07'),
(13, 'Crear desembolsos simulados', 'PERMISO_CREAR_DESEMBOLSO_SIMULADO', 0, '2021-06-07', '2021-06-07'),
(14, 'Editar desembolsos simulados', 'PERMISO_EDITAR_DESEMBOLSO_SIMULADO', 0, '2021-06-07', '2021-06-07'),
(15, 'Borrar desembolsos simulados', 'PERMISO_BORRAR_DESEMBOLSO_SIMULADO', 0, '2021-06-07', '2021-06-07'),
(16, 'Ver listado de desembolsos simulados', 'PERMISO_VER_LISTADO_DESEMBOLSOS_SIMULADOS', 0, '2021-06-07', '2021-06-07'),
(17, 'Borrar creditos bancarios simulados', 'PERMISO_BORRAR_CREDITO_BANCO', 0, '2021-06-07', '2021-06-07');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `descripcion` varchar(250) NOT NULL,
  `permisos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `visible` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `descripcion`, `permisos_json`, `visible`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', NULL, 0, '2021-04-22', '2021-04-26'),
(25, 'Visualizador', '[{\"id\":1,\"descripcion\":\"Crear cr\\u00e9ditos simulados\",\"slug\":\"PERMISO_CREAR_CREDITO_SIMULADO\",\"orden\":0,\"created_at\":\"2021-04-22T05:00:00.000000Z\",\"updated_at\":\"2021-04-22T05:00:00.000000Z\",\"activado\":false},{\"id\":2,\"descripcion\":\"Editar creditos simulados\",\"slug\":\"PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO\",\"orden\":0,\"created_at\":\"2021-04-22T05:00:00.000000Z\",\"updated_at\":\"2021-04-22T05:00:00.000000Z\",\"activado\":false},{\"id\":3,\"descripcion\":\"Ver listado de cr\\u00e9ditos simulados\",\"slug\":\"PERMISO_LISTAR_CREDITOS_SIMULADOS\",\"orden\":0,\"created_at\":\"2021-04-23T05:00:00.000000Z\",\"updated_at\":\"2021-04-23T05:00:00.000000Z\",\"activado\":true},{\"id\":4,\"descripcion\":\"Ver l\\u00edneas credito SAP\",\"slug\":\"PERMISO_VER_CREDITOS_SAP\",\"orden\":0,\"created_at\":\"2021-04-23T05:00:00.000000Z\",\"updated_at\":\"2021-04-23T05:00:00.000000Z\",\"activado\":true},{\"id\":5,\"descripcion\":\"Borrar creditos simulados\",\"slug\":\"PERMISO_BORRAR_CREDITO_SIMULADO\",\"orden\":0,\"created_at\":\"2021-04-23T05:00:00.000000Z\",\"updated_at\":\"2021-04-23T05:00:00.000000Z\",\"activado\":false},{\"id\":6,\"descripcion\":\"Registrar usuarios\",\"slug\":\"PERMISO_REGISTRAR_USUARIOS\",\"orden\":0,\"created_at\":\"2021-05-31T05:00:00.000000Z\",\"updated_at\":\"2021-05-31T05:00:00.000000Z\",\"activado\":false},{\"id\":7,\"descripcion\":\"Editar usuarios\",\"slug\":\"PERMISO_EDITAR_USUARIOS\",\"orden\":0,\"created_at\":\"2021-05-31T05:00:00.000000Z\",\"updated_at\":\"2021-05-31T05:00:00.000000Z\",\"activado\":false},{\"id\":8,\"descripcion\":\"Activar\\/Desactivar usuarios\",\"slug\":\"PERMISO_ACTIVAR_DESACTIVAR_USUARIOS\",\"orden\":0,\"created_at\":\"2021-05-31T05:00:00.000000Z\",\"updated_at\":\"2021-05-31T05:00:00.000000Z\",\"activado\":false},{\"id\":9,\"descripcion\":\"Parametrizar datos (registrar, editar, listar)\",\"slug\":\"PERMISO_PARAMETRIZAR_DATOS\",\"orden\":0,\"created_at\":\"2021-06-07T05:00:00.000000Z\",\"updated_at\":\"2021-06-07T05:00:00.000000Z\",\"activado\":false},{\"id\":10,\"descripcion\":\"Crear creditos bancarios simulados\",\"slug\":\"PERMISO_CREAR_CREDITO_BANCARIO_SIMULADO_SIMULADO\",\"orden\":0,\"created_at\":\"2021-06-07T05:00:00.000000Z\",\"updated_at\":\"2021-06-07T05:00:00.000000Z\",\"activado\":false},{\"id\":12,\"descripcion\":\"Ver listado de creditos  bancarios simulados\",\"slug\":\"PERMISO_VER_LISTADO_CREDITOS_BANCARIOS\",\"orden\":0,\"created_at\":\"2021-06-07T05:00:00.000000Z\",\"updated_at\":\"2021-06-07T05:00:00.000000Z\",\"activado\":true},{\"id\":13,\"descripcion\":\"Crear desembolsos simulados\",\"slug\":\"PERMISO_CREAR_DESEMBOLSO_SIMULADO\",\"orden\":0,\"created_at\":\"2021-06-07T05:00:00.000000Z\",\"updated_at\":\"2021-06-07T05:00:00.000000Z\",\"activado\":false},{\"id\":14,\"descripcion\":\"Editar desembolsos simulados\",\"slug\":\"PERMISO_EDITAR_DESEMBOLSO_SIMULADO\",\"orden\":0,\"created_at\":\"2021-06-07T05:00:00.000000Z\",\"updated_at\":\"2021-06-07T05:00:00.000000Z\",\"activado\":false},{\"id\":15,\"descripcion\":\"Borrar desembolsos simulados\",\"slug\":\"PERMISO_BORRAR_DESEMBOLSO_SIMULADO\",\"orden\":0,\"created_at\":\"2021-06-07T05:00:00.000000Z\",\"updated_at\":\"2021-06-07T05:00:00.000000Z\",\"activado\":false},{\"id\":16,\"descripcion\":\"Ver listado de desembolsos simulados\",\"slug\":\"PERMISO_VER_LISTADO_DESEMBOLSOS_SIMULADOS\",\"orden\":0,\"created_at\":\"2021-06-07T05:00:00.000000Z\",\"updated_at\":\"2021-06-07T05:00:00.000000Z\",\"activado\":true},{\"id\":17,\"descripcion\":\"Borrar creditos bancarios simulados\",\"slug\":\"PERMISO_BORRAR_CREDITO_BANCO\",\"orden\":0,\"created_at\":\"2021-06-07T05:00:00.000000Z\",\"updated_at\":\"2021-06-07T05:00:00.000000Z\",\"activado\":false}]', 1, '2023-05-26', '2023-05-26');

-- --------------------------------------------------------

--
-- Table structure for table `roles_permisos`
--

CREATE TABLE `roles_permisos` (
  `id` int(11) NOT NULL,
  `permiso_id` int(10) UNSIGNED NOT NULL,
  `rol_id` int(10) UNSIGNED NOT NULL,
  `created_at` date NOT NULL,
  `updated_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `roles_permisos`
--

INSERT INTO `roles_permisos` (`id`, `permiso_id`, `rol_id`, `created_at`, `updated_at`) VALUES
(26561016, 3, 25, '2023-05-26', '2023-05-26'),
(28258769, 4, 25, '2023-05-26', '2023-05-26'),
(30338170, 5, 1, '2021-04-26', '2021-04-26'),
(39964130, 12, 25, '2023-05-26', '2023-05-26'),
(46183520, 2, 1, '2021-04-26', '2021-04-26'),
(46946956, 4, 1, '2021-04-26', '2021-04-26'),
(60252655, 16, 25, '2023-05-26', '2023-05-26'),
(61448687, 1, 1, '2021-04-26', '2021-04-26'),
(83304776, 3, 1, '2021-04-26', '2021-04-26');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_credito`
--

CREATE TABLE `tipo_credito` (
  `id` int(10) UNSIGNED NOT NULL,
  `descripcion` varchar(145) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tipo_credito`
--

INSERT INTO `tipo_credito` (`id`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'Real', '2021-03-10 16:26:06', '2021-03-10 16:26:06'),
(2, 'Simulado', '2021-03-10 16:26:06', '2021-03-10 16:26:06');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_vinculacion`
--

CREATE TABLE `tipo_vinculacion` (
  `id` int(10) UNSIGNED NOT NULL,
  `descripcion` varchar(145) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tipo_vinculacion`
--

INSERT INTO `tipo_vinculacion` (`id`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'Contratista', '2021-03-03 02:03:11', '2021-03-03 02:03:11'),
(2, 'Provisional', '2021-03-03 02:03:11', '2021-06-03 03:45:07'),
(3, 'Nombrado', '2021-03-03 02:03:28', '2021-03-03 02:03:28');

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo_vinculacion_id` int(10) UNSIGNED NOT NULL,
  `genero_id` int(10) UNSIGNED NOT NULL,
  `rol_id` int(10) UNSIGNED NOT NULL,
  `identificacion` varchar(45) NOT NULL,
  `nombres` varchar(145) NOT NULL,
  `apellidos` varchar(145) NOT NULL,
  `telefono` varchar(45) DEFAULT NULL,
  `celular` varchar(45) NOT NULL,
  `email` varchar(45) NOT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_inicio_vinculacion` datetime NOT NULL,
  `fecha_fin_vinculacion` datetime DEFAULT NULL,
  `password` varchar(250) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `tipo_vinculacion_id`, `genero_id`, `rol_id`, `identificacion`, `nombres`, `apellidos`, `telefono`, `celular`, `email`, `direccion`, `fecha_inicio_vinculacion`, `fecha_fin_vinculacion`, `password`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, '1234567899', 'Admin', 'Admin', NULL, '3143142525', 'admin@gmail.com', 'admin@gmail.com', '2021-03-02 00:00:00', '2021-07-30 05:00:00', '$2y$10$PZgtAHNgiQFdcchn.65Wg.1feJJXhrZrMVT1vhbFLcSo7ECjUoZH2', 1, '2021-03-03 03:20:02', '2022-10-06 14:28:20'),
(10, 2, 2, 1, 'Tesoreria', 'Tesoreria', 'Tesoreria', '', '', 'tesoreria@valledelcauca.gov.co', '', '2021-04-27 00:00:00', '2021-07-14 00:00:00', '$2y$10$TKfZrHq0gbhP815AkCiJ8eIEqOcb7zCfAHfjLYZkfJ0uzV3O2shUy', 1, '2021-04-30 16:20:09', '2021-06-08 12:27:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auditorias`
--
ALTER TABLE `auditorias`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bancos`
--
ALTER TABLE `bancos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `descripcion` (`descripcion`);

--
-- Indexes for table `creditos`
--
ALTER TABLE `creditos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_creditos_tipo_credito1_idx` (`tipo_credito_id`);

--
-- Indexes for table `creditos_bancos`
--
ALTER TABLE `creditos_bancos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `credito_id` (`credito_id`),
  ADD KEY `banco_id` (`banco_id`);

--
-- Indexes for table `creditos_desembolsos`
--
ALTER TABLE `creditos_desembolsos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `credito_banco_id` (`credito_banco_id`);

--
-- Indexes for table `generos`
--
ALTER TABLE `generos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `descripcion` (`descripcion`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `descripcion` (`descripcion`);

--
-- Indexes for table `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `permisos_roles_ibfk_2` (`permiso_id`),
  ADD KEY `rol_id` (`rol_id`);

--
-- Indexes for table `tipo_credito`
--
ALTER TABLE `tipo_credito`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tipo_vinculacion`
--
ALTER TABLE `tipo_vinculacion`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuarios_tipo_vinculacion1_idx` (`tipo_vinculacion_id`),
  ADD KEY `fk_usuarios_generos1_idx` (`genero_id`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auditorias`
--
ALTER TABLE `auditorias`
  MODIFY `id` bigint(60) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bancos`
--
ALTER TABLE `bancos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `creditos`
--
ALTER TABLE `creditos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `creditos_bancos`
--
ALTER TABLE `creditos_bancos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `creditos_desembolsos`
--
ALTER TABLE `creditos_desembolsos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generos`
--
ALTER TABLE `generos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `tipo_credito`
--
ALTER TABLE `tipo_credito`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tipo_vinculacion`
--
ALTER TABLE `tipo_vinculacion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `creditos`
--
ALTER TABLE `creditos`
  ADD CONSTRAINT `creditos_ibfk_1` FOREIGN KEY (`tipo_credito_id`) REFERENCES `tipo_credito` (`id`);

--
-- Constraints for table `creditos_bancos`
--
ALTER TABLE `creditos_bancos`
  ADD CONSTRAINT `creditos_bancos_ibfk_1` FOREIGN KEY (`credito_id`) REFERENCES `creditos` (`id`),
  ADD CONSTRAINT `creditos_bancos_ibfk_2` FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`);

--
-- Constraints for table `creditos_desembolsos`
--
ALTER TABLE `creditos_desembolsos`
  ADD CONSTRAINT `creditos_desembolsos_ibfk_1` FOREIGN KEY (`credito_banco_id`) REFERENCES `creditos_bancos` (`id`);

--
-- Constraints for table `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD CONSTRAINT `roles_permisos_ibfk_2` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`),
  ADD CONSTRAINT `roles_permisos_ibfk_3` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_generos1` FOREIGN KEY (`genero_id`) REFERENCES `generos` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_usuarios_tipo_vinculacion1` FOREIGN KEY (`tipo_vinculacion_id`) REFERENCES `tipo_vinculacion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
