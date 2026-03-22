<?php
/**
 * Template: Index (fallback)
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

get_header();
?>

<main class="max-w-7xl mx-auto px-6 py-12">
	<?php if ( have_posts() ) : ?>
		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
			<?php while ( have_posts() ) : the_post(); ?>
			<article class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden hover:shadow-xl transition-all group">
				<?php if ( has_post_thumbnail() ) : ?>
				<a href="<?php the_permalink(); ?>" class="block h-48 overflow-hidden">
					<?php the_post_thumbnail( 'medium_large', [ 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ] ); ?>
				</a>
				<?php endif; ?>
				<div class="p-6">
					<p class="text-xs text-primary font-bold uppercase tracking-wider mb-2"><?php echo esc_html( get_the_date() ); ?></p>
					<h2 class="text-lg font-bold text-slate-900 group-hover:text-primary transition-colors">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h2>
					<p class="text-sm text-slate-500 mt-2 line-clamp-3"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 25 ) ); ?></p>
				</div>
			</article>
			<?php endwhile; ?>
		</div>

		<div class="mt-12 flex justify-center">
			<?php the_posts_pagination( [
				'mid_size'  => 2,
				'prev_text' => '<span class="material-symbols-outlined">chevron_left</span>',
				'next_text' => '<span class="material-symbols-outlined">chevron_right</span>',
			] ); ?>
		</div>
	<?php else : ?>
		<div class="text-center py-20">
			<span class="material-symbols-outlined text-5xl text-slate-300 mb-4 block">article</span>
			<h2 class="text-xl font-bold text-slate-900 mb-2"><?php esc_html_e( 'Nenhum conteúdo encontrado.', 'guiawp-reset' ); ?></h2>
		</div>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
