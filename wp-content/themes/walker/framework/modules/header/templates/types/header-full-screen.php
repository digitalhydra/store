<?php do_action('walker_edge_before_page_header'); ?>

<header class="edgtf-page-header" <?php walker_edge_inline_style($menu_area_border_bottom_color); ?>>
    <div class="edgtf-menu-area" <?php walker_edge_inline_style($menu_area_background_color); ?>>
		<?php do_action( 'walker_edge_after_header_menu_area_html_open' )?>
        <?php if($full_screen_header_in_grid) : ?>
            <div class="edgtf-grid">
        <?php endif; ?>
        <div class="edgtf-vertical-align-containers">
            <div class="edgtf-position-left">
                <div class="edgtf-position-left-inner">
                    <?php if(!$hide_logo) {
                        walker_edge_get_logo();
                    } ?>
                </div>
            </div>
            <div class="edgtf-position-right">
                <div class="edgtf-position-right-inner">
                    <?php if(is_active_sidebar('edgtf-header-full-screen-widget-area')) : ?>
                        <?php dynamic_sidebar('edgtf-header-full-screen-widget-area'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if($full_screen_header_in_grid) : ?>
            </div>
        <?php endif; ?>
    </div>
    <?php do_action('walker_edge_end_of_page_header_html'); ?>
</header>

<?php do_action('walker_edge_after_page_header'); ?>