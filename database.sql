-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/09/2025 às 21:01
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `anotaai_clone`
--
CREATE DATABASE anotaai_clone;
-- --------------------------------------------------------
USE anotaai_clone;

--
-- Estrutura para tabela `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `admins`
--

INSERT INTO `admins` (`id`, `loja_id`, `email`, `senha`) VALUES
(19, 17, 'acaimaria@gmail.com', '$2y$10$Yl81lxfMqtpBMgAbhIeY6OfbdtRV.B0FYyn1gLTzhEEtX2.xPkdN6');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_antigo`
--

CREATE TABLE `admin_antigo` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `admin_antigo`
--

INSERT INTO `admin_antigo` (`id`, `usuario`, `senha`) VALUES
(1, 'admin', '$2y$10$MdzgqOdlBiuY5ixVTVxbOe7PB0E5c2PfTKEZC7i5.PMFDoxzP2CjO');

-- --------------------------------------------------------

--
-- Estrutura para tabela `areas_entrega`
--

CREATE TABLE `areas_entrega` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) DEFAULT NULL,
  `bairro` varchar(100) NOT NULL,
  `taxa_entrega` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `areas_entrega`
--

INSERT INTO `areas_entrega` (`id`, `loja_id`, `bairro`, `taxa_entrega`) VALUES
(19, 17, 'paraíso', 8.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `loja_id`, `nome`, `ordem`) VALUES
(9, 17, 'Açai', 0),
(10, 17, 'Coca-cola', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `loja_id` int(11) NOT NULL,
  `chave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`loja_id`, `chave`, `valor`) VALUES
(17, 'nome_loja', 'Açaí da Maria'),
(18, 'nome_loja', 'lugarzinho');

-- --------------------------------------------------------

--
-- Estrutura para tabela `horarios_funcionamento`
--

CREATE TABLE `horarios_funcionamento` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) DEFAULT NULL,
  `dia_semana` int(1) NOT NULL COMMENT '0=Domingo, 1=Segunda, ..., 6=Sábado',
  `ativo` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Aberto, 0 = Fechado',
  `horario_abertura` time DEFAULT NULL,
  `horario_fechamento` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `horarios_funcionamento`
--

INSERT INTO `horarios_funcionamento` (`id`, `loja_id`, `dia_semana`, `ativo`, `horario_abertura`, `horario_fechamento`) VALUES
(190, 17, 0, 1, '09:00:00', '18:00:00'),
(191, 17, 1, 1, '09:00:00', '18:00:00'),
(192, 17, 2, 1, '09:00:00', '18:00:00'),
(193, 17, 3, 1, '09:00:00', '18:00:00'),
(194, 17, 4, 1, '09:00:00', '18:00:00'),
(195, 17, 5, 1, '09:00:00', '18:00:00'),
(196, 17, 6, 1, '09:00:00', '18:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `lojas`
--

CREATE TABLE `lojas` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL COMMENT 'Senha com hash para o login da loja',
  `aprovado` tinyint(1) NOT NULL DEFAULT 0,
  `telefone` varchar(20) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_analise` timestamp NULL DEFAULT NULL,
  `observacao_analise` text DEFAULT NULL,
  `ativa` tinyint(1) DEFAULT 1,
  `cep` varchar(10) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `lojas`
--

INSERT INTO `lojas` (`id`, `nome`, `email`, `senha`, `aprovado`, `telefone`, `endereco`, `bairro`, `data_criacao`, `data_analise`, `observacao_analise`, `ativa`, `cep`, `cidade`, `estado`, `numero`, `lat`, `lng`, `latitude`, `longitude`, `logo`) VALUES
(17, 'Açaí da Maria', 'acaimaria@gmail.com', '$2y$10$Yl81lxfMqtpBMgAbhIeY6OfbdtRV.B0FYyn1gLTzhEEtX2.xPkdN6', 1, '21993546758', 'Rua Litoral', 'Paraíso', '2025-11-06 17:35:52', NULL, NULL, 1, '26297-318', 'Nova Iguaçu', NULL, '472', -22.819177, -43.5932952, NULL, NULL, 'logo_loja_17.png'),
(18, 'lugarzinho', 'lugarzinho@gmail.com', '$2y$10$mZqMU8WtnIRgNi7TBj1DsOnVMoqTxdVVmCjKrKC9Ff740KZ70bDG2', 1, NULL, 'Rua Litoral', 'Paraíso', '2025-12-02 18:24:12', NULL, NULL, 1, '26297318', 'Nova Iguaçu', 'RJ', NULL, NULL, NULL, -22.81917700, -43.59329520, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `lojas_excluidas`
--

CREATE TABLE `lojas_excluidas` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `motivo` varchar(255) NOT NULL,
  `data_exclusao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `lojas_excluidas`
--

INSERT INTO `lojas_excluidas` (`id`, `loja_id`, `nome`, `email`, `endereco`, `bairro`, `motivo`, `data_exclusao`) VALUES
(1, 4, 'minha loja teste', '', NULL, NULL, 'Descumprimento das diretrizes', '2025-12-02 12:55:57'),
(2, 15, 'Pizzaria do Zé', 'pizzari@gmail.com', 'rua aqui', 'marinha', 'Descumprimento das diretrizes', '2025-12-02 12:56:06'),
(3, 5, 'prensado da fran', 'prensado@gmail.com', 'rua meridional', 'guacha', 'Descumprimento das diretrizes', '2025-12-02 12:56:10'),
(4, 16, 'Sobre Dom', 'sobredom1@gmail.com', 'Rua Litoral', 'Paraíso', 'Descumprimento das diretrizes', '2025-12-02 12:56:19');

-- --------------------------------------------------------

--
-- Estrutura para tabela `lojas_favoritas`
--

CREATE TABLE `lojas_favoritas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `lojas_favoritas`
--

INSERT INTO `lojas_favoritas` (`id`, `usuario_id`, `loja_id`, `criado_em`) VALUES
(8, 1, 17, '2025-11-11 11:56:16'),
(11, 5, 17, '2025-12-02 17:58:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `lojas_recusadas`
--

CREATE TABLE `lojas_recusadas` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `data_recusa` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `data` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pendente','preparando','saiu_para_entrega','entregue','cancelado','aguardando_pagamento') NOT NULL DEFAULT 'pendente',
  `total` decimal(10,2) NOT NULL,
  `taxa_entrega` decimal(10,2) DEFAULT 0.00,
  `metodo_pagamento` enum('dinheiro','cartao','pix') NOT NULL,
  `troco_para` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pedidos`
--

INSERT INTO `pedidos` (`id`, `loja_id`, `usuario_id`, `data`, `status`, `total`, `taxa_entrega`, `metodo_pagamento`, `troco_para`) VALUES
(1, 1, 1, '2025-08-20 22:52:19', 'entregue', 130.50, 0.00, 'dinheiro', NULL),
(2, 1, 1, '2025-08-20 23:39:45', 'entregue', 55.00, 0.00, 'dinheiro', NULL),
(3, 1, 1, '2025-08-20 23:45:32', 'entregue', 27.50, 0.00, 'dinheiro', 100.00),
(4, 1, 1, '2025-08-20 23:56:30', 'entregue', 27.50, 0.00, 'pix', NULL),
(5, 1, 1, '2025-08-21 00:02:45', 'entregue', 27.50, 0.00, 'pix', NULL),
(6, 1, 1, '2025-08-21 00:04:29', 'entregue', 27.50, 0.00, 'pix', NULL),
(7, 1, 1, '2025-08-21 00:09:57', 'entregue', 27.50, 0.00, 'pix', NULL),
(8, 1, 1, '2025-08-22 21:19:23', 'entregue', 357.50, 0.00, 'pix', NULL),
(9, 1, 1, '2025-08-22 22:32:59', 'entregue', 34.50, 0.00, 'cartao', NULL),
(10, 1, 1, '2025-08-22 23:11:07', 'entregue', 26.00, 0.00, 'cartao', NULL),
(11, 1, 1, '2025-08-22 23:13:41', 'entregue', 26.00, 0.00, 'cartao', NULL),
(12, 1, 1, '2025-08-22 23:42:27', 'entregue', 55.00, 0.00, 'cartao', NULL),
(13, 1, 1, '2025-08-22 23:48:50', 'entregue', 53.50, 0.00, 'cartao', NULL),
(14, 1, 1, '2025-08-22 23:55:43', 'entregue', 53.50, 0.00, 'cartao', NULL),
(28, 1, 1, '2025-08-25 21:38:29', 'entregue', 32.50, 5.00, 'pix', NULL),
(31, NULL, 1, '2025-08-27 22:02:08', 'saiu_para_entrega', 64.00, 5.00, 'dinheiro', NULL),
(32, NULL, 1, '2025-08-28 00:34:09', 'pendente', 15.00, 5.00, 'cartao', NULL),
(33, 1, 1, '2025-08-28 00:41:28', 'entregue', 31.00, 5.00, 'dinheiro', NULL),
(34, 1, 1, '2025-09-10 20:05:14', 'entregue', 12.00, 5.00, 'dinheiro', NULL),
(35, 5, 4, '2025-10-15 20:43:31', 'entregue', 53.00, 8.00, 'dinheiro', 50.00),
(36, 5, 4, '2025-10-16 22:12:51', 'entregue', 113.00, 8.00, 'cartao', NULL),
(37, 5, 4, '2025-10-16 22:33:57', 'entregue', 53.00, 8.00, 'dinheiro', 100.00),
(38, 5, 4, '2025-10-19 20:33:25', 'entregue', 23.00, 8.00, 'cartao', NULL),
(39, 17, 5, '2025-11-06 17:49:43', 'entregue', 20.00, 8.00, 'dinheiro', 50.00),
(40, 17, 1, '2025-11-11 11:54:14', 'entregue', 20.00, 8.00, 'dinheiro', NULL),
(41, 17, 1, '2025-11-11 12:15:47', 'entregue', 20.00, 8.00, 'dinheiro', 50.00),
(42, 17, 5, '2025-11-11 12:32:53', 'entregue', 20.00, 8.00, 'dinheiro', NULL),
(43, 17, 5, '2025-12-02 19:32:47', 'cancelado', 32.00, 8.00, 'dinheiro', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_itens`
--

CREATE TABLE `pedido_itens` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `observacao` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pedido_itens`
--

INSERT INTO `pedido_itens` (`id`, `pedido_id`, `produto_id`, `quantidade`, `preco`, `observacao`) VALUES
(4, 2, 5, 2, 27.50, 'sem milho'),
(5, 3, 5, 1, 27.50, ''),
(6, 4, 5, 1, 27.50, ''),
(7, 5, 5, 1, 27.50, 'sem milho'),
(8, 6, 5, 1, 27.50, ''),
(9, 7, 5, 1, 27.50, ''),
(10, 8, 5, 13, 27.50, ''),
(11, 9, 6, 1, 7.00, ''),
(12, 9, 5, 1, 27.50, ''),
(13, 10, 5, 1, 26.00, ''),
(14, 11, 5, 1, 26.00, ''),
(15, 12, 5, 1, 27.50, 'Opção: Com linguiça.'),
(16, 12, 5, 1, 27.50, 'Opção: Com linguiça.'),
(17, 13, 5, 1, 26.00, ''),
(18, 13, 5, 1, 27.50, 'Opção: Com linguiça.'),
(19, 14, 5, 1, 26.00, ''),
(20, 14, 5, 1, 27.50, 'Opção: Com linguiça.'),
(37, 28, 5, 1, 27.50, 'Opção: Com linguiça.'),
(41, 31, 5, 1, 26.00, ''),
(42, 31, 6, 1, 7.00, ''),
(43, 31, 5, 1, 26.00, ''),
(45, 33, 5, 1, 26.00, ''),
(46, 34, 6, 1, 7.00, ''),
(48, 36, 12, 1, 15.00, ''),
(49, 36, 13, 1, 45.00, ''),
(50, 36, 12, 1, 15.00, ''),
(51, 36, 12, 1, 15.00, ''),
(52, 36, 12, 1, 15.00, ''),
(53, 37, 13, 1, 45.00, ''),
(54, 38, 12, 1, 15.00, ''),
(55, 39, 17, 1, 12.00, ''),
(56, 40, 17, 1, 12.00, ''),
(57, 41, 17, 1, 12.00, ''),
(58, 42, 17, 1, 12.00, ''),
(59, 43, 17, 1, 12.00, ''),
(60, 43, 17, 1, 12.00, '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) DEFAULT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT 'default.jpg',
  `ativo` tinyint(4) DEFAULT 1,
  `categoria_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `loja_id`, `nome`, `descricao`, `preco`, `foto`, `imagem`, `ativo`, `categoria_id`) VALUES
(5, 1, 'Prensado de Contra-File', 'Pão 20cm, Contrafilé, Milho, Ervilha, Batata-Palha, Cenoura ralada, Purê, Azeitona, Ovo de codorna, Passas, Queijo ralado, Molho(tomate,cebola e pimentão), ketchup, Maionese, Mostarda, Maionese caseira, Mussarela, Catupiry ou Cheddar.', 26.00, NULL, '68a62e95a21b3.jpeg', 1, NULL),
(6, 1, 'Coca-cola', 'lata 500ml', 7.00, NULL, '68a8c2113980d.jpg', 1, NULL),
(8, 1, 'pizza', 'pizza familia', 33.00, NULL, '68c1b1dcb7fb4.jpg', 1, NULL),
(9, 2, 'pizza', 'grande', 33.00, NULL, '68c1c0040cbb1.jpg', 1, NULL),
(12, 5, 'açai', 'asas', 15.00, 'prod_68f1355ad5b61.jpg', '68f12f7e5bde4.jpg', 1, NULL),
(13, 5, 'pizza', 'asasa', 45.00, 'prod_68f135b74947e.jpg', 'default.jpg', 1, NULL),
(15, 5, 'pizza calabacon', 'gdfg', 67.00, 'prod_68f13a969b085.jpeg', 'default.jpg', 1, NULL),
(16, 16, 'açai', 'assas', 14.00, 'prod_68f5366113a51.jpg', 'default.jpg', 1, NULL),
(17, 17, 'Açai', 'Frutas: Banana em rodelas, morangos, kiwi, etc.\r\nCereais: Granola, aveia, flocos de arroz\r\nCremes e Doces: Leite condensado, leite em pó, creme de avelã com chocolate\r\nOutros: Mel, paçoca triturada, coco ralado,', 12.00, 'prod_692f0c515be29.jpg', 'default.jpg', 1, 9),
(18, 17, 'Coca-cola', '', 12.00, '692f280322056.jpg', 'default.jpg', 1, 10);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_opcoes`
--

CREATE TABLE `produto_opcoes` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `nome_opcao` varchar(100) NOT NULL COMMENT 'Ex: Com Linguiça, Com Cheddar, Borda de Catupiry',
  `preco_adicional` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produto_opcoes`
--

INSERT INTO `produto_opcoes` (`id`, `produto_id`, `nome_opcao`, `preco_adicional`) VALUES
(1, 5, 'Com linguiça', 1.50);

-- --------------------------------------------------------

--
-- Estrutura para tabela `super_admins`
--

CREATE TABLE `super_admins` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `super_admins`
--

INSERT INTO `super_admins` (`id`, `email`, `senha`, `data_criacao`) VALUES
(1, 'master@email.com', '$2y$10$nB2t2TOFkdrC71wrjesYaOv.Tpp3dpFFX7Fg4rBjIlLKrrYdqM3me', '2025-08-27 23:01:55');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `endereco` text DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `cep` varchar(10) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `numero` varchar(10) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `endereco`, `bairro`, `telefone`, `criado_em`, `cep`, `cidade`, `numero`, `lat`, `lng`) VALUES
(1, 'Nathalia aguiar', 'nathaliaaguiar444@gmail.com', '$2y$10$A32VdFIEwb198vG94GaRuOkt9WEMnev4HVnygERa.TaQXZzztBK4.', 'Rua Meridional', 'Paraíso', '21973140724', '2025-08-20 22:34:03', '26297-327', 'Nova Iguaçu', '343', NULL, NULL),
(3, 'brenda', 'brenda@gmail.com', '$2y$10$nxVbnidLN8HrMJcywYoDGuiYKkLN/yoOoS1m7XpZDYK7DT1A8NJOK', 'rua a ', 'CENTRO', '21973140724', '2025-09-10 21:22:40', NULL, NULL, NULL, NULL, NULL),
(4, 'nathalia aguiar', 'nathalia@gmail.com', '$2y$10$w89zGNX7xFPgxYTUOCsqKukwoxWXE2PrrSI3p7/YEiN/cCtX8DtAe', 'Rua Litoral', 'Paraíso', '21973140724', '2025-10-15 19:40:41', '26297-318', 'Nova Iguaçu', '380', NULL, NULL),
(5, 'Riane Bastos', 'rianebastos@gmail.com', '$2y$10$UAmx/jdkj7YinDufrSf0xu99BUEDz4f2kSgqWj2nRz2Ge22o.y.yq', 'Rua Litoral', 'Paraíso', '21993456789', '2025-11-06 17:40:28', '26297-318', 'Nova Iguaçu', '123', -22.8210476, -43.6012684);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios_excluidos`
--

CREATE TABLE `usuarios_excluidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `bairro` varchar(255) DEFAULT NULL,
  `motivo` varchar(255) NOT NULL,
  `data_exclusao` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `loja_id` (`loja_id`);

--
-- Índices de tabela `admin_antigo`
--
ALTER TABLE `admin_antigo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Índices de tabela `areas_entrega`
--
ALTER TABLE `areas_entrega`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bairro` (`bairro`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`loja_id`,`chave`);

--
-- Índices de tabela `horarios_funcionamento`
--
ALTER TABLE `horarios_funcionamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dia_semana` (`dia_semana`);

--
-- Índices de tabela `lojas`
--
ALTER TABLE `lojas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `lojas_excluidas`
--
ALTER TABLE `lojas_excluidas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `lojas_favoritas`
--
ALTER TABLE `lojas_favoritas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`,`loja_id`),
  ADD KEY `loja_id` (`loja_id`);

--
-- Índices de tabela `lojas_recusadas`
--
ALTER TABLE `lojas_recusadas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices de tabela `produto_opcoes`
--
ALTER TABLE `produto_opcoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `usuarios_excluidos`
--
ALTER TABLE `usuarios_excluidos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `admin_antigo`
--
ALTER TABLE `admin_antigo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `areas_entrega`
--
ALTER TABLE `areas_entrega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `horarios_funcionamento`
--
ALTER TABLE `horarios_funcionamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- AUTO_INCREMENT de tabela `lojas`
--
ALTER TABLE `lojas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `lojas_excluidas`
--
ALTER TABLE `lojas_excluidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `lojas_favoritas`
--
ALTER TABLE `lojas_favoritas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `lojas_recusadas`
--
ALTER TABLE `lojas_recusadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `produto_opcoes`
--
ALTER TABLE `produto_opcoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `usuarios_excluidos`
--
ALTER TABLE `usuarios_excluidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `lojas_favoritas`
--
ALTER TABLE `lojas_favoritas`
  ADD CONSTRAINT `lojas_favoritas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lojas_favoritas_ibfk_2` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `pedido_itens_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_itens_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `produto_opcoes`
--
ALTER TABLE `produto_opcoes`
  ADD CONSTRAINT `produto_opcoes_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
