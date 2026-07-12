# 📦 Guia de Instalação - SMM WooCommerce Ultimate Integration

## Requisitos Mínimos

- **WordPress:** 6.0 ou superior
- **PHP:** 8.0 ou superior
- **MySQL:** 5.7 ou superior
- **WooCommerce:** 7.0 ou superior
- **cURL:** Habilitado (para requisições de API)

## Métodos de Instalação

### **Método 1: Upload via Painel WordPress** ✅ (Mais Fácil)

1. Acesse o painel administrativo do WordPress
2. Navegue para **Plugins → Adicionar Novo**
3. Clique em **Fazer upload de plugin**
4. Selecione o arquivo `smm-woocommerce-ultimate-integration.zip`
5. Clique em **Instalar agora**
6. Clique em **Ativar plugin**

### **Método 2: Upload via FTP** ⚙️

1. Descompacte o arquivo `smm-woocommerce-ultimate-integration.zip`
2. Conecte via FTP ao seu servidor
3. Navegue para `wp-content/plugins/`
4. Faça upload da pasta `smm-woocommerce-ultimate-integration/`
5. Acesse o painel WordPress
6. Navegue para **Plugins**
7. Encontre "SMM WooCommerce Ultimate Integration"
8. Clique em **Ativar**

### **Método 3: Upload via SSH/CLI** 🖥️

```bash
# Conectar via SSH
ssh usuario@seu-dominio.com

# Navegar para plugins
cd ~/public_html/wp-content/plugins/

# Descompactar plugin
unzip smm-woocommerce-ultimate-integration.zip

# Definir permissões corretas
chmod -R 755 smm-woocommerce-ultimate-integration/

# Ativar plugin via WP-CLI
wp plugin activate smm-woocommerce-ultimate-integration
```

## Configuração Inicial

### **1. Acesse o Dashboard do Plugin**

1. No painel WordPress, vá para **SMM Integration**
2. Você verá o dashboard principal do plugin

### **2. Configurar Providers**

#### Via Dashboard Admin:
1. Clique em **Providers**
2. Clique em **Adicionar Provider**
3. Preencha:
   - **Slug:** `smartpanel` (ou outro)
   - **Nome:** `SmartPanel` (ou nome do provider)
   - **Tipo:** `smartpanel`
   - **API Key:** Cole sua chave de API
   - **API Secret:** Cole seu segredo de API
4. Clique em **Salvar**

#### Via REST API:
```bash
curl -X POST https://seu-site.com/wp-json/smm/v1/providers \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer SEU_TOKEN_WP" \
  -d '{
    "slug": "smartpanel",
    "name": "SmartPanel",
    "type": "smartpanel",
    "configuration": {
      "api_key": "sua-chave-aqui",
      "api_secret": "seu-segredo-aqui"
    }
  }'
```

### **3. Configurações Gerais**

Vá para **Configurações → SMM Integration** e configure:

- **Habilitar Plugin:** ✓ Ativo
- **Registrar Requisições de API:** ✓ Ativo
- **Processamento Assíncrono:** ✓ Ativo
- **Intervalo de Sincronização:** Cada 1 hora
- **Máximo de Tentativas:** 3
- **Timeout de Requisição:** 30 segundos
- **Habilitar Cache:** ✓ Ativo
- **Expiração de Cache:** 3600 segundos

## Endpoints REST API

### **Gerenciar Providers**

```bash
# Listar todos os providers
GET /wp-json/smm/v1/providers

# Obter um provider específico
GET /wp-json/smm/v1/providers/{slug}

# Criar novo provider
POST /wp-json/smm/v1/providers

# Atualizar provider
PUT /wp-json/smm/v1/providers/{slug}

# Deletar provider
DELETE /wp-json/smm/v1/providers/{slug}

# Sincronizar provider
POST /wp-json/smm/v1/providers/{slug}/sync

# Obter status de sincronização
GET /wp-json/smm/v1/providers/{slug}/status
```

### **Sincronização**

```bash
# Sincronizar todos os providers
POST /wp-json/smm/v1/sync/all

# Sincronizar um provider
POST /wp-json/smm/v1/sync/provider

# Obter status geral de sincronização
GET /wp-json/smm/v1/sync/status

# Obter logs de sincronização
GET /wp-json/smm/v1/sync/logs?page=1&per_page=20
```

## Estrutura de Pastas

```
smm-woocommerce-ultimate-integration/
├── admin/                          # Componentes administrativos
│   └── class-smm-admin.php
├── api/                            # Endpoints REST API
│   ├── class-smm-rest-controller.php
│   ├── class-smm-rest-providers-controller.php
│   └── class-smm-rest-sync-controller.php
├── assets/                         # CSS, JS, imagens
│   ├── css/
│   ├── js/
│   └── images/
├── background/                     # Processamento em background
│   ├── class-smm-background-processor.php
│   └── class-smm-sync-engine.php
├── classes/                        # Classes principais
│   ├── class-smm-autoloader.php
│   └── class-smm-installer.php
├── core/                           # Core do plugin
│   ├── class-smm-core.php
│   └── class-smm-security-check.php
├── frontend/                       # Componentes de frontend
│   └── class-smm-frontend.php
├── includes/                       # Helpers e utilitários
│   ├── class-smm-cache-manager.php
│   ├── config.php
│   ├── helpers/
│   └── traits/
├── providers/                      # Integração com providers
│   ├── interface-smm-provider.php
│   ├── class-smm-provider-abstract.php
│   ├── class-smm-provider-smartpanel.php
│   └── class-smm-providers-manager.php
├── languages/                      # Arquivos de tradução
├── templates/                      # Templates customizáveis
├── uploads/                        # Diretório de uploads
├── cache/                          # Diretório de cache
├── logs/                           # Diretório de logs
├── smm-woocommerce-ultimate-integration.php  # Arquivo principal
├── README.md                       # Documentação
├── INSTALACAO.md                   # Este arquivo
└── LICENSE                         # Licença GPL v2
```

## Troubleshooting

### **Problema: Plugin não aparece após upload**

✅ **Solução:**
1. Verifique se a pasta está em `wp-content/plugins/`
2. Confirme que o arquivo principal é `smm-woocommerce-ultimate-integration.php`
3. Verifique as permissões (755 para pastas, 644 para arquivos)
4. Limpe o cache do navegador (Ctrl+Shift+Del)

### **Problema: "Fatal error: Class not found"**

✅ **Solução:**
1. Verifique se PHP 8.0+ está instalado: `php -v`
2. Confirme que cURL está habilitado
3. Reinicie o PHP-FPM: `sudo systemctl restart php-fpm`

### **Problema: Sincronização não funciona**

✅ **Solução:**
1. Verifique as credenciais do provider
2. Confirme que a opção "Processamento Assíncrono" está ativa
3. Teste manualmente via REST API
4. Verifique os logs em **SMM Integration → Logs**

### **Problema: "Access Denied" na REST API**

✅ **Solução:**
1. Confirme que o usuário tem permissão `manage_options`
2. Use um token de autenticação válido
3. Verifique as headers da requisição

## Atualizações

### **Como atualizar o plugin:**

1. **Backup:** Faça backup completo do site antes
2. **Upload:** Siga o método de instalação novamente
3. **Ativar:** O WordPress detectará automaticamente a versão nova
4. **Teste:** Verifique se tudo funciona corretamente

## Desinstalação

### **Para desinstalar o plugin:**

1. Acesse **Plugins** no painel WordPress
2. Encontre "SMM WooCommerce Ultimate Integration"
3. Clique em **Desativar**
4. Clique em **Deletar**
5. Confirme a exclusão

⚠️ **Aviso:** Isso removerá todos os dados do plugin. Faça backup primeiro!

## Suporte e Documentação

📚 **Documentação Completa:** Consulte `README.md`  
🐛 **Reportar Bugs:** GitHub Issues  
💬 **Suporte:** support@lojabelastock.com  

## Licença

Este plugin é distribuído sob a licença **GPLv2 ou superior**.
Para mais detalhes, consulte o arquivo `LICENSE`.

---

**Versão:** 1.0.0  
**Última atualização:** 2024  
**Status:** Produção ✅
