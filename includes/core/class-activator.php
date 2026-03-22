<?php
/**
 * Ativador do plugin - cria roles, páginas e flush rewrite
 *
 * @package GuiaWP
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 * @modified 1.7.5 - 2026-03-14 - install_theme ativa automaticamente o tema apos copiar os arquivos
 * @modified 1.7.6 - 2026-03-14 - install_theme sempre copia/atualiza o tema, guard de existencia, permalink structure garantido em WP limpo
 * @modified 1.7.7 - 2026-03-14 - flush diferido via gcep_needs_flush: garante que CPT e rotas estejam registrados antes do flush
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GCEP_Activator {

	public static function activate(): void {
		self::sync_installation();
	}

	public static function sync_installation(): void {
		self::create_roles();
		self::create_pages();
		self::create_analytics_table();
		self::create_blog_views_table();
		self::create_plans_table();
		self::schedule_expiration();
		self::sync_anuncio_visibility();
		self::install_theme();
		self::ensure_permalink_structure();

		update_option( 'gcep_needs_flush', true );
		update_option( 'gcep_plugin_version', GCEP_VERSION );
	}

	private static function create_analytics_table(): void {
		require_once GCEP_PLUGIN_DIR . 'includes/analytics/class-analytics.php';
		GCEP_Analytics::create_table();
	}

	private static function create_blog_views_table(): void {
		require_once GCEP_PLUGIN_DIR . 'includes/analytics/class-analytics.php';
		GCEP_Analytics::create_blog_views_table();
	}

	private static function create_plans_table(): void {
		require_once GCEP_PLUGIN_DIR . 'includes/plans/class-plans.php';
		GCEP_Plans::create_table();
	}

	private static function schedule_expiration(): void {
		require_once GCEP_PLUGIN_DIR . 'includes/expiration/class-expiration.php';
		GCEP_Expiration::schedule_event();
	}

	private static function sync_anuncio_visibility(): void {
		require_once GCEP_PLUGIN_DIR . 'includes/helpers/class-helpers.php';
		GCEP_Helpers::sync_existing_anuncio_post_statuses();
	}

	private static function create_roles(): void {
		remove_role( 'gcep_anunciante' );
		add_role( 'gcep_anunciante', __( 'Anunciante', 'guiawp' ), [
			'read'         => true,
			'upload_files' => true,
		] );
	}

	private static function create_pages(): void {
		$pages = [
			[
				'slug'    => 'cadastro',
				'title'   => __( 'Cadastro', 'guiawp' ),
				'content' => '',
			],
			[
				'slug'    => 'login',
				'title'   => __( 'Entrar', 'guiawp' ),
				'content' => '',
			],
			[
				'slug'    => 'painel',
				'title'   => __( 'Painel', 'guiawp' ),
				'content' => '',
			],
			[
				'slug'    => 'painel-admin',
				'title'   => __( 'Painel Admin', 'guiawp' ),
				'content' => '',
			],
			[
				'slug'    => 'central-de-ajuda',
				'title'   => __( 'Central de Ajuda', 'guiawp' ),
				'content' => self::get_help_page_content(),
			],
			[
				'slug'    => 'termos-de-uso',
				'title'   => __( 'Termos de Uso', 'guiawp' ),
				'content' => self::get_terms_page_content(),
			],
			[
				'slug'    => 'privacidade',
				'title'   => __( 'Privacidade', 'guiawp' ),
				'content' => self::get_privacy_page_content(),
			],
		];

		foreach ( $pages as $page ) {
			if ( get_page_by_path( $page['slug'], OBJECT, 'page' ) ) {
				continue;
			}

			wp_insert_post( [
				'post_title'   => $page['title'],
				'post_name'    => $page['slug'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => wp_slash( $page['content'] ),
			] );
		}
	}

	private static function get_help_page_content(): string {
		return <<<'HTML'
<!-- wp:paragraph -->
<p><strong>Modelo inicial:</strong> este conteúdo foi criado automaticamente pelo GuiaWP para servir como ponto de partida. Revise e adapte com as regras, fluxos e canais oficiais da sua operação.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Como funciona o GuiaWP</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>O GuiaWP é uma plataforma de descoberta de empresas, profissionais e conteúdos locais. Usuários podem explorar anúncios, acessar informações de contato, consumir conteúdo do blog e interagir com negócios cadastrados na plataforma.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>1. Cadastro e acesso</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>O anunciante cria sua conta pela página de cadastro.</li><li>Após o login, o usuário acessa seu painel para criar, editar e acompanhar anúncios.</li><li>Algumas áreas podem ser restritas conforme o perfil e o tipo de conta.</li></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>2. Publicação de anúncios</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>O anunciante preenche os dados do negócio, contatos, mídias, localização e redes sociais.</li><li>O conteúdo pode passar por validação automática e/ou revisão administrativa antes da publicação.</li><li>Anúncios que não atendam às políticas da plataforma podem ser rejeitados, pausados ou removidos.</li></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>3. Planos e cobrança</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>A plataforma pode oferecer planos gratuitos e premium, com recursos e prazos diferentes.</li><li>Anúncios premium podem depender de aprovação e confirmação de pagamento.</li><li>Datas de vigência, renovação e benefícios variam conforme a configuração ativa do guia.</li></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>4. Motivos de rejeição mais comuns</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Informações incompletas, inconsistentes ou enganosas.</li><li>Uso de conteúdo impróprio, promocional em excesso ou fora do escopo da plataforma.</li><li>Imagens, vídeos ou descrições que não representem corretamente o negócio anunciado.</li><li>Descumprimento das políticas editoriais ou comerciais do guia.</li></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>5. Precisa de ajuda?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Se você precisar de suporte, utilize os canais oficiais informados no rodapé do site. Recomendamos revisar também os <a href="/termos-de-uso/">Termos de Uso</a> e a página de <a href="/privacidade/">Privacidade</a>.</p>
<!-- /wp:paragraph -->
HTML;
	}

	private static function get_terms_page_content(): string {
		return <<<'HTML'
<!-- wp:paragraph -->
<p><strong>Modelo inicial:</strong> este texto é apenas um ponto de partida. Ele deve ser revisado por responsável jurídico antes da publicação definitiva.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>1. Aceitação dos termos</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Ao acessar ou utilizar o GuiaWP, o usuário declara estar ciente e de acordo com estes Termos de Uso, bem como com as demais políticas publicadas na plataforma.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>2. Objeto da plataforma</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>O GuiaWP disponibiliza um ambiente digital para divulgação de empresas, profissionais, serviços e conteúdos editoriais, podendo oferecer áreas públicas, áreas restritas e recursos pagos.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>3. Responsabilidades do anunciante</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Fornecer informações verdadeiras, completas e atualizadas.</li><li>Manter a titularidade ou autorização de uso de imagens, marcas, vídeos e demais conteúdos enviados.</li><li>Não publicar conteúdo ilícito, ofensivo, enganoso ou que viole direitos de terceiros.</li><li>Responder pela regularidade de sua atividade comercial e pelos contatos divulgados no anúncio.</li></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2>4. Moderação e publicação</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>O GuiaWP poderá revisar, aprovar, rejeitar, suspender, editar ou remover anúncios e conteúdos que contrariem estes termos, políticas internas, exigências técnicas ou obrigações legais.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>5. Planos, cobrança e vigência</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Os recursos, preços, benefícios e prazos dos planos podem variar conforme a configuração comercial ativa no momento da contratação. Pagamentos, renovações e liberações podem depender de confirmação operacional e antifraude.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>6. Propriedade intelectual</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Os elementos visuais, marca, estrutura, tecnologia e conteúdos próprios do GuiaWP permanecem protegidos por legislação aplicável. O uso indevido da plataforma ou de seus materiais poderá resultar em restrições de acesso e medidas legais cabíveis.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>7. Limitação de responsabilidade</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>O GuiaWP atua como plataforma de intermediação e divulgação, não se responsabilizando diretamente por negociações, atendimentos, produtos ou serviços prestados por anunciantes cadastrados, salvo quando houver obrigação legal específica.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>8. Atualizações destes termos</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Estes Termos de Uso podem ser alterados a qualquer momento para refletir melhorias de produto, mudanças legais ou ajustes operacionais. A versão vigente será sempre a publicada nesta página.</p>
<!-- /wp:paragraph -->
HTML;
	}

	private static function get_privacy_page_content(): string {
		return <<<'HTML'
<!-- wp:paragraph -->
<p><strong>Modelo inicial:</strong> este conteúdo deve ser revisado e complementado de acordo com a realidade da operação, bases legais aplicáveis e ferramentas efetivamente utilizadas no site.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>1. Quais dados podem ser coletados</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Dados cadastrais informados por usuários e anunciantes, como nome, e-mail, telefone e informações de acesso.</li><li>Dados de anúncios publicados, mídias enviadas, localização e canais de contato comerciais.</li><li>Informações técnicas de navegação, métricas de uso e registros operacionais necessários para funcionamento, segurança e melhoria da plataforma.</li></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2>2. Finalidades do tratamento</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Permitir cadastro, autenticação e uso das áreas restritas.</li><li>Exibir anúncios, conteúdos e páginas públicas da plataforma.</li><li>Executar validações, moderação, cobrança, suporte e comunicações operacionais.</li><li>Melhorar desempenho, segurança, experiência do usuário e análise de uso da plataforma.</li></ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2>3. Compartilhamento de dados</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Os dados podem ser compartilhados com provedores de hospedagem, gateways de pagamento, serviços de e-mail, ferramentas analíticas e outros operadores estritamente necessários ao funcionamento do GuiaWP, sempre dentro das finalidades legítimas da operação.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>4. Conteúdos públicos</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Dados inseridos em anúncios públicos, como telefone comercial, endereço, site, redes sociais e materiais promocionais, poderão ser exibidos aos visitantes conforme a configuração do anúncio e das políticas da plataforma.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>5. Armazenamento e segurança</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>O GuiaWP adota medidas técnicas e organizacionais razoáveis para proteger as informações tratadas. Ainda assim, nenhum ambiente digital é totalmente imune a falhas, sendo importante que usuários utilizem senhas seguras e mantenham seus dados atualizados.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>6. Direitos do titular</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Dependendo da legislação aplicável, o titular poderá solicitar confirmação de tratamento, acesso, correção, atualização ou exclusão de dados, observadas as obrigações legais e regulatórias de retenção.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>7. Cookies e tecnologias semelhantes</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>A plataforma pode utilizar cookies e tecnologias equivalentes para login, segurança, preferências, medição de desempenho e experiência de navegação. Recomenda-se detalhar aqui quais categorias de cookies são efetivamente usadas.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>8. Atualizações desta política</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Esta Política de Privacidade poderá ser atualizada periodicamente para refletir mudanças legais, operacionais ou tecnológicas. A versão válida será sempre a publicada nesta página.</p>
<!-- /wp:paragraph -->
HTML;
	}

	private static function install_theme(): void {
		$source = GCEP_PLUGIN_DIR . 'includes/theme/guiawp-reset';
		$dest   = get_theme_root() . '/guiawp-reset';

		if ( is_dir( $source ) ) {
			self::copy_dir( $source, $dest );
			delete_transient( 'theme_roots' );
		}

		require_once GCEP_PLUGIN_DIR . 'includes/theme/class-theme-installer.php';

		if ( wp_get_theme( 'guiawp-reset' )->exists() ) {
			GCEP_Theme_Installer::activate();
		}
	}

	private static function ensure_permalink_structure(): void {
		if ( '' !== get_option( 'permalink_structure' ) ) {
			return;
		}

		update_option( 'permalink_structure', '/%postname%/' );

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules( true );
	}

	private static function copy_dir( string $src, string $dst ): void {
		$dir = opendir( $src );
		if ( ! is_dir( $dst ) ) {
			wp_mkdir_p( $dst );
		}
		while ( false !== ( $file = readdir( $dir ) ) ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}
			if ( is_dir( $src . '/' . $file ) ) {
				self::copy_dir( $src . '/' . $file, $dst . '/' . $file );
			} else {
				copy( $src . '/' . $file, $dst . '/' . $file );
			}
		}
		closedir( $dir );
	}
}
