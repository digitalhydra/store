jQuery(document).ready(function($) {
    // Sorting
    $('#cat_tbody, #product_tbody').sortable({
        items:'tr',
        cursor:'move',
        axis:'y',
        handle: 'td',
        scrollSensitivity:40,
        helper:function(e,ui){
            ui.children().each(function(){
                $(this).width($(this).width());
            });
            ui.css('left', '0');
            return ui;
        },
        start:function(event,ui){
            ui.item.css('background-color','#f6f6f6');
        },
        stop:function(event,ui){
            ui.item.removeAttr('style');
            update_priorities();
        }
    });
    var categories = store_categories;

    $("#add_category").click(function(e) {
        $("#no_categories").remove();

        var options = '';

        // remove all selected categories
        for (var x = 0; x < categories.length; x++) {
            var used = false;
            $(".category-select option:selected").each(function() {
                if ($(this).val() == categories[x].id) {
                    used = true;
                    return false;
                }
            });

            if (!used) {
                options += '<option value="'+ categories[x].id +'">'+ categories[x].name +'</option>';
            }
        }

        if (options == '') {
            alert( cart_addons_settings.all_categories_used );
            return false;
        }

        var number;
        do {
            number = 1 + Math.floor(Math.random() * 9999999);
        } while ($("#cselect_"+number).length > 0);

        var html = $("#category_form_template tbody").html();
        html = html.replace(/\{number\}/g, number);
        html = html.replace(/(sfn\-product\-search\-tpl)/g, 'sfn-product-search');

        $("#cat_tbody").append("<tr>"+ html +"</tr>");

        $("select#category_"+ number).html(options);

        sfn_ajax_search();
    });

    $("#add_product").click(function(e) {
        $("#no_products").remove();

        var options = '';

        var number;
        do {
            number = 1 + Math.floor(Math.random() * 9999999);
        } while ($("#pselect_"+number).length > 0);

        var html = $("#product_form_template tbody").html();

        html = html.replace(/\{number\}/g, number);
        html = html.replace(/(sfn\-product\-search\-tpl)/g, 'sfn-product-search');

        $("#product_tbody").append(html);
        sfn_ajax_search();
    });

    $("tbody#product_tbody").on("change", ".product-select", function() {
        var $label = $(this).next(".include-variations-label");

        $.get(ajaxurl, {action: "sfn_product_is_variable", product_id: $(this).val()}, function(resp) {
            if ( resp.is_variable === true ) {
                $label.show();
            } else {
                $label.hide();
                $label.find(":input[type=checkbox]").attr("checked", false);
            }
        }, "json");
    });

    $(".remove").live("click", function(e) {
        e.preventDefault();
        $(this).parents("tr").remove();
    });
});

function update_priorities() {
    jQuery('#cat_tbody tr').each(function(x){
        jQuery(this).find('td .priority').html(x+1);
    });

    jQuery('#product_tbody tr').each(function(x){
        jQuery(this).find('td .priority').html(x+1);
    });
}