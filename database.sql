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
(6, 4, 'aguiar@gmail.com', '$2y$10$aND/SzBo3wd887Nh41Za.ONoX0Agwsk3Jwe9xaIx.ED60WwiFD9xy');

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
(6, 4, 'CENTRO', 8.00);

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
(4, 'nome_loja', 'loja teste');

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `lojas`
--

CREATE TABLE `lojas` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `lojas`
--

INSERT INTO `lojas` (`id`, `nome`, `data_criacao`, `ativa`) VALUES
(4, 'minha loja teste', '2025-09-10 17:41:47', 1);

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
(1, 1, 1, '2025-08-20 19:52:19', 'entregue', 130.50, 0.00, 'dinheiro', NULL),
(2, 1, 1, '2025-08-20 20:39:45', 'entregue', 55.00, 0.00, 'dinheiro', NULL),
(3, 1, 1, '2025-08-20 20:45:32', 'entregue', 27.50, 0.00, 'dinheiro', 100.00),
(4, 1, 1, '2025-08-20 20:56:30', 'entregue', 27.50, 0.00, 'pix', NULL),
(5, 1, 1, '2025-08-20 21:02:45', 'entregue', 27.50, 0.00, 'pix', NULL),
(6, 1, 1, '2025-08-20 21:04:29', 'entregue', 27.50, 0.00, 'pix', NULL),
(7, 1, 1, '2025-08-20 21:09:57', 'entregue', 27.50, 0.00, 'pix', NULL),
(8, 1, 1, '2025-08-22 18:19:23', 'entregue', 357.50, 0.00, 'pix', NULL),
(9, 1, 1, '2025-08-22 19:32:59', 'entregue', 34.50, 0.00, 'cartao', NULL),
(10, 1, 1, '2025-08-22 20:11:07', 'entregue', 26.00, 0.00, 'cartao', NULL),
(11, 1, 1, '2025-08-22 20:13:41', 'entregue', 26.00, 0.00, 'cartao', NULL),
(12, 1, 1, '2025-08-22 20:42:27', 'entregue', 55.00, 0.00, 'cartao', NULL),
(13, 1, 1, '2025-08-22 20:48:50', 'entregue', 53.50, 0.00, 'cartao', NULL),
(14, 1, 1, '2025-08-22 20:55:43', 'entregue', 53.50, 0.00, 'cartao', NULL),
(15, 1, 2, '2025-08-22 21:17:24', 'entregue', 30.00, 4.00, '', NULL),
(16, 1, 2, '2025-08-22 21:17:55', 'entregue', 30.00, 4.00, '', NULL),
(17, 1, 2, '2025-08-22 21:21:07', 'entregue', 31.50, 4.00, 'cartao', NULL),
(18, 1, 2, '2025-08-22 21:35:04', 'entregue', 30.00, 4.00, 'cartao', NULL),
(19, 1, 2, '2025-08-23 19:11:59', 'entregue', 31.50, 4.00, 'cartao', NULL),
(20, 1, 2, '2025-08-23 19:48:40', 'entregue', 31.50, 4.00, 'cartao', NULL),
(21, 1, 2, '2025-08-23 20:59:42', 'entregue', 30.00, 4.00, 'cartao', NULL),
(22, 1, 2, '2025-08-23 21:23:01', 'entregue', 77.00, 4.00, 'cartao', NULL),
(23, 1, 2, '2025-08-23 21:42:48', 'entregue', 30.00, 4.00, 'cartao', NULL),
(24, 1, 2, '2025-08-23 21:47:36', 'entregue', 37.00, 4.00, 'cartao', NULL),
(25, 1, 2, '2025-08-23 21:51:09', 'entregue', 30.00, 4.00, 'cartao', NULL),
(26, 1, 2, '2025-08-23 21:53:52', 'entregue', 30.00, 4.00, 'cartao', NULL),
(27, 1, 2, '2025-08-23 21:57:26', 'entregue', 30.00, 4.00, 'cartao', NULL),
(28, 1, 1, '2025-08-25 18:38:29', 'entregue', 32.50, 5.00, 'pix', NULL),
(29, 1, 2, '2025-08-27 11:43:51', 'entregue', 57.50, 4.00, 'cartao', NULL),
(30, 1, 2, '2025-08-27 12:10:04', 'entregue', 31.50, 4.00, 'dinheiro', NULL),
(31, NULL, 1, '2025-08-27 19:02:08', 'saiu_para_entrega', 64.00, 5.00, 'dinheiro', NULL),
(32, NULL, 1, '2025-08-27 21:34:09', 'pendente', 15.00, 5.00, 'cartao', NULL),
(33, 1, 1, '2025-08-27 21:41:28', 'entregue', 31.00, 5.00, 'dinheiro', NULL),
(34, 1, 1, '2025-09-10 17:05:14', 'entregue', 12.00, 5.00, 'dinheiro', NULL);

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
(21, 17, 5, 1, 27.50, 'Opção: Com linguiça.'),
(22, 18, 5, 1, 26.00, ''),
(23, 19, 5, 1, 27.50, 'Opção: Com linguiça.'),
(24, 20, 5, 1, 27.50, 'Opção: Com linguiça.'),
(25, 21, 5, 1, 26.00, ''),
(26, 22, 5, 1, 26.00, ''),
(27, 22, 6, 1, 7.00, ''),
(28, 22, 5, 1, 26.00, ''),
(29, 22, 6, 1, 7.00, ''),
(30, 22, 6, 1, 7.00, ''),
(31, 23, 5, 1, 26.00, ''),
(32, 24, 6, 1, 7.00, ''),
(33, 24, 5, 1, 26.00, ''),
(34, 25, 5, 1, 26.00, ''),
(35, 26, 5, 1, 26.00, ''),
(36, 27, 5, 1, 26.00, ''),
(37, 28, 5, 1, 27.50, 'Opção: Com linguiça.'),
(38, 29, 5, 1, 27.50, 'Opção: Com linguiça.'),
(39, 29, 5, 1, 26.00, ''),
(40, 30, 5, 1, 27.50, 'Opção: Com linguiça.'),
(41, 31, 5, 1, 26.00, ''),
(42, 31, 6, 1, 7.00, ''),
(43, 31, 5, 1, 26.00, ''),
(45, 33, 5, 1, 26.00, ''),
(46, 34, 6, 1, 7.00, '');

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
  `imagem` varchar(255) DEFAULT 'default.jpg',
  `ativo` tinyint(4) DEFAULT 1,
  `categoria_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `loja_id`, `nome`, `descricao`, `preco`, `imagem`, `ativo`, `categoria_id`) VALUES
(5, 1, 'Prensado de Contra-File', 'Pão 20cm, Contrafilé, Milho, Ervilha, Batata-Palha, Cenoura ralada, Purê, Azeitona, Ovo de codorna, Passas, Queijo ralado, Molho(tomate,cebola e pimentão), ketchup, Maionese, Mostarda, Maionese caseira, Mussarela, Catupiry ou Cheddar.', 26.00, '68a62e95a21b3.jpeg', 1, NULL),
(6, 1, 'Coca-cola', 'lata 500ml', 7.00, '68a8c2113980d.jpg', 1, NULL),
(8, 1, 'pizza', 'pizza familia', 33.00, '68c1b1dcb7fb4.jpg', 1, NULL),
(9, 2, 'pizza', 'grande', 33.00, '68c1c0040cbb1.jpg', 1, NULL);

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
(1, 'master@email.com', '$2y$10$nB2t2TOFkdrC71wrjesYaOv.Tpp3dpFFX7Fg4rBjIlLKrrYdqM3me', '2025-08-27 20:01:55');

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
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `endereco`, `bairro`, `telefone`, `criado_em`) VALUES
(1, 'Nathalia aguiar', 'nathaliaaguiar444@gmail.com', '$2y$10$A32VdFIEwb198vG94GaRuOkt9WEMnev4HVnygERa.TaQXZzztBK4.', 'rua meridional numero 89 jardim paraiso', 'CENTRO', '21973140724', '2025-08-20 19:34:03'),
(2, 'anderson martins', 'anderson@gmail.com', '$2y$10$jZmvDLFs2PxfT8Td4VcCDuASshCMducOSlDmpcg/bXcWCju2G4fCe', 'Rua Meridional número 89', 'guacha', '2198989898', '2025-08-22 21:15:39'),
(3, 'brenda', 'brenda@gmail.com', '$2y$10$nxVbnidLN8HrMJcywYoDGuiYKkLN/yoOoS1m7XpZDYK7DT1A8NJOK', 'rua a ', 'CENTRO', '21973140724', '2025-09-10 18:22:40');

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
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `admin_antigo`
--
ALTER TABLE `admin_antigo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `areas_entrega`
--
ALTER TABLE `areas_entrega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `horarios_funcionamento`
--
ALTER TABLE `horarios_funcionamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `lojas`
--
ALTER TABLE `lojas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE;

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
