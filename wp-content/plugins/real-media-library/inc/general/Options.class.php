<?php
/**
 * This class handles all hooks for the options.
 * 
 * If you want to extend the options for your plugin
 * please use the RML/Options/Register action. There are no
 * parameters. The settings section headline must start with
 * RealMediaLibrary:* (also in translation). The *-value will be
 * added as navigation label.
 * 
 * @author MatthiasWeb
 * @package real-media-library\inc\attachment
 * @since 1.0
 * @singleton
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class RML_Options {
    private static $me = null;
    
    private function __construct() {
            
    }

    public function register_fields() {
        add_settings_section(
        	'rml_options_general',
        	__('RealMediaLibrary:General'),
        	array($this, 'empty_callback'),
        	'media'
        );
        
        register_setting( 'media', 'rml_hide_upload_preview', 'esc_attr' );
        add_settings_field(
            'rml_hide_upload_preview',
            '<label for="rml_hide_upload_preview">'.__('Hide upload preview' , RML_TD ).'</label>' ,
            array($this, 'html_hide_upload_preview'),
            'media',
            'rml_options_general'
        );
        
        register_setting( 'media', 'rml_all_folders_gallery', 'esc_attr' );
        add_settings_field(
            'rml_all_folders_gallery',
            '<label for="rml_all_folders_gallery">'.__('Allow all folders for folder gallery' , RML_TD ).'</label>' ,
            array($this, 'html_rml_all_folders_gallery'),
            'media',
            'rml_options_general'
        );
        
        register_setting( 'media', 'rml_load_frontend', 'esc_attr' );
        add_settings_field(
            'rml_load_frontend',
            '<label for="rml_load_frontend">'.__('Load RML functionality in frontend' , RML_TD ).'</label>' ,
            array($this, 'html_rml_load_frontend'),
            'media',
            'rml_options_general'
        );
        
        register_setting( 'media', 'rml_hide_info_links', 'esc_attr' );
        add_settings_field(
            'rml_hide_info_links',
            '<label for="rml_hide_info_links">'.__('Hide info links' , RML_TD ).'</label>' ,
            array($this, 'html_hide_info_links'),
            'media',
            'rml_options_general'
        );
        
        // Other plugins / extensions
        do_action("RML/Options/Register");
        
        // Reset
        add_settings_section(
        	'rml_options_reset',
        	__('RealMediaLibrary:Reset'),
        	array($this, 'empty_callback'),
        	'media'
        );
        
        add_settings_field(
            'rml_button_wipe',
            '<label for="rml_button_wipe">'.__('Wipe all settings (folders, attachment relations)' , RML_TD ).'</label>' ,
            array($this, 'html_rml_button_wipe'),
            'media',
            'rml_options_reset'
        );
    }
    
    function empty_callback( $arg ) {
    }
    
    public function html_rml_button_wipe() {
        // Check if reinstall the database tables
        if (isset($_GET["rml_install"])) {
            echo "DB Update was executed<br /><br />";
            rml_install(true);
            echo "<br /><br />";
        }
        
        echo '<button class="rml-button-wipe button" data-nonce-key="wipe" data-action="rml_wipe" data-method="rel">' . __('Wipe attachment relations', RML_TD) . '</button>
        <button class="rml-button-wipe button button-primary" data-nonce-key="wipe" data-action="rml_wipe" data-method="all">' . __('Wipe all', RML_TD) . '</button>';
    }
    
    public function html_rml_all_folders_gallery() {
        $value = get_option( 'rml_all_folders_gallery', '' );
        echo '<input type="checkbox" id="rml_all_folders_gallery"
                name="rml_all_folders_gallery" value="1" ' . checked(1, $value, false) . ' />';
    }
    
    public function html_hide_upload_preview() {
        $value = get_option( 'rml_hide_upload_preview', '' );
        echo '<input type="checkbox" id="rml_hide_upload_preview"
                name="rml_hide_upload_preview" value="1" ' . checked(1, $value, false) . ' />
                <label>' . __('Check this if your uploader does not work properly.', RML_TD) . '</label>';
    }
    
    public function html_hide_info_links() {
        $value = get_option( 'rml_hide_info_links', '' );
        echo '<input type="checkbox" id="rml_hide_info_links"
                name="rml_hide_info_links" value="1" ' . checked(1, $value, false) . ' />
                <label>' . __('Links on the sidebar (Version, Tips, ...)', RML_TD) . '</label>';
    }
    
    public function html_rml_load_frontend() {
        $value = get_option( 'rml_load_frontend', '1' );
        echo '<input type="checkbox" id="rml_load_frontend"
                name="rml_load_frontend" value="1" ' . checked(1, $value, false) . ' />
                <label>' . __('If you are using a front end page builder, for example Visual Composer', RML_TD) . '</label>';
    }
    
    /**
     * Getter for options
     */
    public static function load_frontend() {
        return get_option( 'rml_load_frontend', '1' ) === '1';
    }
    
    public static function getInstance() {
        if (self::$me == null) {
                self::$me = new RML_Options();
        }
        return self::$me;
    }
}

?>