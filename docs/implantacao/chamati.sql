-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Tempo de geração: 02/01/2025 às 18:57
-- Versão do servidor: 8.0.40
-- Versão do PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `chamati`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `arquivos_chamado`
--

CREATE TABLE `arquivos_chamado` (
  `id` int NOT NULL,
  `chamado_id` int NOT NULL,
  `caminho_arquivo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `data_upload` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chamados`
--

CREATE TABLE `chamados` (
  `id` int NOT NULL,
  `numero_chamado` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `filial_id` int NOT NULL,
  `setor_id` int NOT NULL,
  `tipo_prioridade` enum('Normal','Urgente') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Normal',
  `codigo_acesso_remoto` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_contato` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefone_contato` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descricao` text COLLATE utf8mb4_general_ci NOT NULL,
  `imagem_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Aberto','Em Progresso','Fechado') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Aberto',
  `usuario_abertura_id` int NOT NULL,
  `tecnico_responsavel_id` int DEFAULT NULL,
  `data_abertura` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_transferencia` timestamp NULL DEFAULT NULL,
  `data_fechamento` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `filiais`
--

CREATE TABLE `filiais` (
  `id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_chamados`
--

CREATE TABLE `historico_chamados` (
  `id` int NOT NULL,
  `chamado_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `status_chamado` enum('Aberto','Em Progresso','Fechado') COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_general_ci NOT NULL,
  `data_acao` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `setores`
--

CREATE TABLE `setores` (
  `id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `filial_id` int NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `senha_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` enum('Tecnico','Funcionario') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Funcionario',
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `tipo`, `status`, `criado_em`, `atualizado_em`) VALUES
(1, 'TECNICO', 'tecnico1@chamati.com', '$2y$10$Q3RZVFkGIaiW3loAQ4ekxefH7ZV0FS0JPcXHzT8fOgZOp79gUTOVW', 'Tecnico', 'Ativo', '2024-12-24 03:55:05', '2024-12-27 20:37:20'),
(2, 'TECNICO', 'tecnico2@chamati.com', '$2y$10$88kqzEGiwNlUWYA/HbJdj.kytXRYs.pvzhNwqJ/4/NAtXtfPoN7zC', 'Tecnico', 'Ativo', '2024-12-24 03:55:18', '2024-12-27 20:37:43'),
(3, 'FUNCIONARIO', 'funcionario@chamati.com', '$2y$10$najNf6iZ9ZdpR5fncL9gM.qejpUW1J1Q43P11krAlsOuAyZ1FmUHm', 'Funcionario', 'Ativo', '2024-12-24 03:56:05', '2024-12-27 20:37:07');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `arquivos_chamado`
--
ALTER TABLE `arquivos_chamado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chamado_id` (`chamado_id`);

--
-- Índices de tabela `chamados`
--
ALTER TABLE `chamados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_chamado` (`numero_chamado`),
  ADD KEY `filial_id` (`filial_id`),
  ADD KEY `setor_id` (`setor_id`),
  ADD KEY `usuario_abertura_id` (`usuario_abertura_id`),
  ADD KEY `tecnico_responsavel_id` (`tecnico_responsavel_id`);

--
-- Índices de tabela `filiais`
--
ALTER TABLE `filiais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `historico_chamados`
--
ALTER TABLE `historico_chamados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chamado_id` (`chamado_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `setores`
--
ALTER TABLE `setores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `filial_id` (`filial_id`);

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
-- AUTO_INCREMENT de tabela `arquivos_chamado`
--
ALTER TABLE `arquivos_chamado`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chamados`
--
ALTER TABLE `chamados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `filiais`
--
ALTER TABLE `filiais`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_chamados`
--
ALTER TABLE `historico_chamados`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `setores`
--
ALTER TABLE `setores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `arquivos_chamado`
--
ALTER TABLE `arquivos_chamado`
  ADD CONSTRAINT `arquivos_chamado_ibfk_1` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`);

--
-- Restrições para tabelas `chamados`
--
ALTER TABLE `chamados`
  ADD CONSTRAINT `chamados_ibfk_1` FOREIGN KEY (`filial_id`) REFERENCES `filiais` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chamados_ibfk_2` FOREIGN KEY (`setor_id`) REFERENCES `setores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chamados_ibfk_3` FOREIGN KEY (`usuario_abertura_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chamados_ibfk_4` FOREIGN KEY (`tecnico_responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `historico_chamados`
--
ALTER TABLE `historico_chamados`
  ADD CONSTRAINT `historico_chamados_ibfk_1` FOREIGN KEY (`chamado_id`) REFERENCES `chamados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historico_chamados_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `setores`
--
ALTER TABLE `setores`
  ADD CONSTRAINT `setores_ibfk_1` FOREIGN KEY (`filial_id`) REFERENCES `filiais` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
