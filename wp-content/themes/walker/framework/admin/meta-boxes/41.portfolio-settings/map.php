<?php

if(!function_exists('walker_edge_map_portfolio_settings')) {
    function walker_edge_map_portfolio_settings() {
        $meta_box = walker_edge_add_meta_box(array(
            'scope' => 'portfolio-item',
            'title' => 'Portfolio Settings',
            'name'  => 'portfolio_settings_meta_box'
        ));

        walker_edge_add_meta_box_field(array(
            'name'        => 'edgtf_portfolio_single_template_meta',
            'type'        => 'select',
            'label'       => 'Portfolio Type',
            'description' => 'Choose a default type for Single Project pages',
            'parent'      => $meta_box,
            'options'     => array(
                ''                  => 'Default',
                'small-images'      => 'Portfolio small images',
                'small-slider'      => 'Portfolio small slider',
                'big-images'        => 'Portfolio big images',
                'big-slider'        => 'Portfolio big slider',
                'custom'            => 'Portfolio custom',
                'full-width-custom' => 'Portfolio full width custom',
                'gallery'           => 'Portfolio gallery'
            )
        ));

        $all_pages = array();
        $pages     = get_pages();
        foreach($pages as $page) {
            $all_pages[$page->ID] = $page->post_title;
        }

        walker_edge_add_meta_box_field(array(
            'name'        => 'portfolio_single_back_to_link',
            'type'        => 'select',
            'label'       => '"Back To" Link',
            'description' => 'Choose "Back To" page to link from portfolio Single Project page',
            'parent'      => $meta_box,
            'options'     => $all_pages
        ));

        walker_edge_add_meta_box_field(array(
            'name'        => 'portfolio_external_link',
            'type'        => 'text',
            'label'       => 'Portfolio External Link',
            'description' => 'Enter URL to link from Portfolio List page',
            'parent'      => $meta_box,
            'args'        => array(
                'col_width' => 3
            )
        ));
    }

    add_action('walker_edge_meta_boxes_map', 'walker_edge_map_portfolio_settings');
}