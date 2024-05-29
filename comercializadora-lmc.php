<?php

/**
 * Plugin Name:     Comercializadora LMC
 * Plugin URI:      https://apprillabs.com
 * Description:     Customizaciones para el core de negocio.
 * Author:          Alex Monroy
 * Author URI:      #
 * Text Domain:     comercializadora-lmc
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Comercializadora LMC
 */

require __DIR__ . '/vendor/autoload.php';

if(! defined('LMC_DOMAIN')) {
    define('LMC_DOMAIN', 'comercializadora-lmc');
}

     
function custom_roles_plugin_add_roles() {
   // add_role(
   //     'administrador_sucursal',
   //     'Administrador de Sucursal',
   //     array(
   //         'read'                  => true,
   //         'edit_posts'            => true, // Por ejemplo, no permitir la edición de publicaciones
   //         'manage_woocommerce'    => true, // Capacidad para administrar WooCommerce
   //     )
   // );
   remove_role('vendedor');
   remove_role('administrador_sucursal');
}

/**
 * Add field Location on User profile.
 */
function custom_roles_plugin_add_custom_user_profile_fields($user) {
    $selected_term = get_user_meta($user->ID, 'sucursal', true); // Obtener el término seleccionado para el usuario

    $args = array(
        'taxonomy' => 'location',
        'hide_empty' => false,
    );
    $terms = get_terms($args); // Get Location taxonomy
    
    ?>
    <h3><?php _e('Sucursal', LMC_DOMAIN); ?></h3>
    <table class="form-table" id="Location-table">
        <tr>
            <th><label for="sucursal"><?php _e('Seleccione la sucursal', LMC_DOMAIN); ?></label></th>
            <td>
                <select name="sucursal" id="sucursal" class="select2">
                    <option value=""><?php _e('Seleccione una sucursal', LMC_DOMAIN); ?></option>
                    <?php foreach ($terms as $term) : ?>
                        <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($selected_term, $term->term_id); ?>><?php echo esc_html($term->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'custom_roles_plugin_add_custom_user_profile_fields');
add_action('edit_user_profile', 'custom_roles_plugin_add_custom_user_profile_fields');

/**
 * Save value field "Location" to update user profile.
 */
function custom_roles_plugin_save_custom_user_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['sucursal'])) {
        update_user_meta($user_id, 'sucursal', $_POST['sucursal']);
    }
}
add_action('personal_options_update', 'custom_roles_plugin_save_custom_user_profile_fields');
add_action('edit_user_profile_update', 'custom_roles_plugin_save_custom_user_profile_fields');


/**
 * Load custom scripts.
 */
function lmc_sctipts(){
    
    $user_id = get_current_user_id();

    $location_id = get_user_meta($user_id, 'sucursal', true);    
	wp_register_script('location', plugin_dir_url( __FILE__ ) . '/assets/js/frontend.js', array('jquery'), '', true );
	wp_enqueue_script('location');
    
	wp_localize_script('location','locationID',['id'=>$location_id]);
}
add_action('wp_enqueue_scripts', 'lmc_sctipts');

/**
* Add a custom field to the checkout page
*/
function custom_checkout_field($checkout) {
    $user_id = get_current_user_id(  );

    $user_location = get_user_meta($user_id, 'sucursal', true); // Obtener el término seleccionado para el usuario

    // Obtenemos los términos de la taxonomía 'location'
    $args = array(
        'taxonomy' => 'location',
        'hide_empty' => false,
    );
    $terms = get_terms($args);

    // dump($terms,$user_location, $user);

    echo '<div id="locationLMC"><h3>' . __('Sucursal en donde se hace la compra') . '</h3>';
    woocommerce_form_field('location', array(
            'type'          => 'select',
            'required'      => true,
            'class'         => array(
                'my-field-class form-row-wide'
            ),
            'label'         => __('Sucursal') ,
            'placeholder'   => __('Seleccione una sucursal') ,
            'options'       => wp_list_pluck( $terms, 'name', 'term_id' ),
            'default'       => $user_location
        ), $user_location
        // $checkout->get_value('location')
    );
    echo '</div>';
}

add_action('woocommerce_after_checkout_billing_form', 'custom_checkout_field');

/**
 * Save the dropdown custom field selected value as order custom meta data:
 */
add_action( 'woocommerce_checkout_create_order', 'my_custom_checkout_field_update_order_meta', 10, 2 );
function my_custom_checkout_field_update_order_meta( $order, $data ) {
    if ( isset($_POST['location']) && ! empty($_POST['location']) ) {
        $order->update_meta_data( 'location', sanitize_text_field( $_POST['location'] ) );
    } 
}

/**
 * Display the custom field value on admin order pages after billing adress:
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'my_custom_checkout_field_display_admin_order_meta', 10, 1 );
function my_custom_checkout_field_display_admin_order_meta( $order ) {
    echo '<p><strong>'.__('Location').':</strong> ' . $order->get_meta('location') . '</p>'; 
}

/**
 * Display the custom field value on email notifications:
 */
add_action( 'woocommerce_email_after_order_table', 'custom_woocommerce_email_order_meta_fields', 10, 4 );
function custom_woocommerce_email_order_meta_fields( $order, $sent_to_admin, $plain_text, $email ) {
    echo '<p><strong>'.__('Location').':</strong> ' . $order->get_meta('location') . '</p>';
}

/**
 * Agregar el filtro en la pantalla de órdenes de pedido
 */
function agregar_filtro_location_select2() {
    global $typenow;
    if ( 'shop_order' === $typenow ) {
        $terms = get_terms( array(
            'taxonomy' => 'location',
            'hide_empty' => false,
        ) );

        echo '<select name="location_filter" id="dropdown_location_filter">';
        echo '<option value="">' . __( 'Filtrar por sucursal', LMC_DOMAIN ) . '</option>';
        
        foreach ( $terms as $term ) {
            $selected = isset($_GET['location_filter']) && $_GET['location_filter'] == $term->term_id ? 'selected' : '';
            echo '<option value="' . esc_attr( $term->term_id ) . '" ' . $selected . '>' . esc_html( $term->name ) . '</option>';
        }

        echo '</select>';
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $('#dropdown_location_filter').select2();
            });
        </script>
        <?php
    }
}
add_action( 'restrict_manage_posts', 'agregar_filtro_location_select2', 20 );

/**
 * Modificar la consulta de pedidos para incluir el filtro
 */
function filtrar_ordenes_por_location( $query ) {
    global $pagenow, $typenow;

    if ( 'shop_order' === $typenow && 'edit.php' === $pagenow && isset( $_GET['location_filter'] ) && ! empty( $_GET['location_filter'] ) ) {
        $meta_query = array(
            array(
                'key'     => 'Location',
                'value'   => sanitize_text_field( $_GET['location_filter'] ),
                'compare' => '='
            )
        );
        $query->set( 'meta_query', $meta_query );
    }
}
add_action( 'pre_get_posts', 'filtrar_ordenes_por_location' );

function lmc_hide_nenus_to_seller() {
    if ( current_user_can( 'admin_location' ) ) {
        // remove_menu_page( 'woocommerce' ); // WooCommerce main menu

        // Submenús específicos
        remove_submenu_page( 'woocommerce', 'wc-admin' ); // Analytics 
        remove_submenu_page( 'woocommerce', 'wc-admin&path=/analytics/overview' ); // Analytics
        remove_submenu_page( 'woocommerce', 'wc-admin&path=/customers' ); // Analytics
        remove_submenu_page( 'woocommerce', 'wc-admin&path=/extensions' ); // Analytics
        remove_submenu_page( 'woocommerce', 'wc-reports' ); // Reports
        remove_submenu_page( 'woocommerce', 'wc-settings' ); // Settings
        remove_submenu_page( 'woocommerce', 'wc-status' ); // Status
        remove_submenu_page( 'woocommerce', 'wc-addons' ); // Addons
        remove_submenu_page( 'users.php', 'users.php' );

        // Si quieres eliminar otros submenús de WooCommerce, puedes agregar más líneas aquí.
    }
}
add_action( 'admin_menu', 'lmc_hide_nenus_to_seller', 99 );

/**
 * Cargar select2 en el admin
 */
function cargar_select2_admin() {
    global $typenow;
    if ( 'shop_order' === $typenow ) {
        wp_enqueue_script( 'select2', plugins_url( '/select2/js/select2.min.js', __FILE__ ), array( 'jquery' ), '4.0.13' );
        wp_enqueue_style( 'select2-css', plugins_url( '/select2/css/select2.min.css', __FILE__ ), array(), '4.0.13' );
    }
}
// add_action( 'admin_enqueue_scripts', 'cargar_select2_admin' );


// add_action('wp_footer', function() {
//     $order = wc_get_order( 5600 );
//     // $temp = get_post_meta( 5583, 'Location', sanitize_text_field( $_POST['locationLMC'] ) );
//     dump($order);
// });

function lmc_test() {
    $user_id = get_current_user_id(); // Obtener el ID del usuario actualmente autenticado

    $user_meta = get_user_meta($user_id);

    // dump($user_meta, $user_id, get_userdata( get_current_user_id() )->allcaps);
}

function agregar_todas_las_taxonomias_location_al_producto($post_id, $post, $update) {
    // Verificar que sea un producto y que no sea una actualización
    if ($post->post_type != 'product' || $update) {
        return;
    }

    // Obtener todas las taxonomías de 'location'
    $terms = get_terms(array(
        'taxonomy' => 'location',
        'hide_empty' => false,
    ));

    if (!is_wp_error($terms) && !empty($terms)) {
        $term_ids = wp_list_pluck($terms, 'term_id');
        // Asignar todas las taxonomías 'location' al producto
        wp_set_object_terms($post_id, $term_ids, 'location');
    }
    
    // Hacer seguimiento de la cantidad de inventario de este producto por defecto
    update_post_meta($post_id, '_manage_stock', 'yes');
    update_post_meta($post_id, '_stock', 0); // Inicializar el stock en 0
    update_post_meta($post_id, '_stock_status', 'instock'); // Establecer el estado del stock en 'instock'
}
add_action('save_post', 'agregar_todas_las_taxonomias_location_al_producto', 10, 3);


// add_action('wp_footer', 'lmc_test', 10, 0 );

function dump_submenus_woocommerce() {
    global $submenu;

    // Verificar si WooCommerce está activo
    // dump($submenu['woocommerce']);
    dump($submenu['users.php']);
}

// add_action( 'admin_footer', 'dump_submenus_woocommerce' );

function obtener_meta_data_item_pedido( ) {
    // Obtener el objeto del pedido
    $order = wc_get_order( 5600 );
    
    // Verificar si el pedido es válido
    if ( ! $order ) {
        return;
    }

    // Obtener los ítems del pedido
    $items = $order->get_items();

    // Recorrer los ítems del pedido
    foreach ( $items as $item_id => $item ) {
        // Obtener la meta data del ítem
        $meta_data = $item->get_meta_data();

        // Mostrar la meta data del ítem (puedes ajustarlo según tus necesidades)
        foreach ( $meta_data as $meta ) {
            // dump($meta->key, $meta->value);
            // echo 'Key: ' . $meta->key . '<br>';
            // echo 'Value: ' . $meta->value . '<br>';
        }
    }
    // 12 item id
    // 5600 order id
    // Update the itemmeta of the order item
    // wc_update_order_item_meta($orderItemId, '_item_stock_locations_updated', 'yes');}
    $order_item_id = 12;
    $location = $order->get_meta('location');
    $data = wc_update_order_item_meta($order_item_id, '_item_stock_updated_at_' . $location, 1);
    $update = wc_update_order_item_meta($order_item_id, '_item_stock_updated_at_937', 'yes');
    

    // Save itemmeta _slw_data
    $current_slw_data = wc_get_order_item_meta( $order_item_id, '_slw_data', true );
    $new_data = array(
        $location => array(
            'location_name' 		=> 'Perrito',
            'quantity_subtracted'	=> '1'
        )
    );
    $_slw_data = wc_update_order_item_meta( $order_item_id, '_slw_data', $new_data );


    dump($location,$data,$_slw_data, $update);
}
// add_action( 'wp_footer', 'obtener_meta_data_item_pedido' );