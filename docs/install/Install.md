# 🚀 Guia de Instalação - Sistema MRP

Este guia fornece instruções detalhadas para configurar o Sistema MRP em diferentes ambientes.

## 📋 Índice

- [Pré-requisitos](#-pré-requisitos)
- [Instalação Local](#-instalação-local)
- [Configuração do Banco](#-configuração-do-banco)
- [Configuração do Sistema](#️-configuração-do-sistema)
- [Verificação da Instalação](#-verificação-da-instalação)
- [Ambientes Específicos](#-ambientes-específicos)
- [Solução de Problemas](#-solução-de-problemas)

## ⚙️ Pré-requisitos

### Requisitos Mínimos

| Componente | Versão Mínima | Recomendada | Observações |
|------------|---------------|-------------|-------------|
| **PHP** | 7.4 | 8.0+ | Com extensões listadas abaixo |
| **MySQL** | 5.7 | 8.0+ | Ou MariaDB 10.2+ |
| **Servidor Web** | Qualquer | Apache 2.4+ | Nginx, IIS ou PHP built-in |
| **Memória RAM** | 512MB | 1GB+ | Para ambiente de desenvolvimento |
| **Espaço em Disco** | 50MB | 100MB+ | Para logs e cache |

### Extensões PHP Obrigatórias

```bash
# Verificar extensões instaladas
php -m | grep -E "(pdo|mysql|mbstring|json|curl)"

# Extensões necessárias:
- pdo_mysql    # Conexão com MySQL
- mbstring     # Manipulação de strings UTF-8
- json         # Manipulação de dados JSON
- curl         # Requisições HTTP (opcional)
- openssl      # Segurança (opcional)
```

### Verificação de Ambiente

```bash
# Verificar versão do PHP
php --version

# Verificar versão do MySQL
mysql --version

# Verificar se o MySQL está rodando
# Windows:
net start | findstr -i mysql

# Linux/Mac:
sudo service mysql status
```

## 💻 Instalação Local

### Opção 1: Download Direto

```bash
# 1. Baixar o projeto
# Download do ZIP ou clone do repositório

# 2. Extrair para pasta do servidor web
# Windows (XAMPP): C:\xampp\htdocs\sistema-mrp
# Linux: /var/www/html/sistema-mrp
# Mac: /Applications/XAMPP/htdocs/sistema-mrp
```

### Opção 2: Git Clone

```bash
# 1. Clonar repositório
git clone https://github.com/seu-usuario/sistema-mrp.git
cd sistema-mrp

# 2. Verificar estrutura
ls -la
# Deve mostrar: config/, controllers/, models/, views/, assets/, *.php
```

### Opção 3: PHP Built-in Server

```bash
# Para desenvolvimento rápido
cd sistema-mrp
php -S localhost:8000

# Acessar: http://localhost:8000
```

## 🗄️ Configuração do Banco

### Passo 1: Criar Banco de Dados

#### Via linha de comando:
```sql
# Conectar ao MySQL
mysql -u root -p

# Criar banco
CREATE DATABASE sistema_mrp 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

# Verificar se foi criado
SHOW DATABASES;

# Sair
EXIT;
```

#### Via phpMyAdmin:
1. Acesse `http://localhost/phpmyadmin`
2. Clique em "Novo"
3. Nome: `sistema_mrp`
4. Collation: `utf8mb4_unicode_ci`
5. Clique "Criar"

### Passo 2: Importar Schema

#### Via linha de comando:
```bash
# Importar estrutura e dados
mysql -u root -p sistema_mrp < database/schema.sql

# Verificar se importou corretamente
mysql -u root -p -e "USE sistema_mrp; SHOW TABLES;"
```

#### Via phpMyAdmin:
1. Selecione banco `sistema_mrp`
2. Clique na aba "Importar"
3. Escolha arquivo `database/schema.sql`
4. Clique "Executar"

### Passo 3: Verificar Dados

```sql
# Conectar ao banco
mysql -u root -p sistema_mrp

# Verificar tabelas criadas
SHOW TABLES;
# Deve mostrar: bom, componentes, produtos

# Verificar dados inseridos
SELECT COUNT(*) as total FROM componentes;
# Deve retornar: 6 ou 7 (se adicionou Pneus)

SELECT COUNT(*) as total FROM produtos;
# Deve retornar: 2

SELECT COUNT(*) as total FROM bom;
# Deve retornar: 6
```

## ⚙️ Configuração do Sistema

### Configurar Conexão com Banco

Edite o arquivo `config/database.php`:

```php
<?php
class Database {
    // AJUSTE ESTAS CONFIGURAÇÕES
    private const HOST = 'localhost';        // Servidor MySQL
    private const DB_NAME = 'sistema_mrp';   // Nome do banco
    private const USERNAME = 'root';         // Usuário MySQL
    private const PASSWORD = 'sua_senha';    // Senha MySQL
    private const CHARSET = 'utf8mb4';
    
    // ... resto do código permanece igual
}
```

### Configurações por Ambiente

#### XAMPP (Windows/Mac/Linux):
```php
private const HOST = 'localhost';
private const USERNAME = 'root';
private const PASSWORD = '';  // Senha vazia por padrão
```

#### WAMP (Windows):
```php
private const HOST = 'localhost';
private const USERNAME = 'root';
private const PASSWORD = '';  // Ou 'root'
```

#### MAMP (Mac):
```php
private const HOST = 'localhost';
private const USERNAME = 'root';
private const PASSWORD = 'root';
```

#### MySQL Standalone:
```php
private const HOST = 'localhost';
private const USERNAME = 'seu_usuario';
private const PASSWORD = 'sua_senha';
```

### Configurar Servidor Web

#### Apache (.htaccess)
Crie arquivo `.htaccess` na raiz:
```apache
# Segurança básica
Options -Indexes

# Rewrite rules (opcional)
RewriteEngine On

# Forçar HTTPS (produção)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Headers de segurança
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

#### Nginx
Configuração básica:
```nginx
server {
    listen 80;
    server_name localhost;
    root /caminho/para/sistema-mrp;
    index index.php;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## ✅ Verificação da Instalação

### Teste 1: Página Principal
```bash
# Acesse no navegador:
http://localhost/sistema-mrp/

# Deve mostrar:
✅ "Conexão com Banco de Dados: OK!"
✅ Dashboard com 4 cards de estatísticas
✅ Seção "Ações Rápidas"
✅ Exemplo prático
```

### Teste 2: Módulo Estoque
```bash
# Acesse:
http://localhost/sistema-mrp/estoque.php

# Deve mostrar:
✅ 4 cards de estatísticas
✅ Tabela com 6-7 componentes
✅ Alerta de "Estoque Baixo" (se houver)
✅ Botão "Novo Componente" funcionando
```

### Teste 3: Módulo MRP
```bash
# Acesse:
http://localhost/sistema-mrp/mrp.php

# Teste o exemplo:
1. Digite: Bicicletas = 6, Computadores = 3
2. Clique "Calcular Necessidades"
3. Deve mostrar lista de compras correta
```

### Teste 4: Responsividade
```bash
# No navegador:
1. Pressione F12
2. Clique no ícone de dispositivo móvel
3. Teste diferentes resoluções
4. Verifique se layout se adapta
```

## 🖥️ Ambientes Específicos

### XAMPP

#### Instalação:
1. Baixe XAMPP: https://www.apachefriends.org/
2. Instale em `C:\xampp` (Windows)
3. Inicie Apache e MySQL no painel

#### Configuração:
```bash
# Pasta do projeto:
C:\xampp\htdocs\sistema-mrp\

# URL de acesso:
http://localhost/sistema-mrp/

# phpMyAdmin:
http://localhost/phpmyadmin/
```

### WAMP

#### Configuração:
```bash
# Pasta do projeto:
C:\wamp64\www\sistema-mrp\

# URL de acesso:
http://localhost/sistema-mrp/
```

### Linux (Ubuntu/Debian)

#### Instalação das dependências:
```bash
# Atualizar sistema
sudo apt update

# Instalar LAMP stack
sudo apt install apache2 mysql-server php php-mysql php-mbstring

# Habilitar módulos
sudo a2enmod rewrite
sudo systemctl restart apache2

# Configurar MySQL
sudo mysql_secure_installation
```

#### Configuração:
```bash
# Pasta do projeto:
/var/www/html/sistema-mrp/

# Permissões:
sudo chown -R www-data:www-data /var/www/html/sistema-mrp/
sudo chmod -R 755 /var/www/html/sistema-mrp/
```

### Docker (Opcional)

#### docker-compose.yml:
```yaml
version: '3.8'
services:
  web:
    image: php:8.0-apache
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
  
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: sistema_mrp
    ports:
      - "3306:3306"
    volumes:
      - ./database/schema.sql:/docker-entrypoint-initdb.d/schema.sql
```

#### Executar:
```bash
docker-compose up -d
# Acesso: http://localhost:8000
```

## 🔧 Solução de Problemas

### Problema 1: "Call to undefined function PDO"

**Causa**: Extensão PDO não instalada

**Solução**:
```bash
# Ubuntu/Debian:
sudo apt install php-mysql php-pdo

# CentOS/RHEL:
sudo yum install php-mysql php-pdo

# Windows (XAMPP):
# Editar php.ini, descomentar:
extension=pdo_mysql
```

### Problema 2: "Access denied for user 'root'"

**Causa**: Senha incorreta ou usuário sem permissão

**Solução**:
```sql
# Resetar senha do root:
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'nova_senha';
FLUSH PRIVILEGES;
EXIT;

# Testar:
mysql -u root -p
```

### Problema 3: "Can't connect to MySQL server"

**Causa**: MySQL não está rodando

**Solução**:
```bash
# Windows:
net start mysql

# Linux:
sudo service mysql start
# ou
sudo systemctl start mysql

# Mac:
sudo /usr/local/mysql/support-files/mysql.server start
```

### Problema 4: "Unknown database 'sistema_mrp'"

**Causa**: Banco não foi criado

**Solução**:
```sql
mysql -u root -p
CREATE DATABASE sistema_mrp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Problema 5: Caracteres especiais quebrados

**Causa**: Charset incorreto

**Solução**:
```sql
# Verificar charset do banco:
SELECT @@character_set_database;

# Se não for utf8mb4, recriar:
DROP DATABASE sistema_mrp;
CREATE DATABASE sistema_mrp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Problema 6: Página em branco

**Causa**: Erro no PHP não exibido

**Solução**:
```php
# Adicionar no início do index.php temporariamente:
error_reporting(E_ALL);
ini_set('display_errors', 1);

# Verificar logs:
# XAMPP: C:\xampp\php\logs\php_error_log
# Linux: /var/log/apache2/error.log
```

### Problema 7: Atalhos de teclado não funcionam

**Causa**: JavaScript não carregou

**Solução**:
1. Verificar se `assets/js/app.js` existe
2. Verificar console do navegador (F12)
3. Verificar se Bootstrap está carregando

### Verificação Final

Execute este checklist após instalação:

- [ ] ✅ Página inicial carrega sem erros
- [ ] ✅ Conexão com banco funcionando
- [ ] ✅ 6+ componentes listados no estoque
- [ ] ✅ Cálculo MRP funciona (6 bicicletas + 3 computadores)
- [ ] ✅ Modais abrem e fecham corretamente
- [ ] ✅ Atalhos de teclado funcionam (F2, Ctrl+Shift+E)
- [ ] ✅ Layout responsivo no mobile
- [ ] ✅ Sem erros no console do navegador

## 📞 Suporte

Se ainda tiver problemas:

1. **Verifique logs de erro** do servidor web
2. **Teste conexão** com banco separadamente
3. **Confirme permissões** de arquivo (Linux)
4. **Limpe cache** do navegador
5. **Reinicie serviços** (Apache, MySQL)

---

*Instalação concluída com sucesso? Acesse o [Guia de Testes](TESTING.md) para validar todas as funcionalidades!*