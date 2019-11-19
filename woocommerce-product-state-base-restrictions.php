<?php
/*
* Plugin Name: WooCommerce Visibility by State using Geolocation
* Description: Restrict WooCommerce products in specific states of Brazil
* Author: Marcos Rezende
* Author URI: https://www.linkedin.com/in/rezendemarcos/
* Version: 1.0
* Text Domain: woo-product-state-base-restrictions
* WC requires at least: 3.0
* WC tested up to: 3.6.1
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include 'include/settings.php';

class Product_State_Restrictions {
	var $user_country = "";

	function __construct() {
		if ( defined( 'DOING_CRON' ) and DOING_CRON ) {
			return;
		}
		add_action( 'plugins_loaded', array( $this, 'plugin_init' ) );
	}

	function on_activation() {
		WC_Geolocation::update_database();
	}

	public function plugin_dir_url(){
		return plugin_dir_url( __FILE__ );
	}

	function plugin_init() {
		// $i18n_dir = basename( dirname( __FILE__ ) ) . '/lang/';
		// load_plugin_textdomain( 'woo-product-country-base-restrictions', false, $i18n_dir );

		if ( $this->valid_version() ) {
			add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_custom_product_fields' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_custom_product_fields' ) );

			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_custom_variation_fields'), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'save_custom_variation_fields'), 10, 2 );

			if( get_option('wpcbr_make_non_purchasable') == 'yes' ){
				add_filter( 'woocommerce_is_purchasable', array( $this, 'is_purchasable' ), 10, 2 );
				add_filter( 'woocommerce_available_variation', array( $this, 'variation_filter' ), 10, 3 );
			}

			$position = get_option('wpcbr_message_position', 33 );
			add_action( 'woocommerce_single_product_summary', array($this, 'meta_area_message' ), $position );

			add_filter( 'woocommerce_geolocation_update_database_periodically', array($this, 'update_geo_database'), 10, 1 );

			add_filter( 'woocommerce_product_settings', array($this, 'add_pcr_settings') );

			add_action( 'pre_get_posts', array( $this, 'product_by_country_pre_get_posts' ) );

		} else {
			add_action( 'admin_notices', array( $this, 'admin_error_notice' ) );
		}
	}

	function valid_version() {
		if ( defined( 'WOOCOMMERCE_VERSION' ) ) {
			if ( version_compare( WOOCOMMERCE_VERSION, "3.0", ">=" ) ) {
				return true;
			}
		}
		return false;
	}

	function add_pcr_settings( $settings ) {
		$new_settings = $settings;

		$new_settings[] =
				array( 'type' => 'title', 'title' => __('Product Country Restrictions', 'woo-product-country-base-restrictions') );
		$new_settings[] =
				array(
					'name'     => __( 'Restriction message', 'woo-product-country-base-restrictions' ),
					'desc_tip' => __( 'Error message to display when a product is restricted by country.', 'woo-product-country-base-restrictions' ),
					'id'       => 'product-country-restrictions-message',
					'type'     => 'text',
					'css'      => 'min-width:300px;',
					'std'      => '',
					'default'  => '',
					'desc'     => sprintf(__( 'Leave blank for default message: %s', 'woo-product-country-base-restrictions' ), $this->default_message()),
				);
		$new_settings[] =
				array( 'type' => 'sectionend' );

		return $new_settings;
	}

	function admin_error_notice() {
		$message = __('Product State Restrictions requires WooCommerce 3.0 or newer', 'woo-product-country-base-restrictions');
		echo"<div class='error'><p>$message</p></div>";
	}

	function update_geo_database( ) {
		return true;
	}

	function add_custom_product_fields() {
		global $post;
		echo '<div class="options_group">';

		woocommerce_wp_select(
			array(
				'id'      => '_fz_country_restriction_type',
				'label'   => __( 'Geographic availability', 'woo-product-country-base-restrictions' ),
				'default'       => 'all',
				'class'         => 'availability wc-enhanced-select',
				'options'       => array(
					'all'       => __( 'All states', 'woo-product-country-base-restrictions' ),
					'specific'  => __( 'Selected states only', 'woo-product-country-base-restrictions' ),
					'excluded'  => __( 'Excluding selected states', 'woo-product-country-base-restrictions' ),
				)
			) );

		$selections = get_post_meta( $post->ID, '_fz_restricted_countries', true );
		if(empty($selections) || ! is_array($selections)) {
			$selections = array();
		}
		$countries = WC()->countries->get_states('BR');
		asort( $countries );
?>
		<p class="form-field forminp">
		<label for="_restricted_countries"><?php echo __( 'Selected states', 'woo-product-country-base-restrictions' ); ?></label>
		<select multiple="multiple" name="_restricted_countries[]" style="width:350px"
			data-placeholder="<?php esc_attr_e( 'Choose states&hellip;', 'woocommerce' ); ?>" title="<?php esc_attr_e( 'State', 'woocommerce' ) ?>"
			class="wc-enhanced-select">
			<?php
		if ( ! empty( $countries ) ) {
			foreach ( $countries as $key => $val ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections ), true, false ).'>' . $val . '</option>';
			}
		}
?>
		</select>

		</p><?php
		if( empty( $countries ) ) {
			echo "<p><b>" .__( "You need to setup shipping locations in WooCommerce settings ", 'woo-product-country-base-restrictions')." <a href='admin.php?page=wc-settings'> ". __( "HERE", 'woo-product-country-base-restrictions' )."</a> ".__( "before you can choose state restrictions", 'woo-product-country-base-restrictions' )."</b></p>";
		}

		echo '</div>';
	}

	function add_custom_variation_fields( $loop, $variation_data, $variation ) {

		woocommerce_wp_select(
			array(
				'id'      => '_fz_country_restriction_type[' . $variation->ID . ']',
				'label'   => __( 'Geographic availability', 'woo-product-country-base-restrictions' ),
				'default'       => 'all',
				'class'         => 'availability wc-enhanced-select',
				'value'         => get_post_meta( $variation->ID, '_fz_country_restriction_type', true ),
				'options'       => array(
					'all'       => __( 'All states', 'woo-product-country-base-restrictions' ),
					'specific'  => __( 'Selected states only', 'woo-product-country-base-restrictions' ),
					'excluded'  => __( 'Excluding selected states', 'woo-product-country-base-restrictions' ),
				)
			)
		);

		$selections = get_post_meta( $variation->ID, '_fz_restricted_countries', true );
		if(empty($selections) || ! is_array($selections)) {
			$selections = array();
		}
		$countries = WC()->countries->get_states('BR');
		asort( $countries );
?>
		<p class="form-field forminp">
		<label for="_restricted_countries[<?php echo $variation->ID; ?>]"><?php echo __( 'Selected states', 'woo-product-country-base-restrictions' ); ?></label>
		<select multiple="multiple" name="_restricted_countries[<?php echo $variation->ID; ?>][]" style="width:350px"
			data-placeholder="<?php esc_attr_e( 'Choose states&hellip;', 'woocommerce' ); ?>" title="<?php esc_attr_e( 'State', 'woocommerce' ) ?>"
			class="wc-enhanced-select">
<?php
		if ( ! empty( $countries ) ) {
			foreach ( $countries as $key => $val ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections ), true, false ).'>' . $val . '</option>';
			}
		}
?>
		</select>
<?php
	}

	function save_custom_product_fields( $post_id ) {
		$restriction = sanitize_text_field($_POST['_fz_country_restriction_type']);
		if(! is_array($restriction)) {
			if ( !empty( $restriction ) )
				update_post_meta( $post_id, '_fz_country_restriction_type', $restriction );

			$countries = array();

			if(isset($_POST["_restricted_countries"])) {
				$countries = $this->sanitize( $_POST['_restricted_countries'] );
			}

			update_post_meta( $post_id, '_fz_restricted_countries', $countries );
		}
	}

	function save_custom_variation_fields( $post_id ) {
		$restriction = sanitize_text_field($_POST['_fz_country_restriction_type'][ $post_id ]);
		if ( !empty( $restriction ) )
			update_post_meta( $post_id, '_fz_country_restriction_type', $restriction );

		$countries = array();
		if(isset($_POST["_restricted_countries"])) {
			$countries = sanitize_text_field( $_POST['_restricted_countries'][ $post_id ] );
		}
		update_post_meta( $post_id, '_fz_restricted_countries', $countries );
	}

	function is_restricted_by_id( $id ) {
		$restriction = get_post_meta( $id, '_fz_country_restriction_type', true );
		if ( 'specific' == $restriction || 'excluded' == $restriction ) {
			$countries = get_post_meta( $id, '_fz_restricted_countries', true );
			if ( empty( $countries ) || ! is_array( $countries ) )
				$countries = array();

			$customercountry = $this->get_country();

			if ( 'specific' == $restriction && !in_array( $customercountry, $countries ) )
				return true;

			if ( 'excluded' == $restriction && in_array( $customercountry, $countries ) )
				return true;
		}

		return false;
	}

	function is_restricted( $product ) {
		$id = $product->get_id();

		if($product->get_type() == 'variation') {
			$parentid = $product->get_parent_id();
			$parentRestricted = $this->is_restricted_by_id($parentid);
			if($parentRestricted)
				return true;
		}
		return $this->is_restricted_by_id($id);
	}

	function is_purchasable( $purchasable, $product ) {
		if ( $this->is_restricted( $product ) )
			$purchasable = false;
		return $purchasable;
	}

	function variation_filter($a, $b, $c) {
		if(! $a['is_purchasable']) {
			$a['variation_description'] = $this->no_soup_for_you() . $a['variation_description'];
		}
		return $a;
	}

	function meta_area_message() {
		global $product;

		if($this->is_restricted($product)) {
			echo $this->no_soup_for_you();
		}
	}

	function default_message() {
		return __('Sorry, this product is not available in your country', 'woo-product-country-base-restrictions');
	}

	function no_soup_for_you() {
		$msg = get_option('wpcbr_default_message', $this->default_message());
		if(empty($msg)) {
			$msg = $this->default_message();
		}
		return "<div class='restricted_country'>" . $msg . "</div>";
	}

	function get_country() {
		global $woocommerce;

		if( isset($woocommerce->customer) ){
			$shipping_country = $woocommerce->customer->get_shipping_state();
			if(!empty($shipping_country)){
				$this->user_country = $shipping_country;
				return $this->user_country;
			}
		}

		if( empty( $this->user_country) ) {
			$ip_address = WC_Geolocation::get_ip_address();
			$geoloc = WC_Geolocation::geolocate_ip($ip_address, true, true);
			$ip_details = json_decode(file_get_contents('http://ipinfo.io/'.$ip_address.'/json'));
			$this->user_country = $this->get_region_code($ip_details->region);
		}

		return $this->user_country;
	}

	function get_region_code($region) {
		switch ($region) {
			case 'Acre':
				return 'AC';
			case 'Alagoas':
				return 'AL';
			case 'Amapá':
			case 'Amapa':
				return 'AP';
			case 'Amazonas':
				return 'AM';
			case 'Bahia':
				return 'BA';
			case 'Ceará':
			case 'Ceara':
				return 'CE';
			case 'Distrito Federal':
				return 'DF';
			case 'Espírito Santo':
			case 'Espirito Santo':
				return 'ES';
			case 'Goiás':
			case 'Goias':
				return 'GO';
			case 'Maranhão':
			case 'Maranhao':
				return 'MA';
			case 'Mato Grosso':
				return 'MT';
			case 'Mato Grosso do Sul':
				return 'MS';
			case 'Minas Gerais':
				return 'MG';
			case 'Para':
				return 'PA';
			case 'Paraiba':
				return 'PB';
			case 'Paraná':
			case 'Parana':
				return 'PR';
			case 'Pernambuco':
				return 'PE';
			case 'Piauí':
			case 'Piaui':
				return 'PI';
			case 'Rio de Janeiro':
				return 'RJ';
			case 'Rio Grande do Norte':
				return 'RN';
			case 'Rio Grande do Sul':
				return 'RS';
			case 'Rondônia':
			case 'Rondonia':
				return 'RO';
			case 'Roraima':
				return 'RR';
			case 'Santa Catarina':
				return 'SC';
			case 'São Paulo':
			case 'Sao Paulo':
				return 'SP';
			case 'Sergipe':
				return 'SE';
			case 'Tocantins':
				return 'TO';
		}
		return '';
	}

	function product_by_country_pre_get_posts( $query ) {

		if ( is_admin() ) {
			return;
		}

		if( get_option('product_visibility') == 'hide_catalog_visibility' && $query->is_single == 1 ){
			return;
		}

		remove_action( 'pre_get_posts', array( $this, 'product_by_country_pre_get_posts' ) );
		$country = $this->get_country();
		// Calculate `post__not_in`
		$post__not_in = $query->get( 'post__not_in' );
		$args = $query->query;
		$args['fields'] = 'ids';
		$loop = new WP_Query( $args );
		foreach ( $loop->posts as $product_id ) {
			if( $this->is_restricted_by_id( $product_id ) ){
				$post__not_in[] = $product_id;
			}
		}
		$query->set( 'post__not_in', $post__not_in );
		add_action( 'pre_get_posts', array( $this, 'product_by_country_pre_get_posts' ) );
	}
	public function sanitize( $input ) {
		if( is_array( $input ) ){
			$new_input = array();
			// Loop through the input and sanitize each of the values
			foreach ( $input as $key => $val ) {
				$new_input[ $key ] = ( isset( $input[ $key ] ) ) ? sanitize_text_field( $val ) : '';
			}
			// Initialize the new array that will hold the sanitize values
			return $new_input;
		}
		return sanitize_text_field( $input );
	}
}
$psr = new Product_State_Restrictions();
register_activation_hook( __FILE__, array( $psr, 'on_activation' ) );
