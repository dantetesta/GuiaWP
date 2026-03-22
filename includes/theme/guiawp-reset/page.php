<?php
/**
 * Template: Page
 *
 * @package GuiaWP_Reset
 * @author Dante Testa <https://dantetesta.com.br>
 * @since 1.0.0 - 2026-03-11
 */

get_header();
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8 md:py-12">
	<?php while ( have_posts() ) : the_post(); ?>
	<article>
		<h1 class="text-2xl md:text-4xl font-black text-slate-900 tracking-tight mb-6 md:mb-8"><?php the_title(); ?></h1>
		<div class="prose prose-lg max-w-none text-slate-700">
			<?php the_content(); ?>
		</div>
	</article>
	<?php endwhile; ?>
</main>

<?php get_footer(); ?>
