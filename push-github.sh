#!/bin/bash
# Script para enviar o plugin GuiaWP para o GitHub
# Autor: Dante Testa <https://dantetesta.com.br>
# Executar na raiz do plugin: cd wp-content/plugins/guiawp && bash push-github.sh

set -e

REPO_URL="https://github.com/dantetesta/GuiaWP.git"

# Inicializar git se necessário
if [ ! -d ".git" ]; then
    git init
    git remote add origin "$REPO_URL"
    echo "Repositório inicializado e remote adicionado."
else
    echo "Repositório git já existe."
    # Garantir que o remote está correto
    git remote set-url origin "$REPO_URL" 2>/dev/null || git remote add origin "$REPO_URL"
fi

# Criar .gitignore se não existir
if [ ! -f ".gitignore" ]; then
cat > .gitignore << 'GITIGNORE'
node_modules/
.DS_Store
Thumbs.db
*.log
push-github.sh
GITIGNORE
    echo ".gitignore criado."
fi

# Adicionar todos os arquivos
git add -A

# Commit
git commit -m "GuiaWP v2.0.0 - Sistema completo de cores dinâmicas e rodapé configurável

- 7 cores configuráveis no painel: primária, secundária, destaque, fundo, texto, rodapé, fundo categorias
- Rodapé com fundo sólido/gradiente, opacidade, direção, cores de título/texto/links/hover
- Mapeamento Tailwind→CSS variables para cores dinâmicas
- Crop CTA 800x600 na aba Aparência
- 4 colunas de anúncios relacionados no single
- Classes group-hover e bg-opacity ausentes no build Tailwind

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"

# Push
git branch -M main
git push -u origin main

echo ""
echo "Push concluído com sucesso!"
echo "Repositório: $REPO_URL"
