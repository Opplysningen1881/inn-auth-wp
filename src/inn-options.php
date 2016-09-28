<?php
defined( 'ABSPATH' ) or die( '3 No script kiddies please!' );

class inn_SettingsPage {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Innstillinger for INN-auth', 
            'INN-auth', 
            'manage_options', 
            'inn-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }
	
	/**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'inn-auth_options' );
        ?>
        <div class="wrap">
            <h2>Innstillinger for INN-auth</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'inn_option_group' );   
                do_settings_sections( 'inn-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }
	
	/**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'inn_option_group', // Option group
            'inn-auth_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'inn_section_id', // ID
            'Innstillinger for INN-auth', // Title
            array( $this, 'print_section_info' ), // Callback
            'inn-setting-admin' // Page
        );

        add_settings_field(
            'sso_url', // ID
            'SSO URL', // Title
            array( $this, 'sso_url_callback' ), // Callback
            'inn-setting-admin', // Page
            'inn_section_id' // Section
        );

        add_settings_field(
            'app_id',
            'APP_ID',
            array( $this, 'app_id_callback' ),
            'inn-setting-admin',
            'inn_section_id'
        );

        add_settings_field(
            'app_name',
            'APP_NAME',
            array( $this, 'app_name_callback' ),
            'inn-setting-admin',
            'inn_section_id'
        );
		
        add_settings_field(
            'app_secret',
            'APP_SECRET',
            array( $this, 'app_secret_callback' ),
            'inn-setting-admin',
            'inn_section_id'
        );
		
		add_settings_field(
            'app_url',
            'Application URL',
            array( $this, 'app_url_callback' ),
            'inn-setting-admin',
            'inn_section_id'
        );
		
		add_settings_field(
            'app_defcon',
            'Minimum Security Level',
            array( $this, 'app_defcon_callback' ),
            'inn-setting-admin',
            'inn_section_id'
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['sso_url'] ) )
            $new_input['sso_url'] = sanitize_text_field( $input['sso_url'] );

        if( isset( $input['app_id'] ) )
            $new_input['app_id'] = sanitize_text_field( $input['app_id'] );

		if( isset( $input['app_name'] ) )
            $new_input['app_name'] = sanitize_text_field( $input['app_name'] );

        if( isset( $input['app_secret'] ) )
            $new_input['app_secret'] = sanitize_text_field( $input['app_secret'] );

        if( isset( $input['app_url'] ) )
            $new_input['app_url'] = sanitize_text_field( $input['app_url'] );
		
        if( isset( $input['app_defcon'] ) )
            $new_input['app_defcon'] = sanitize_text_field( $input['app_defcon'] );
		
        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Legg inn konfigurasjonsdetaljer som mottatt fra Opplysningen:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function sso_url_callback()
    {
        printf(
            '<input type="text" id="sso_url" name="inn-auth_options[sso_url]" value="%s" />',
            isset( $this->options['sso_url'] ) ? esc_attr( $this->options['sso_url']) : ''
        );
    }

    public function app_id_callback()
    {
        printf(
            '<input type="text" id="app_id" name="inn-auth_options[app_id]" value="%s" />',
            isset( $this->options['app_id'] ) ? esc_attr( $this->options['app_id']) : ''
        );
    }
	
	public function app_name_callback()
    {
        printf(
            '<input type="text" id="app_name" name="inn-auth_options[app_name]" value="%s" />',
            isset( $this->options['app_name'] ) ? esc_attr( $this->options['app_name']) : ''
        );
    }
	
	public function app_secret_callback()
    {
        printf(
            '<input type="text" id="app_secret" name="inn-auth_options[app_secret]" value="%s" />',
            isset( $this->options['app_secret'] ) ? esc_attr( $this->options['app_secret']) : ''
        );
    }

	public function app_url_callback()
    {
        printf(
            '<input type="text" id="app_url" name="inn-auth_options[app_url]" value="%s" />',
            isset( $this->options['app_url'] ) ? esc_attr( $this->options['app_url']) : ''
        );
    }
	
	public function app_defcon_callback()
    {
        printf(
            '<input type="text" id="app_defcon" name="inn-auth_options[app_defcon]" value="%s" />',
            isset( $this->options['app_defcon'] ) ? esc_attr( $this->options['app_defcon']) : ''
        );
    }
}

if( is_admin() )
    $inn_settings_page = new inn_SettingsPage();
