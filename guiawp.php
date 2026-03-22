<?php
/**
 * Plugin Name: GuiaWP
 * Plugin URI: https://dantetesta.com.br
 * Description: Guia comercial de empresas e profissionais liberais com dashboards externos, planos grátis e premium.
 * Version: 2.0.0
 * Author: Dante Testa
 * Author URI: https://dantetesta.com.br
 * Text Domain: guiawp
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * License: GPLv2 or later
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.1.0 - 2026-03-11 - Multi-step, crop, ViaCEP, galeria, vídeos, CNPJ, redes sociais
 * @modified 1.2.0 - 2026-03-11 - Edição admin frontend, CRUD categorias, upload logotipo, logo condicional
 * @modified 1.2.1 - 2026-03-11 - Descrição condicional por plano, galeria AJAX com conversão WebP, rascunho no Step 1
 * @modified 1.2.2 - 2026-03-11 - Status AJAX na gestão de anúncios, correção upload logo, controle largura logo
 * @modified 1.2.3 - 2026-03-11 - Analytics de visitas, filtro de período no dashboard, gráfico Chart.js, correção fade-out
 * @modified 1.3.0 - 2026-03-11 - Integracao Mercado Pago (PIX + Cartao) e Pagou (PIX), webhook HMAC-SHA256, polling automatico
 * @modified 1.3.1 - 2026-03-11 - Limpeza de imagens orfas, preview logo em tempo real, delete seguro ao substituir/remover
 * @modified 1.3.2 - 2026-03-11 - Fix criacao tabela wp_gcep_plans (dbDelta compat), ensure_table fallback
 * @modified 1.3.3 - 2026-03-11 - Fix botao Publicar visivel no Step 1, validacao plano premium, fluxo pagamento corrigido
 * @modified 1.4.0 - 2026-03-11 - Blog nativo com painel frontend, rota publica /blog e carrossel de postagens recentes
 * @modified 1.4.1 - 2026-03-11 - Fix </div> orfao no Step 1 que quebrava form inteiro, fallback planos vazios
 * @modified 1.4.2 - 2026-03-11 - Submit AJAX com resultado inline, overlay aprovado/rejeitado, botao Pagar no dashboard, fluxo admin premium
 * @modified 1.4.3 - 2026-03-11 - Fix display justificativa de rejeicao no overlay, whitespace-pre-wrap, console.log debug
 * @modified 1.4.4 - 2026-03-11 - Step 1 edicao com plano somente-leitura, modo edicao no JS, redirect erro corrigido
 * @modified 1.4.5 - 2026-03-11 - Validacao IA antes de salvar edicao, validate_from_data sem persistir, overlay resultado na edicao
 * @modified 1.4.6 - 2026-03-11 - Justificativa rejeicao no painel admin, paginacao nas listagens admin e anunciante
 * @modified 1.4.7 - 2026-03-11 - Parser de resposta da IA mais tolerante e fallback visivel da justificativa na edicao
 * @modified 1.4.8 - 2026-03-11 - Contexto separado para editar anuncio em painel do anunciante e painel admin
 * @modified 1.4.9 - 2026-03-11 - Fallback AJAX/inline para sempre exibir a justificativa da IA na edicao
 * @modified 1.5.0 - 2026-03-11 - Responsividade mobile-first completa: dashboards admin/user, frontend publico, menu off-canvas, tabelas adaptativas, filtros colapsaveis
 * @modified 1.5.1 - 2026-03-11 - Hero configuravel (imagem, overlay gradiente, cores, opacidade, direcao), UI sidebar user/admin, footer sticky, botao criar anuncio compacto
 * @modified 1.5.2 - 2026-03-11 - Hardening de visibilidade dos anuncios, cron de expiracao, consultas paginadas/admin otimizadas e reset seguro por e-mail
 * @modified 1.5.3 - 2026-03-11 - Cartao server-side desativado por padrao e analytics com deduplicacao de visualizacoes
 * @modified 1.5.4 - 2026-03-11 - Ocultar avatar single post, views unicas por IP/dia, AdSense admin config e injecao inline
 * @modified 1.5.5 - 2026-03-11 - Upgrade gratis→premium na edicao, plano premium travado na vigencia, redirect pagamento pos-upgrade
 * @modified 1.5.6 - 2026-03-11 - Meta box anuncios relacionados no blog, sidebar sticky, meta no hero, fix copiar link
 * @modified 1.5.7 - 2026-03-11 - Rodape com contatos/redes configuraveis e novos campos de Instagram, Facebook, X e WhatsApp
 * @modified 1.5.8 - 2026-03-11 - Exclusao de anuncios (dono+admin) com cleanup de midias, Zona de Perigo no perfil com codigo email 10min
 * @modified 1.5.9 - 2026-03-11 - Páginas nativas de suporte/termos/privacidade com seed inicial e rodapé sem coluna Negócios
 * @modified 1.6.0 - 2026-03-11 - Admin dashboard com graficos visitas/dia, ranking top 20 anuncios e posts, filtro periodo, dedup IP/dia global
 * @modified 1.7.0 - 2026-03-11 - Mapa de anuncios com Leaflet/OSM, geocodificacao Nominatim no cadastro, pins com modal e contagem de visitas, filtros e geolocalizacao do usuario
 * @modified 1.7.1 - 2026-03-11 - Captcha configuravel nos forms publicos, auth visual alinhado com a home e cadastro simplificado
 * @modified 1.7.2 - 2026-03-11 - Editor rico restrito na descricao premium e geracao de anuncio com IA por contexto
 * @modified 1.7.3 - 2026-03-12 - Fluxo de criacao/edicao de anuncios endurecido, logs de diagnostico, testes de decisao e pagamento admin
 * @modified 1.7.4 - 2026-03-12 - Scripts premium por anúncio com modal no dashboard do anunciante e views em lote na lista
 * @modified 1.7.5 - 2026-03-14 - Ativacao automatica do tema guiawp-reset ao ativar o plugin e restauracao do tema anterior ao desativar
 * @modified 1.7.6 - 2026-03-14 - Tema embutido no plugin, copia/atualiza sempre, guard exists(), permalink structure garantido em WP limpo
 * @modified 1.7.7 - 2026-03-14 - Flush diferido via gcep_needs_flush: CPT e rotas registrados antes do flush, corrige 404 em todas as rotas
 * @modified 1.8.0 - 2026-03-20 - Imagem por categoria com crop quadrado 400x400 WebP 90%, upload AJAX, exibicao no carrossel da home e tabela admin
 * @modified 1.8.1 - 2026-03-20 - Correcao: conflito gcep-crop.js em /categorias; renew() sem datas antecipadas; wp_date() em Expiration; delete_category com cleanup de attachment; confirm_payment desduplicado no WebhookHandler; guard ABSPATH no arquivo de teste; REQUEST_URI sem query string em Assets
 * @modified 1.8.2 - 2026-03-20 - Sistema de notificacoes toast global (gcep-toast.js); substituicao de todos os alert(), showFeedback() e divs gcep_msg por gcepToast() centrado na tela
 * @modified 1.8.3 - 2026-03-20 - taxonomy-gcep_categoria.php com cards identicos ao archive; badge categoria minimalista (borda sutil, fonte 9px, peso medium)
 * @modified 1.8.4 - 2026-03-20 - Hero: icone lupa no botao, somente icone no mobile, categorias ao lado do botao (2 linhas mobile); carrossel de categorias mobile (3.5 cards vistos, fonte compacta); border-radius dos cards 60% menor no mobile
 * @modified 1.8.5 - 2026-03-20 - Filtros e ordenadores na pagina de categoria: busca por titulo, cidade, estado (UF) e sort por titulo/data/visitas com paginacao preservando filtros
 * @modified 1.8.6 - 2026-03-20 - Overflow horizontal corrigido em todos os paineis (body overflow-x:hidden + min-w-0); filtro de categoria sem icones absolutos nos inputs; step 1 criar anuncio mobile (grid-cols-1); cards destaque home minimalistas; blog carousel compacto; tabelas admin com min-w e scroll
 * @modified 1.8.7 - 2026-03-20 - Coluna POST no blog admin mais compacta; UI de anuncios relacionados no formulario blog frontend com busca AJAX; save handler salva meta _gcep_anuncios_relacionados
 * @modified 1.8.8 - 2026-03-21 - Badges de status com cores/borda explicitas, labels abreviados, acoes compactas sem quebra de linha; botao deletar usuario com limpeza completa de anuncios, midias e metas orfas
 * @modified 1.8.9 - 2026-03-21 - Fix acoes duplicadas no dashboard Meus Anuncios, badges de status explicitas, cards categorias home com/sem imagem via CSS grid, sidebar filtros sticky com botao fixo na pagina de anuncios
 * @modified 1.9.0 - 2026-03-21 - Integracao Gemini Imagen 3 para geracao de imagens com IA em categorias e blog, modal reutilizavel com melhoria de prompt via OpenAI/Groq, processamento WebP otimizado
 * @modified 1.9.1 - 2026-03-21 - Overlay hero mais escuro para legibilidade, campo Video de Destaque no blog com player YouTube/Vimeo sobreposto ao hero
 * @modified 1.9.2 - 2026-03-21 - Cards destaques home redesenhados (mesmo layout do archive), prompt IA categorias fullcover sem bordas, remocao Anuncios Recentes do dashboard anunciante, categorias linkadas no single, mapa com coordenadas salvas, botoes compartilhar redesenhados, gap botoes acoes corrigido, pagina /categorias, icone verificado removido, modal conferencia rapida admin anuncios, botoes acao usuarios admin corrigidos
 * @modified 1.9.3 - 2026-03-21 - Exclusao em massa de blog posts com remocao de midias, WP Admin link no sidebar, rewrite completo do plugin GuiaWP Seed v2.0 (batch processing, config, blog, geracao procedural 1000+)
 * @modified 1.9.4 - 2026-03-21 - Redesign modal do mapa: foto de capa + foto perfil sobreposta + titulo + categoria + saiba mais; fix encoding entidades HTML nos titulos; campo capa no AJAX; destaques home: prioridade premium aleatorio, fallback gratis, fix filtro plano gratis vs free
 * @modified 1.9.5 - 2026-03-21 - Auditoria completa: N+1 queries mapa eliminado (5500→~10), transient cache mapa 10min, extract() removido, sanitizacao admin settings por tipo, touch targets 44px+, versao fonts corrigida, race condition seed.js, limites server-side seed config
 * @modified 1.9.7 - 2026-03-21 - 6 steps form (contato/endereco separados), icones SVG redes sociais, sidebar sticky single, modal mapa com endereco e contatos
 * @modified 1.9.8 - 2026-03-21 - 7 steps form (galeria separada de midia), botoes acao padronizados painel usuario, integracao intl-tel-input DDI bandeiras
 * @modified 1.9.9 - 2026-03-22 - Sistema completo de cores dinamicas: 5 CSS variables (primaria, secundaria, destaque, fundo, texto), mapeamento Tailwind→CSS vars, crop CTA, classes group-hover e bg-opacity ausentes
 * @modified 2.0.0 - 2026-03-22 - Novas cores independentes: Cor do Rodape e Cor de Fundo Categorias separadas da secundaria, footer adaptativo claro/escuro, 7 CSS variables totais
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GCEP_VERSION', '2.0.0' );
define( 'GCEP_PLUGIN_FILE', __FILE__ );
define( 'GCEP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GCEP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GCEP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once GCEP_PLUGIN_DIR . 'includes/core/class-loader.php';

function gcep_init() {
	GCEP_Loader::get_instance()->init();
}
add_action( 'plugins_loaded', 'gcep_init' );

function gcep_activate() {
	require_once GCEP_PLUGIN_DIR . 'includes/core/class-activator.php';
	GCEP_Activator::activate();
}
register_activation_hook( __FILE__, 'gcep_activate' );

function gcep_deactivate() {
	require_once GCEP_PLUGIN_DIR . 'includes/theme/class-theme-installer.php';
	GCEP_Theme_Installer::restore_previous_theme();
	require_once GCEP_PLUGIN_DIR . 'includes/expiration/class-expiration.php';
	GCEP_Expiration::clear_scheduled_event();
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'gcep_deactivate' );
