<?php

$title_meta_box = walker_edge_add_meta_box(
    array(
        'scope' => array('page', 'portfolio-item', 'post'),
        'title' => 'Title',
        'name' => 'title_meta'
    )
);

    walker_edge_add_meta_box_field(
        array(
            'name' => 'edgtf_show_title_area_meta',
            'type' => 'select',
            'default_value' => '',
            'label' => 'Show Title Area',
            'description' => 'Disabling this option will turn off page title area',
            'parent' => $title_meta_box,
            'options' => array(
                '' => '',
                'no' => 'No',
                'yes' => 'Yes'
            ),
            'args' => array(
                "dependence" => true,
                "hide" => array(
                    "" => "",
                    "no" => "#edgtf_edgtf_show_title_area_meta_container",
                    "yes" => ""
                ),
                "show" => array(
                    "" => "#edgtf_edgtf_show_title_area_meta_container",
                    "no" => "",
                    "yes" => "#edgtf_edgtf_show_title_area_meta_container"
                )
            )
        )
    );

    $show_title_area_meta_container = walker_edge_add_admin_container(
        array(
            'parent' => $title_meta_box,
            'name' => 'edgtf_show_title_area_meta_container',
            'hidden_property' => 'edgtf_show_title_area_meta',
            'hidden_value' => 'no'
        )
    );

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_title_area_type_meta',
                'type' => 'select',
                'default_value' => '',
                'label' => 'Title Area Type',
                'description' => 'Choose title type',
                'parent' => $show_title_area_meta_container,
                'options' => array(
                    '' => '',
                    'standard' => 'Standard',
                    'breadcrumb' => 'Breadcrumb'
                ),
                'args' => array(
                    "dependence" => true,
                    "hide" => array(
                        "standard" => "",
                        "standard" => "",
                        "breadcrumb" => "#edgtf_edgtf_title_area_type_meta_container"
                    ),
                    "show" => array(
                        "" => "#edgtf_edgtf_title_area_type_meta_container",
                        "standard" => "#edgtf_edgtf_title_area_type_meta_container",
                        "breadcrumb" => ""
                    )
                )
            )
        );

        $title_area_type_meta_container = walker_edge_add_admin_container(
            array(
                'parent' => $show_title_area_meta_container,
                'name' => 'edgtf_title_area_type_meta_container',
                'hidden_property' => 'edgtf_title_area_type_meta',
                'hidden_value' => '',
                'hidden_values' => array('breadcrumb'),
            )
        );

            walker_edge_add_meta_box_field(
                array(
                    'name' => 'edgtf_title_area_enable_breadcrumbs_meta',
                    'type' => 'select',
                    'default_value' => '',
                    'label' => 'Enable Breadcrumbs',
                    'description' => 'This option will display Breadcrumbs in Title Area',
                    'parent' => $title_area_type_meta_container,
                    'options' => array(
                        '' => '',
                        'no' => 'No',
                        'yes' => 'Yes'
                    ),
                )
            );

            walker_edge_add_meta_box_field(
                array(
                    'name' => 'edgtf_title_predefined_size_meta',
                    'type' => 'select',
                    'default_value' => '',
                    'label' => 'Predefined Title Size',
                    'description' => 'Choose Title Predefined size',
                    'parent' => $title_area_type_meta_container,
                    'options' => array(
                        '' => '',
                        'small' => 'Small',
                        'medium' => 'Medium',
                        'large' => 'Large'
                    )
                )
            );

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_title_area_vertial_alignment_meta',
                'type' => 'select',
                'default_value' => '',
                'label' => 'Vertical Alignment',
                'description' => 'Specify title vertical alignment',
                'parent' => $show_title_area_meta_container,
                'options' => array(
                    '' => '',
                    'header_bottom' => 'From Bottom of Header',
                    'window_top' => 'From Window Top'
                )
            )
        );

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_title_area_content_alignment_meta',
                'type' => 'select',
                'default_value' => '',
                'label' => 'Horizontal Alignment',
                'description' => 'Specify title horizontal alignment',
                'parent' => $show_title_area_meta_container,
                'options' => array(
                    '' => '',
                    'left' => 'Left',
                    'center' => 'Center',
                    'right' => 'Right'
                )
            )
        );

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_title_text_color_meta',
                'type' => 'color',
                'label' => 'Title Color',
                'description' => 'Choose a color for title text',
                'parent' => $show_title_area_meta_container
            )
        );

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_title_breadcrumb_color_meta',
                'type' => 'color',
                'label' => 'Breadcrumb Color',
                'description' => 'Choose a color for breadcrumb text',
                'parent' => $show_title_area_meta_container
            )
        );

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_title_area_background_color_meta',
                'type' => 'color',
                'label' => 'Background Color',
                'description' => 'Choose a background color for Title Area',
                'parent' => $show_title_area_meta_container
            )
        );

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_hide_background_image_meta',
                'type' => 'yesno',
                'default_value' => 'no',
                'label' => 'Hide Background Image',
                'description' => 'Enable this option to hide background image in Title Area',
                'parent' => $show_title_area_meta_container,
                'args' => array(
                    "dependence" => true,
                    "dependence_hide_on_yes" => "#edgtf_edgtf_hide_background_image_meta_container",
                    "dependence_show_on_yes" => ""
                )
            )
        );

        $hide_background_image_meta_container = walker_edge_add_admin_container(
            array(
                'parent' => $show_title_area_meta_container,
                'name' => 'edgtf_hide_background_image_meta_container',
                'hidden_property' => 'edgtf_hide_background_image_meta',
                'hidden_value' => 'yes'
            )
        );

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_title_area_background_image_meta',
                'type' => 'image',
                'label' => 'Background Image',
                'description' => 'Choose an Image for Title Area',
                'parent' => $hide_background_image_meta_container
            )
        );

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_title_area_background_image_responsive_meta',
                'type' => 'select',
                'default_value' => '',
                'label' => 'Background Responsive Image',
                'description' => 'Enabling this option will make Title background image responsive',
                'parent' => $hide_background_image_meta_container,
                'options' => array(
                    '' => '',
                    'no' => 'No',
                    'yes' => 'Yes'
                ),
                'args' => array(
                    "dependence" => true,
                    "hide" => array(
                        "" => "",
                        "no" => "",
                        "yes" => "#edgtf_edgtf_title_area_background_image_responsive_meta_container, #edgtf_edgtf_title_area_height_meta"
                    ),
                    "show" => array(
                        "" => "#edgtf_edgtf_title_area_background_image_responsive_meta_container, #edgtf_edgtf_title_area_height_meta",
                        "no" => "#edgtf_edgtf_title_area_background_image_responsive_meta_container, #edgtf_edgtf_title_area_height_meta",
                        "yes" => ""
                    )
                )
            )
        );

        $title_area_background_image_responsive_meta_container = walker_edge_add_admin_container(
            array(
                'parent' => $hide_background_image_meta_container,
                'name' => 'edgtf_title_area_background_image_responsive_meta_container',
                'hidden_property' => 'edgtf_title_area_background_image_responsive_meta',
                'hidden_value' => 'yes'
            )
        );

            walker_edge_add_meta_box_field(
                array(
                    'name' => 'edgtf_title_area_background_image_parallax_meta',
                    'type' => 'select',
                    'default_value' => '',
                    'label' => 'Background Image in Parallax',
                    'description' => 'Enabling this option will make Title background image parallax',
                    'parent' => $title_area_background_image_responsive_meta_container,
                    'options' => array(
                        '' => '',
                        'no' => 'No',
                        'yes' => 'Yes',
                        'yes_zoom' => 'Yes, with zoom out'
                    )
                )
            );

        walker_edge_add_meta_box_field(array(
            'name' => 'edgtf_title_area_height_meta',
            'type' => 'text',
            'label' => 'Height',
            'description' => 'Set a height for Title Area',
            'parent' => $show_title_area_meta_container,
            'args' => array(
                'col_width' => 2,
                'suffix' => 'px'
            )
        ));

        walker_edge_add_meta_box_field(array(
            'name' => 'edgtf_title_area_subtitle_meta',
            'type' => 'text',
            'default_value' => '',
            'label' => 'Subtitle Text',
            'description' => 'Enter your subtitle text',
            'parent' => $show_title_area_meta_container,
            'args' => array(
                'col_width' => 6
            )
        ));

        walker_edge_add_meta_box_field(
            array(
                'name' => 'edgtf_subtitle_color_meta',
                'type' => 'color',
                'label' => 'Subtitle Color',
                'description' => 'Choose a color for subtitle text',
                'parent' => $show_title_area_meta_container
            )
        );