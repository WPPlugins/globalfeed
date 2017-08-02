<?php
/**
 * The template for displaying posts in the Image Post Format on index and archive pages
 *
 * Learn more: http://codex.wordpress.org/Post_Formats
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

global $mb_globalfeed;
?>
<article id="post-<?php the_ID(); ?>" class="mbgf-fb_article image">
        <?php if ( $mb_globalfeed->theme_show_avatar() ) { ?>
        <div class="mbgf-fb_avatar"><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'twentyeleven' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php echo get_avatar( get_the_author_meta( 'ID' ), apply_filters( 'twentyeleven_status_avatar', '50' ) ); ?></a></div>
        <?php } ?>
        <div class="mbgf-article-body<?php echo ($mb_globalfeed->theme_show_avatar() ? '' : ' no-avatar') ?>">
        <header class="mbgf-entry-header">
                <h1 class="mbgf-entry-title"><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'twentyeleven' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a></h1>
        </header><!-- .entry-header -->
        
        <?php if ( is_search() ) : // Only display Excerpts for Search ?>
        <div class="mbgf-entry-summary">
                <?php the_excerpt(); ?>
        </div><!-- .entry-summary -->
        <?php else : ?>
        <div class="mbgf-entry-content">
                <?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'twentyeleven' ) ); ?>
        </div><!-- .entry-content -->
        <?php endif; ?>

        <footer class="mbgf-entry-meta">
            <?php do_action('mbgf-fb-theme_show_user_actions'); ?>
            <a href="<?php the_permalink(); ?>" title="<?php echo get_the_date() . __(' at ', 'mb_globalfeed') . get_the_time() ?>" class="relative_time time"><?php echo $mb_globalfeed->formatRelativeDate(get_the_date() . ' ' . get_the_time()); ?></a>
        </footer><!-- #entry-meta -->
        </div>
</article><!-- #post-<?php the_ID(); ?> -->
