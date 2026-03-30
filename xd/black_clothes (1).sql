-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-03-2026 a las 08:29:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `black_clothes`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cart`
--

INSERT INTO `cart` (`id`, `session_id`, `user_id`, `product_id`, `variant_id`, `quantity`, `created_at`) VALUES
(14, '4pum767bs99slij154tjlhv4rs', NULL, 34, 63, 1, '2026-03-19 04:36:33'),
(16, '8fl06p164vc1g0euk1iijcd7m6', NULL, 51, 122, 1, '2026-03-19 21:48:53'),
(17, '6rs7q1pf8jo2608d3n66gjkhql', NULL, 37, 77, 1, '2026-03-28 06:18:28'),
(18, 'e3upvq7ddj76nd4bqjrie0e989', NULL, 40, 94, 12, '2026-03-28 06:18:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `type` enum('wholesale','individual','both') DEFAULT 'both'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `type`) VALUES
(1, 'Playeras Básicas', 'playeras-basicas', 'both'),
(2, 'Playeras Premium', 'playeras-premium', 'individual'),
(3, 'Paquetes Mayoreo', 'paquetes-mayoreo', 'wholesale');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `order_type` enum('individual','wholesale') DEFAULT 'individual',
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `shipping_name` varchar(150) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `telegram_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(200) DEFAULT NULL,
  `size` varchar(10) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `material` enum('cotton','polyester','mixed') NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sale_type` enum('wholesale','individual','both') DEFAULT 'both',
  `price_individual` decimal(10,2) DEFAULT NULL,
  `price_wholesale` decimal(10,2) DEFAULT NULL,
  `min_wholesale_qty` int(11) DEFAULT 12,
  `stock` int(11) DEFAULT 0,
  `featured` tinyint(1) DEFAULT 0,
  `color` enum('blanco','negro','rojo','azul','verde') NOT NULL DEFAULT 'blanco',
  `color_price_modifier` decimal(10,2) NOT NULL DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `description`, `material`, `category_id`, `sale_type`, `price_individual`, `price_wholesale`, `min_wholesale_qty`, `stock`, `featured`, `color`, `color_price_modifier`, `active`, `created_at`) VALUES
(33, 'Playera Cuello Redondo Peso Completo', '-layera-uello-edondo-eso-ompleto', 'Playera Básica Cuello Redondo – Mayoreo\r\n\r\nPlayera básica de alta calidad ideal para negocios, uniformes, estampado o reventa. Fabricada con tela resistente y cómoda, perfecta para uso diario y producción de prendas personalizadas.\r\n\r\nSu composición de algodón ofrece suavidad, frescura y buena transpirabilidad, mientras que su peso de tela de 195 g/m² proporciona mayor durabilidad y mejor estructura que las playeras básicas convencionales.\r\n\r\nDisponible en diferentes colores y tallas.\r\n\r\nCaracterísticas:\r\n• Cuello redondo clásico\r\n• Tela suave y resistente\r\n• 100% algodón en la mayoría de colores\r\n• Gris jaspe: 90% algodón / 10% poliéster\r\n• Peso de tela: 195 g/m²\r\n• Ideal para estampado, uniformes o reventa', 'cotton', 2, 'both', 95.00, 85.00, 12, 1000, 1, 'blanco', 0.00, 1, '2026-03-17 06:05:29'),
(34, 'Playera Cuello V para Caballero', '-layera-uello-para-aballero', 'Playera de cuello en “V” ideal para negocios, uniformes o reventa. Su diseño moderno ofrece una apariencia más estilizada, perfecta para quienes buscan una opción diferente a la playera clásica.\r\n\r\nFabricada con tela 100% algodón, brinda suavidad, frescura y comodidad durante todo el día. Su peso de 155 g/m² la hace ligera y cómoda, ideal para climas cálidos o uso prolongado.\r\n\r\nUna excelente opción para personalización, estampado o venta al por mayor.\r\n\r\nCaracterísticas:\r\n• Cuello en “V” moderno\r\n• Tela ligera y cómoda\r\n• 100% algodón\r\n• Peso de tela: 155 g/m²\r\n• Ideal para estampado, uniformes o reventa\r\n• Corte para caballero', 'cotton', 1, 'both', 100.00, 90.00, 12, 1000, 0, 'negro', 0.00, 1, '2026-03-17 06:12:49'),
(36, 'Playera deportiva', '-layera-deportiva', 'Playera Deportiva para Caballero\r\n\r\nPlayera deportiva diseñada para brindar máximo rendimiento y comodidad durante la actividad física. Ideal para equipos deportivos, uniformes, eventos o reventa.\r\n\r\nFabricada con tela tipo mesh 100% poliéster, permite una excelente ventilación, secado rápido y ligereza, manteniéndote fresco incluso en entrenamientos intensos o climas calurosos.\r\n\r\nSu diseño de cuello redondo y corte cómodo la hace perfecta para uso deportivo o casual.\r\n\r\nCaracterísticas:\r\n• Cuello redondo\r\n• Tela tipo mesh (transpirable)\r\n• 100% poliéster\r\n• Peso de tela: 150 g/m² (ligera y fresca)\r\n• Secado rápido\r\n• Ideal para deporte, uniformes o eventos', 'polyester', 1, 'both', 125.00, 115.00, 12, 1000, 0, 'rojo', 0.00, 1, '2026-03-18 03:53:33'),
(37, 'Playera Peso Completo Cuello Redondo Sin Mangas', '-layera-eso-ompleto-uello-edondo-in-angas', 'Playera Sin Mangas para Caballero \r\n\r\nPlayera sin mangas ideal para climas cálidos, actividades deportivas o uso casual. Su diseño ligero y cómodo permite mayor libertad de movimiento, siendo perfecta para entrenamiento, playa o uso diario.\r\n\r\nFabricada con tela de algodón de alta calidad, ofrece suavidad, frescura y buena transpirabilidad. Su peso de 155 g/m² la hace ligera y cómoda para uso prolongado.\r\n\r\nUna excelente opción para reventa, uniformes o personalización.\r\n\r\nCaracterísticas:\r\n• Diseño sin mangas\r\n• Tela ligera y fresca\r\n• 100% algodón (gris jaspe: 90% algodón / 10% poliéster)\r\n• Peso de tela: 155 g/m²\r\n• Ideal para clima cálido, deporte o uso casual\r\n• Perfecta para estampado o reventa\r\n\r\nDisponible en diferentes tallas y colores.', 'cotton', 1, 'both', 90.00, 80.00, 12, 1000, 0, 'azul', 0.00, 1, '2026-03-18 04:00:54'),
(38, 'Playera Tipo Polo peso medio', '-layera-ipo-olo-peso-medio', 'Playera Tipo Polo para Caballero peso medio\r\n\r\nPlayera tipo polo ideal para negocios que buscan una imagen más profesional sin sacrificar comodidad. Su diseño con cuello y botones aporta un estilo formal y versátil, perfecto para uniformes, reventa o uso diario.\r\n\r\nFabricada con tela de algodón de alta calidad, ofrece una sensación suave, fresca y transpirable. Su peso de 195 g/m² proporciona mayor resistencia, mejor estructura y una excelente durabilidad en comparación con polos ligeras.\r\n\r\nIdeal para personalización, especialmente bordado.\r\n\r\nCaracterísticas:\r\n• Diseño tipo polo con cuello y botones\r\n• Tela suave, fresca y resistente\r\n• 100% algodón (gris jaspe: 90% algodón / 10% poliéster)\r\n• Peso de tela: 195 g/m² (alta durabilidad)\r\n• Manga corta\r\n• Ideal para uniformes, bordado o reventa\r\n\r\n\r\nDisponible en diferentes tallas y colores.', 'cotton', 1, 'both', 105.00, 100.00, 12, 1000, 0, 'verde', 0.00, 1, '2026-03-18 04:35:22'),
(40, 'Playera Tipo Polo Deportiva', '-layera-ipo-olo-eportiva', 'Playera Tipo Polo Deportiva para Caballero \r\n\r\nPlayera tipo polo deportiva diseñada para ofrecer comodidad, frescura y una apariencia profesional. Ideal para equipos, uniformes empresariales, eventos o reventa.\r\n\r\nFabricada con tela tipo mesh 100% poliéster, permite una excelente ventilación y secado rápido, manteniéndote fresco durante todo el día. Su diseño tipo polo con cuello y botones brinda un estilo más formal sin perder el enfoque deportivo.\r\n\r\nLigera y resistente, es perfecta para climas cálidos y actividades de alto movimiento.\r\n\r\nCaracterísticas:\r\n• Diseño tipo polo con cuello y botones\r\n• Tela tipo mesh (transpirable)\r\n• 100% poliéster\r\n• Peso de tela: 150 g/m² (ligera y fresca)\r\n• Secado rápido\r\n• Ideal para deporte, uniformes o eventos\r\n\r\n\r\nDisponible en diferentes tallas y colores.', 'polyester', 2, 'both', 200.00, 180.00, 12, 1000, 1, 'blanco', 0.00, 1, '2026-03-18 04:55:48'),
(41, 'Playera Cuello Redondo Peso Medio', '-layera-uello-edondo-eso-edio', 'Playera Cuello Redondo para Caballero – Línea Básica\r\n\r\nPlayera básica ideal para negocios que buscan una opción económica, cómoda y versátil. Perfecta para reventa, uniformes, eventos o personalización.\r\n\r\nFabricada con tela de algodón, ofrece suavidad, frescura y buena transpirabilidad. Su peso medio de 155 g/m² la hace ligera y cómoda, ideal para climas cálidos o uso diario.\r\n\r\nUna excelente alternativa para compras por volumen gracias a su gran relación calidad-precio.\r\n\r\nCaracterísticas:\r\n• Cuello redondo clásico\r\n• Tela ligera y cómoda\r\n• 100% algodón (gris jaspe: 90% algodón / 10% poliéster)\r\n• Peso de tela: 155 g/m²\r\n• Ideal para eventos, uniformes o reventa\r\n• Perfecta para estampado\r\n\r\nDisponible en diferentes tallas y colores.', 'cotton', 1, 'both', 75.00, 65.00, 12, 1000, 0, 'blanco', 0.00, 1, '2026-03-18 05:07:34'),
(46, 'Playera tipo polo peso completo', '-layera-tipo-polo-peso-completo', 'Playera tipo polo ideal para negocios, uniformes empresariales y reventa. Su diseño con cuello y botones brinda una apariencia más formal y profesional, perfecta para elevar la imagen de cualquier equipo de trabajo.\r\n\r\nFabricada en tela piqué de alta calidad, combina 50% algodón y 50% poliéster, logrando el equilibrio ideal entre comodidad, frescura y resistencia. Su peso de 225 g/m² proporciona una estructura más firme, mayor durabilidad y excelente presentación.\r\n\r\nIdeal para bordado o personalización.\r\n\r\nCaracterísticas:\r\n• Diseño tipo polo con cuello y botones\r\n• Tela piqué resistente y transpirable\r\n• 50% algodón / 50% poliéster\r\n• Peso de tela: 225 g/m² (alta durabilidad)\r\n• Manga corta\r\n• Ideal para uniformes, bordado o reventa', 'mixed', 2, 'both', 150.00, 140.00, 12, 1000, 1, 'blanco', 0.00, 1, '2026-03-18 05:21:19'),
(47, 'Playera Peso Completo Cuello Redondo para Dama', '-layera-eso-ompleto-uello-edondo-para-ama', 'Playera Cuello Redondo para Dama \r\n\r\nPlayera diseñada especialmente para dama, combinando comodidad, estilo y durabilidad. Ideal para negocios, uniformes, reventa o uso diario.\r\n\r\nFabricada con tela de algodón de alta calidad, ofrece una sensación suave, fresca y transpirable. Su peso completo de 195 g/m² proporciona mayor resistencia, mejor estructura y una excelente caída en el cuerpo.\r\n\r\nSu corte femenino permite un ajuste más estilizado sin perder comodidad, siendo una opción versátil para cualquier ocasión.\r\n\r\nCaracterísticas:\r\n• Cuello redondo\r\n• Corte para dama (ajuste más estilizado)\r\n• Tela suave, fresca y resistente\r\n• 100% algodón (gris jaspe: 90% algodón / 10% poliéster)\r\n• Peso de tela: 195 g/m² (alta durabilidad)\r\n• Ideal para uniformes, reventa o uso diario\r\n• Perfecta para estampado\r\n\r\nDisponible en diferentes tallas y colores.', 'cotton', 2, 'both', 95.00, 80.00, 12, 1000, 1, 'blanco', 0.00, 1, '2026-03-18 06:26:41'),
(48, 'Playera Cuello V de Dama', '-layera-uello-de-ama', 'Playera Cuello “V” para Dama \r\n\r\nPlayera diseñada para dama con cuello en “V”, ideal para quienes buscan una opción más estilizada y moderna. Perfecta para negocios, uniformes, reventa o uso diario.\r\n\r\nFabricada con tela de algodón de alta calidad, ofrece suavidad, frescura y excelente transpirabilidad. Su peso de 155 g/m² la hace ligera y cómoda, ideal para climas cálidos y uso prolongado.\r\n\r\nSu corte femenino proporciona un mejor ajuste al cuerpo, manteniendo comodidad y libertad de movimiento.\r\n\r\nCaracterísticas:\r\n• Cuello en “V” moderno\r\n• Corte para dama (ajuste estilizado)\r\n• Tela ligera y fresca\r\n• 100% algodón\r\n• Peso de tela: 155 g/m²\r\n• Ideal para uniformes, reventa o uso diario\r\n• Perfecta para estampado\r\n\r\nDisponible en diferentes tallas y colores.', 'cotton', 1, 'both', 105.00, 90.00, 12, 1000, 0, 'blanco', 0.00, 1, '2026-03-18 06:35:05'),
(49, 'Playera Deportiva de Dama', '-layera-eportiva-de-ama', 'Playera Deportiva para Dama \r\n\r\nPlayera deportiva diseñada especialmente para dama, ideal para brindar comodidad, frescura y libertad de movimiento durante cualquier actividad física. Perfecta para equipos, uniformes, eventos o reventa.\r\n\r\nFabricada con tela tipo mesh 100% poliéster, ofrece excelente ventilación, secado rápido y ligereza, manteniéndote fresca incluso en entrenamientos intensos o climas cálidos.\r\n\r\nSu diseño de cuello redondo y corte femenino proporciona un ajuste más cómodo y estilizado.\r\n\r\nCaracterísticas:\r\n• Cuello redondo\r\n• Corte para dama (ajuste cómodo y estilizado)\r\n• Tela tipo mesh (transpirable)\r\n• 100% poliéster\r\n• Peso de tela: 150 g/m² (ligera y fresca)\r\n• Secado rápido\r\n• Ideal para deporte, uniformes o eventos\r\n\r\nDisponible en diferentes tallas y colores.', 'cotton', 1, 'both', 120.00, 110.00, 12, 1000, 0, 'blanco', 0.00, 1, '2026-03-18 06:40:51'),
(50, 'Playera tipo polo peso completo de Dama', '-layera-tipo-polo-peso-completo-de-ama', 'Playera Tipo Polo para Dama \r\n\r\nPlayera tipo polo diseñada para dama, ideal para uniformes empresariales, negocios o reventa. Su diseño con cuello y botones brinda una apariencia profesional y estilizada, perfecta para cualquier entorno laboral.\r\n\r\nFabricada en tela piqué de alta calidad, con mezcla de 50% algodón y 50% poliéster, ofrece el equilibrio ideal entre comodidad, frescura y resistencia. Su peso de 225 g/m² proporciona mayor durabilidad y excelente presentación.\r\n\r\nEl corte femenino permite un ajuste más favorecedor sin sacrificar comodidad.\r\n\r\nCaracterísticas:\r\n• Diseño tipo polo con cuello y botones\r\n• Corte para dama (ajuste estilizado)\r\n• Tela piqué resistente y transpirable\r\n• 50% algodón / 50% poliéster\r\n• Peso de tela: 225 g/m² (alta durabilidad)\r\n• Manga corta\r\n• Ideal para uniformes, bordado o reventa\r\n\r\nDisponible en diferentes tallas y colores.', 'cotton', 2, 'both', 160.00, 125.00, 12, 1000, 1, 'blanco', 0.00, 1, '2026-03-18 06:46:55'),
(51, 'Playera Cuello Redondo Peso Mediano de Dama', '-layera-uello-edondo-eso-ediano-de-ama', 'Playera Cuello Redondo para Dama \r\n\r\nPlayera diseñada para dama, ideal para quienes buscan una opción cómoda, ligera y accesible. Perfecta para negocios, uniformes, eventos o reventa.\r\n\r\nFabricada con tela de algodón, brinda suavidad, frescura y buena transpirabilidad. Su peso medio de 155 g/m² la hace ligera y cómoda, ideal para climas cálidos y uso diario.\r\n\r\nSu corte femenino ofrece un ajuste más estilizado sin perder comodidad, adaptándose a diferentes tipos de uso.\r\n\r\nCaracterísticas:\r\n• Cuello redondo\r\n• Corte para dama (ajuste cómodo y estilizado)\r\n• Tela ligera y fresca\r\n• 100% algodón (gris jaspe: 90% algodón / 10% poliéster)\r\n• Peso de tela: 155 g/m²\r\n• Ideal para eventos, uniformes o reventa\r\n• Excelente para estampado\r\n\r\nDisponible en diferentes tallas y colores.', 'cotton', 1, 'both', 80.00, 65.00, 12, 1000, 0, 'blanco', 0.00, 1, '2026-03-18 06:53:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `angle` int(11) DEFAULT 0,
  `is_main` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `angle`, `is_main`, `sort_order`) VALUES
(31, 33, 'product_33_1773727529_0.jpg', 0, 1, 0),
(32, 34, 'product_34_1773727969_0.png', 0, 1, 0),
(34, 36, 'product_36_1773806013_0.png', 0, 1, 0),
(35, 37, 'product_37_1773806454_0.png', 0, 1, 0),
(36, 38, 'product_38_1773808522_0.png', 0, 1, 0),
(40, 40, 'product_40_1773809748_0.png', 0, 1, 0),
(41, 41, 'product_41_1773810454_0.png', 0, 1, 0),
(42, 46, 'product_46_1773811279_0.png', 0, 1, 0),
(43, 47, 'product_47_1773815201_0.png', 0, 1, 0),
(44, 48, 'product_48_1773815705_0.png', 0, 1, 0),
(45, 49, 'product_49_1773816051_0.png', 0, 1, 0),
(46, 50, 'product_50_1773816415_0.png', 0, 1, 0),
(47, 51, 'product_51_1773816788_0.png', 0, 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` enum('XS','S','M','L','XL','XXL','XXXL') NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `color` varchar(50) DEFAULT 'Negro',
  `stock` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `size`, `price`, `color`, `stock`) VALUES
(58, 33, 'S', NULL, 'Negro', 0),
(59, 33, 'M', NULL, 'Negro', 0),
(60, 33, 'L', NULL, 'Negro', 0),
(61, 33, 'XL', NULL, 'Negro', 0),
(62, 34, 'S', NULL, 'Negro', 0),
(63, 34, 'M', NULL, 'Negro', 0),
(64, 34, 'L', NULL, 'Negro', 0),
(65, 34, 'XL', NULL, 'Negro', 0),
(70, 36, 'S', NULL, 'Negro', 0),
(71, 36, 'M', NULL, 'Negro', 0),
(72, 36, 'L', NULL, 'Negro', 0),
(73, 36, 'XL', NULL, 'Negro', 0),
(74, 37, 'S', NULL, 'Negro', 0),
(75, 37, 'M', NULL, 'Negro', 0),
(76, 37, 'L', NULL, 'Negro', 0),
(77, 37, 'XL', NULL, 'Negro', 0),
(78, 38, 'S', NULL, 'Negro', 0),
(79, 38, 'M', NULL, 'Negro', 0),
(80, 38, 'L', NULL, 'Negro', 0),
(81, 38, 'XL', NULL, 'Negro', 0),
(94, 40, 'S', NULL, 'Negro', 0),
(95, 40, 'M', NULL, 'Negro', 0),
(96, 40, 'L', NULL, 'Negro', 0),
(97, 40, 'XL', NULL, 'Negro', 0),
(98, 41, 'S', NULL, 'Negro', 0),
(99, 41, 'M', NULL, 'Negro', 0),
(100, 41, 'L', NULL, 'Negro', 0),
(101, 41, 'XL', NULL, 'Negro', 0),
(102, 46, 'S', NULL, 'Negro', 0),
(103, 46, 'M', NULL, 'Negro', 0),
(104, 46, 'L', NULL, 'Negro', 0),
(105, 46, 'XL', NULL, 'Negro', 0),
(106, 47, 'S', NULL, 'Negro', 0),
(107, 47, 'M', NULL, 'Negro', 0),
(108, 47, 'L', NULL, 'Negro', 0),
(109, 47, 'XL', NULL, 'Negro', 0),
(110, 48, 'S', NULL, 'Negro', 0),
(111, 48, 'M', NULL, 'Negro', 0),
(112, 48, 'L', NULL, 'Negro', 0),
(113, 48, 'XL', NULL, 'Negro', 0),
(114, 49, 'S', NULL, 'Negro', 0),
(115, 49, 'M', NULL, 'Negro', 0),
(116, 49, 'L', NULL, 'Negro', 0),
(117, 49, 'XL', NULL, 'Negro', 0),
(118, 50, 'S', NULL, 'Negro', 0),
(119, 50, 'M', NULL, 'Negro', 0),
(120, 50, 'L', NULL, 'Negro', 0),
(121, 50, 'XL', NULL, 'Negro', 0),
(122, 51, 'S', NULL, 'Negro', 0),
(123, 51, 'M', NULL, 'Negro', 0),
(124, 51, 'L', NULL, 'Negro', 0),
(125, 51, 'XL', NULL, 'Negro', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`, `created_at`) VALUES
(1, 'Admin Black Clothes', 'admin@blackclothes.mx', '$2y$10$PUeh78/3/SRS78Zq62K0ieRlBuIY0P4tCtSXrdUsigg0nTQR1K/om', NULL, NULL, 'admin', '2026-03-09 04:09:44'),
(2, 'Admin', 'admin@blackclothes.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9zjKZcP9yY9p8f3K9F1K2C', NULL, NULL, 'admin', '2026-03-10 03:23:24'),
(3, 'Admin Nuevo', 'admin2@blackclothes.mx', '$2y$10$n/e7D8gp5HMNcBYFynbWruTleawaAWqk5DedEnoVQW5iOAVE22MvK', NULL, NULL, 'admin', '2026-03-10 03:26:19'),
(4, 'a', 'a', '$2y$10$SfHVL4lYa9oXL2a4b95JPetdiNYyhehXKdgqe3BAeTk06m7hfv8/u', NULL, NULL, 'customer', '2026-03-10 03:27:34'),
(6, 'Admin Principal', 'admin@blackclothes.site', '$2y$10$io35w8Qp2CbMA7ARLbHUfumstSt7gkCvXsrzyeiU0D52GBFqCHPgi', NULL, NULL, 'admin', '2026-03-16 20:20:41');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT de la tabla `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Filtros para la tabla `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
