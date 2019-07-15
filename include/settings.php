<?php
class WC_Settings_Tab_WPSBR {
	function __construct() {
		$this->id = 'wpsbr';
		$this->init();
    }
	/*
	* init function
	*/
    function init() {
        add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50 );
        add_action( 'woocommerce_settings_tabs_'.$this->id, array($this, 'settings_tab') );
        add_action( 'woocommerce_update_options_'.$this->id, array($this, 'update_settings') );
		add_action('admin_footer', array( $this, 'admin_script') );
    }


    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
	function add_settings_tab( $settings_tabs ) {
        $settings_tabs['wpsbr'] = __( 'WPSBR Settings', 'wpsbr-settings' );
        return $settings_tabs;
    }
    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
	function settings_tab() {
        woocommerce_admin_fields( $this->get_settings() );
    }
    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
	function update_settings() {
        woocommerce_update_options( $this->get_settings() );
    }
    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
	function get_settings() {
        $settings = array(
            'section_title' => array(
                'name'     => __( 'Products Visibility Based On State using WooCommerce Geolocation', 'woo-product-state-base-restrictions' ),
                'type'     => 'title',
                'desc'     => '',
            ),
			'product_visibility' => array(
				'title'		=> __( 'Product Visibility', 'woo-product-state-base-restrictions' ),
				'type'		=> 'radio',
				'default'	=> 'hide_catalog_visibility',
				'id'		=> 'product_visibility',
				'options'	=> array(
					'hide_catalog_visibility' => __( 'Hide catalog visibility', 'woo-product-state-base-restrictions' )." \n      ".__('This will hide selected products in shop and search results. However product still will be accessible via direct link.', 'woo-product-state-base-restrictions'),
					'hide_completely' => __( 'Hide completely', 'woo-product-state-base-restrictions' )." \n      ".__( 'This will hide selected products completely (including direct link).', 'woo-product-state-base-restrictions' ),
				),
				'class'		=> 'product_visibility',
			),
			'section_end1' => array(
                 'type'		=> 'sectionend',
                 'id'		=> 'wc_settings_tab_demo_section_end1'
            ),
			'section_title1' => array(
                'name'     => __( 'WooCommerce Product State Base Restrictions', 'woo-product-state-base-restrictions' ),
                'type'     => 'title',
                'desc'     => '',
				'class'		=> 'temp'
            ),
			'make_non_purchasable' => array(
				'title'		=> __( 'Make non-purchasable', 'woo-product-state-base-restrictions' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
				'id'		=> 'wpsbr_make_non_purchasable',
				'label'		=> 'Enable plugin',
				'desc'		=> __( "This will make selected products non-purchasable (i.e. product can't be added to the cart).", 'woo-product-state-base-restrictions' ),
			),
			'wpsbr_default_message' => array(
				'title'		=> __( 'Default message', 'woo-product-state-base-restrictions' ),
				'desc_tip'	=> __( "This message will show on product page when product is not purchasable. Default message : Sorry, this product is not available in your state.", 'woo-product-state-base-restrictions' ),
				'type'		=> 'textarea',
				'id'		=> 'wpsbr_default_message',
			),
			'wpsbr_message_position' => array(
				'title'		=> __( 'Message Position', 'woo-product-state-base-restrictions' ),
				'desc'		=> __( "Default : After add to cart", 'woo-product-state-base-restrictions'),
				'desc_tip'	=> __( "This message will show on product page when product is not purchasable.", 'woo-product-state-base-restrictions' ),
				'type'		=> 'select',
				'id'		=> 'wpsbr_message_position',
				'default'	=> '33',
				'options'	=> array(
					'3'			=> __( 'Before title', 'woo-product-state-base-restrictions' ),
					'8'			=> __( 'After title', 'woo-product-state-base-restrictions' ),
					'13'		=> __( 'After price', 'woo-product-state-base-restrictions' ),
					'23'		=> __( 'After short description', 'woo-product-state-base-restrictions' ),
					'33'		=> __( 'After add to cart', 'woo-product-state-base-restrictions' ),
					'43'		=> __( 'After meta', 'woo-product-state-base-restrictions' ),
					'53'		=> __( 'After sharing', 'woo-product-state-base-restrictions' ),
				)
			),
            'section_end2' => array(
                 'type'		=> 'sectionend',
                 'id'		=> 'wc_settings_tab_demo_section_end'
            )
        );
        return apply_filters( 'wc_'.$this->id.'_settings', $settings );
    }

	function admin_script() {?>
		<script>
			jQuery(".product_visibility").parent('label').addClass("label_product_visibility").parents("table.form-table").addClass("table_product_visibility");
			jQuery("#wpsbr_make_non_purchasable").parents("table.form-table").addClass("table_non_purchasable");
			jQuery(document).on("change", ".product_visibility", function(){
				if( jQuery(this).val() == 'hide_catalog_visibility' ){
					jQuery(".table_non_purchasable").show();
				} else {
					jQuery(".table_non_purchasable").hide();
				}
			});
			jQuery(".product_visibility:checked").trigger("change");
        </script>
        <style>
			.label_product_visibility {white-space: pre;}
			.table_product_visibility + h2 {display: none;}
			.table_non_purchasable .description {display: block;}
		</style>
	<?php }
}
new WC_Settings_Tab_WPSBR();
