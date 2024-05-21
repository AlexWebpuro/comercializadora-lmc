
jQuery(document).ready( function() {
    const  userLocationID = locationID.id.toString();
    const $cartSelect = jQuery('.slw_item_stock_location.slw_cart_item_stock_location_selection');
    console.log('Alex was here!');
    // console.log(userLocationID);

    let options = jQuery('#slw_item_stock_location_simple_product').find('option');

    // Si hay stock en la sucursal escogerla.
    options.each(function(index, opcion) {
        // Acceder a cada opción individualmente
        var value = jQuery(opcion).val();
        var text = jQuery(opcion).text();

        if( value == userLocationID ) {
            jQuery('#slw_item_stock_location_simple_product').val(`${userLocationID}`);
        }
    });
    if($cartSelect && window.location.pathname == '/carrito-de-compras/') {
        $cartSelect.prop('disabled','disabled');
    } else {
        console.log($cartSelect);
    }

    
});

