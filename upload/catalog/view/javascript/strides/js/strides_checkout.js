
class StridesCheckout {

    baseUrl = "index.php?route=extension/module/strides/checkout";

    ToggleTab(evt) {
        const name = evt.target.value;
        
        switch (name) {
            case "login":
                $("#password").slideUp();
                $("#customer").slideDown();
                break;
            case "register":
                $("#customer").slideUp();
                $("#password").slideDown();
                break;
            case "guest":
                $("#customer").slideUp();
                $("#password").slideUp();
                break;
        }
    }


    setCart(response) {

        const { total, cart, payment } = response;
        console.log(total, response  );
        if (total) {
            $("#cart-total").text(total);
            $("#cart ul").load("index.php?route=common/cart/info ul li");
        }
        if (cart) {
            $(".checkout-content.checkout-cart").replaceWith(cart);
        }
        if (payment) {
            $(".confirm-order").html(payment);
        }
    }



    setRigth( response ) {
        let { action, zone, shipping_methods, payment_methods, cart, payment, total } = response

        if ( zone && action ===  'address_update') {
            let html = `<option value=""> --- ${response.text_select} --- </option>`;
            if (zone == "") {
                html += `<option value="0" selected>${response.text_none}</option>`;
            }
            else {
                zone = JSON.parse(zone);
                zone.forEach((el) => {
                    html += '<option value="' + el["zone_id"] + '"';
                    html += ">" + el["name"] + "</option>";
                });
            }
            $(`.strides_checkout form#checkout-${response.type} select[name=${response.type}_zone_id]`).html(html);
        } 
        if ( shipping_methods ) {
            $(".checkout-content.checkout-shipping-methods").replaceWith(
                response.shipping_methods
            );
        }
        if ( payment_methods ) {
            $(".checkout-content.checkout-payment-methods").replaceWith(
                response.payment_methods
            );
        }
        if (cart) {
            $(".checkout-content.checkout-cart").replaceWith(response.cart);
        }

        if ( payment ) {
            $(".confirm-order").html(payment);
        }
        if( total ){
            $("#cart-total").text(total);
            $("#cart ul").load("index.php?route=common/cart/info ul li");
        }


    }


    updateData( url, data ) {

        $(".owerflow").addClass("active");
    
        $.post(url, data, null, "json").then((response) => {
            $(".owerflow").removeClass("active");

            if ("redirect" in response && (response.redirect || "").length > 1) {
                window.location.href = response.redirect;
            }
            
            else if ("errors" in response && !$.isEmptyObject(response.errors) ) {
                $(".errors .error").empty();
                for (const key in response.errors) {
                    $(".errors .error").append(`<div>${response.errors[key]}</div>`);
                    $(".errors").addClass("active");
                }
            }
            if ("error" in response && !$.isEmptyObject(response.error) ) {
                $(".errors .error").empty();
                    $(".errors .error").append(`<div>${response.error}</div>`);
                    $(".errors").addClass("active");
            }
           
            if ("action" in response) {
                switch (response.action) {
                    case "cart":
                    case "address_update":
                        this.setRigth(response);
                        break;
                    case "method_update":
                    case "final":
                        this.setCart(response);
                        break;
                }
            }
        });
    
        setTimeout(() => $('.owerflow').removeClass('active'), 7000);
    }


    setCountry(data) {
        this.updateData(`${this.baseUrl}/dispatcher`, data);
    }

}


const StrCheckout = new StridesCheckout()


$(document).ready(function() {
    
    $(".login-box input").on("change", StrCheckout.ToggleTab);

    $("input[name=shipping_address]").on(
        "change", () =>
        $(".checkout-shipping-form").slideToggle() &&
        $(".checkout-shipping-form > form").trigger("reset")
    );

    $(".strides_checkout-custom-filds input").change(function() {
        console.log(this.value);
        $(".checkout-custom-filds fieldset").slideUp();
        $("#" + this.value).slideDown();
    });


    $(".errors .list img").click(function() {
        $(".errors").removeClass("active");
    });

    const update_methods = '.checkout-shipping-methods input, .checkout-payment-methods input'
    const update_address = "#input-payment-country, #input-shipping-country";


    $('.strides_checkout').on('change', update_methods, function(){
        let method = [{
            name: 'action',
            value: $(this).attr('name')
        }]
        const data = [ ...$(this).serializeArray(), ...method ]
        StrCheckout.setCountry( data );
        console.log( data );
    });


    $('.strides_checkout').on('change', update_address, function() {
        
        let method = {
            name: 'action'
        }
        let shipping_address = $("input[name=shipping_address]:checked").val();
        
        let data = $(
            "#account input, #checkout-payment input, #checkout-payment select, .checkout-shipping-methods input, .checkout-payment-methods input"
        ).serializeArray();

        data.push({
            name: "type",
            value: $(this).data('type'),
        });

        data.push({
            name: "account",
            value: $("input[name=account]:checked").val() || 'new'
        });

        if (shipping_address == 'existing' || shipping_address == '1' ) {
            data.push({
                name: "shipping_address",
                value: shipping_address
            });
        } else {
            data = [
                ...data,
                ...$(
                    "#checkout-shipping input, #checkout-shipping select"
                ).serializeArray(),
            ];
        }

        if ($("#invoice").css("display") == "flex") {
            data = [...data, ...$("#invoice input").serializeArray()];
        }

        if ( $(this).parents('.checkout-payment-methods').length ) {
            method.value = 'payment_methods'
        }
        else if ( $(this).parents('.checkout-shipping-methods').length ) {
            method.value = 'shipping_methods'
        }
        else if ( $(this).is('#input-payment-country') ) {
            method.value = 'payment_country'
        }
        else if ( $(this).is('#input-shipping-country') ) {
            method.value = 'shipping_country'
        }

        data.push(method)

        StrCheckout.setCountry( data );
    });



    $('input[name=shipping_address]').change(function(){
        let input = ''

        switch ($(this).val()) {
            case 'existing':
                input = 'select[name=shipping_address_id]'
                break;
            case 'new':
                input = '#checkout-shipping select, #checkout-shipping input'
                break;
        }

        if (input) {
            const data = [ 
                ...$(input).serializeArray(),
                {name: 'action', value: 'islogin'} 
            ]
            StrCheckout.setCountry(data)
            console.log(data );
        }
        
    })



    /**coupon*/

    $('#button-coupon').click(function(){
        const data = {
            coupon: $('input[name=coupon]').val(),
            action: 'coupon'
        };
        StrCheckout.updateData( StrCheckout.baseUrl + '/dispatcher', data )
    })

    /**voucher*/

    $('#button-voucher').click(function(){
        const data = {
            voucher: $('input[name=voucher]').val(),
            action: 'voucher'
        };
        StrCheckout.updateData( StrCheckout.baseUrl + '/dispatcher', data )
        
    })


    /**reward */

    $('#button-reward').click(function(){
        const data = {
            reward: $('input[name=reward]').val(),
            action: 'reward'
        };
        StrCheckout.updateData( StrCheckout.baseUrl + '/dispatcher', data )
    })


    /**confirm checkout*/

    $('.strides_checkout').on('click', "#strides_checkout-confirm-button", function(evt) {
        evt.stopPropagation();
        evt.preventDefault();
        const fields = $(
            `.strides_checkout input[type="text"], .strides_checkout input[type="number"], .strides_checkout input[type="password"],
		 .strides_checkout select, .strides_checkout input:checked,.strides_checkout textarea[name="comment"] `
        );
        const data = fields.serializeArray();
        data.push({
            name: "action",
            value: "final"
        });
        StrCheckout.updateData(`${StrCheckout.baseUrl}/dispatcher`, data);
    });


    /** delete */
    $("#strides_checkout-cart").on("click", ".btn-delete", function(evt) {
        evt.stopPropagation();
        evt.preventDefault();
        const data = {
            cart_id: Number(this.dataset.cart_id),
            action: 'delete'
        };
        StrCheckout.updateData(`${StrCheckout.baseUrl}/dispatcher`, data);
    });



    /**update qty */
    $(".strides_checkout").on("change", ".quantity input", function() {
        const data = {
            key: this.getAttribute("name"),
            quantity: Number(this.value),
            action: 'qty'
        };
        StrCheckout.updateData(`${StrCheckout.baseUrl}/dispatcher`, data);
    });



   



    $("#button-login").click(function() {
        const data = $("#customer input").serializeArray();
        $(".owerflow").addClass("active");
        $(".errors").removeClass("active");
        $(".errors .error").empty();
        $.post(`${StrCheckout.baseUrl}/login`, data, null, "json").then((response) => {
            $(".owerflow").removeClass("active");
            if ("errors" in response) {
                for (const key in response.errors) {
                    $(".errors .error").append(`<div>${response.errors[key]}</div>`);
                    $(".errors").addClass("active");
                }
            } else if ("redirect" in response) {
                window.location.href = response.redirect;
            }
        });
    });


})