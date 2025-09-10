🍽️ PlataFood - Plataforma de Delivery Open Source
📖 Sobre o Projeto
PlataFood é uma plataforma web de delivery de comida, de código-fonte aberto e auto-hospedada. A proposta é oferecer a pequenos e médios estabelecimentos do ramo alimentício (restaurantes, lanchonetes, hamburguerias) uma alternativa independente e econômica às grandes plataformas de marketplace (como iFood e Rappi).

A solução visa eliminar as altas taxas de comissão cobradas por esses serviços, dando ao lojista controle total sobre seu cardápio, pedidos, clientes e identidade visual, com um custo operacional significativamente menor.

✨ Funcionalidades Principais
O sistema é dividido em duas interfaces principais: a Vitrine da Loja para o cliente e o Painel Administrativo para o lojista.

🛍️ Interface do Cliente (Vitrine)
Cardápio Dinâmico: Produtos organizados por categorias.

Filtro de Categorias: Navegação rápida para a seção desejada do cardápio.

Personalização de Produtos: Adicione itens opcionais com preços variáveis (ex: "Hambúrguer com adicional de bacon").

Carrinho de Compras: Adicione produtos, ajuste quantidades e inclua observações.

Sistema de Usuários: Cadastro e login para uma experiência personalizada.

Perfil do Usuário: Acesse o histórico de pedidos e atualize dados cadastrais.

Cálculo de Entrega: Taxa de entrega calculada automaticamente com base no bairro.

Status da Loja: Indicador visual em tempo real se a loja está "Aberta" ou "Fechada".

Checkout Simplificado: Finalize o pedido com métodos de pagamento como Dinheiro, Cartão e PIX.

⚙️ Painel do Administrador
Dashboard Central: Visão geral com estatísticas de pedidos, total de clientes e gráfico de faturamento mensal.

Gerenciamento de Produtos (CRUD): Controle total para adicionar, editar e excluir produtos (nome, preço, imagem, categoria).

Gerenciamento de Categorias (CRUD): Crie e organize as seções do seu cardápio.

Gerenciamento de Adicionais: Crie opções personalizáveis para seus produtos.

Gerenciamento de Pedidos: Acompanhe todos os pedidos recebidos, visualize detalhes e atualize o status (Pendente, Preparando, A caminho, Entregue).

Gerenciamento de Entregas (CRUD): Cadastre bairros e suas respectivas taxas de entrega.

Gerenciamento de Horários: Defina os dias e horários de funcionamento da loja.

Customização: Altere a logo da sua loja e escolha entre temas claro ou escuro para o painel.

🚀 Tecnologias Utilizadas
Este projeto foi construído utilizando as seguintes tecnologias:

Back-end: PHP

Front-end: HTML, CSS, JavaScript

Banco de Dados: MySQL

Servidor Web (Exemplo): Apache

🔧 Instalação e Execução
Para rodar este projeto localmente, siga os passos abaixo:

Pré-requisitos:

Um ambiente de servidor web local (XAMPP, WAMP, LAMP).

PHP 7.4 ou superior.

MySQL ou MariaDB.

Git (opcional, para clonar o repositório).

Passos:

Clone o repositório:

Bash

git clone https://github.com/seu-usuario/platafood.git
Navegue até o diretório do seu servidor web (ex: htdocs no XAMPP) e coloque a pasta do projeto lá.

Crie um banco de dados MySQL para o projeto.

Importe o arquivo .sql (que se encontra na pasta /database) para o banco de dados criado.

Renomeie o arquivo config-example.php para config.php e atualize com as suas credenciais do banco de dados.

Inicie seu servidor Apache e MySQL.

Abra seu navegador e acesse http://localhost/
