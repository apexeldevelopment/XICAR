$(document).ready(function () {
    // Not allow special characters
    var pattner_string = /^[^\s!"#$%&'()*+,./:;<=>?@\[\]^`{|}~]+( [^\s!"#$%&'()*+,./:;<=>?@\[\]^`{|}~]+)*$/;

    $("#taxicompany-form").validate({
        rules: {
            name:{
                required: true,
                minlength: 3,
                maxlength: 30,
                pattern:pattner_string
            },
            country: {
                required: true,
                digits: true,
            },
            phone:{
                required: true,
                digits:true
            },
            contact_person: {
                required: true,
                minlength: 3,
                maxlength: 30,
                pattern:pattner_string
            },
            email: {
                required: true,
                email:true
            },
            address: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            password: {
                required: function(element){
                    return $("#taxi_company_id").val()=="";
                },
                minlength: 8,
                maxlength: 40,
            },
            company_logo: {
                required: function(element){
                    return $("#taxi_company_id").val()=="";
                },
                accept: "image/*"
            },
            bank_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            account_holder_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            account_number: {
                required: true,
                digits: true,
                maxlength: 30,
                minlength: 3,
            },
            online_transaction: {
                required: true,
                minlength: 3,
                maxlength: 50,
            },
            account_types: {
                required: true,
                digits: true,
            }
        },
        messages:{
            company_logo:{
                accept: "Invalid Image File"
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#hotel-form").validate({
        rules: {
            name:{
                required: true,
                minlength: 3,
                maxlength: 30,
                pattern:pattner_string
            },
            country: {
                required: true,
            },
            phone:{
                required: true,
                digits:true
            },
            email: {
                required: true,
                email:true
            },
            address: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            password: {
                required: function(element){
                    return $("#hotel_id").val()=="";
                },
                minlength: 8,
                maxlength: 40,
            },
            hotel_logo: {
                required: function(element){
                    return $("#hotel_id").val()=="";
                },
                accept: "image/*"
            },
            bank_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            account_holder_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            account_number: {
                required: true,
                digits: true,
                maxlength: 30,
                minlength: 3,
            },
            online_transaction: {
                required: true,
                minlength: 3,
                maxlength: 50,
            },
            account_types: {
                required: true,
                digits: true,
            }
        },
        messages:{
            hotel_logo:{
                accept: "Invalid Image File"
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#corporate-form").validate({
        rules: {
            corporate_name:{
                required: true,
                minlength: 3,
                maxlength: 30,
                pattern:pattner_string
            },
            country: {
                required: true,
            },
            phone:{
                required: true,
                digits:true
            },
            email: {
                required: true,
                email:true
            },
            address: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            password: {
                required: function(element){
                    return $("#corporate_id").val()=="";
                },
                minlength: 8,
                maxlength: 40,
            },
            corporate_logo: {
                required: function(element){
                    return $("#corporate_id").val()=="";
                },
                accept: "image/*"
            },
            password_confirmation: {
                required: function(element){
                    return $("#corporate_id").val()=="";
                },
                minlength: 8,
                maxlength: 40,
                equalTo: "#password"
            }
        },
        messages:{
            corporate_logo:{
                accept: "Invalid Image File"
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    // $("#countryForm").validate({
    //     rules: {
    //         name: {
    //             required:true,
    //             lettersonly: true,
    //         },
    //         phonecode:{
    //             required:true,
    //             digits: true,
    //         },
    //         isocode:{
    //             required:true,
    //             lettersonly: true,
    //         },
    //         country_code:{
    //             required:true,
    //             lettersonly:true,
    //         },
    //         distance_unit:{
    //             required:true,
    //         },
    //         min_digits:{
    //             required:true,
    //             digits: true,
    //             min:1,
    //             max:25
    //         },
    //         max_digits:{
    //             required:true,
    //             digits: true,
    //             min:1,
    //             max:25
    //         },
    //         online_transaction:{
    //             required:true,
    //             lettersonly: true
    //         },
    //         sequence:{
    //             required:true,
    //             digits:true,
    //         }
    //     },
    //     highlight: function(element, errorClass, validClass) {
    //         $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
    //     },
    //     unhighlight: function(element, errorClass, validClass) {
    //         $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
    //     }
    // });

    $("#category-form").validate({
        rules: {
            category_name: {
                required: true,
                maxlength: 30,
                // lettersonly: true,
                pattern:pattner_string
            },
            "segment[]": {
                required: true,
                minlength: 1
            },
            status: {
                required: true,
            },
            sequence: {
                required: true,
                digits: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#country-area-step1").validate({
        rules: {
            name: {
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            country: {
                required: true,
                digits: true,
            },
            timezone: {
                required: true,
            },
            status: {
                required: true,
            },
            minimum_wallet_amount: {
                required: function(element){
                    return $("#driver_wallet_status").val()==1;
                },
                // digits: true,
                // min:-5000,
            },
            auto_upgradetion: {
                required: function(element){
                    return $("#no_driver_availabe_enable").val()==1;
                },
                digits: true,
            },
            manual_downgradation: {
                required: function(element){
                    return $("#manual_downgrade_enable").val()==1;
                },
                digits: true,
            },
            "driver_document[]": {
                required: true,
                minlength: 1
            },
            "payment_method[]": {
                required: true,
                minlength: 1
            },
            driver_cash_limit_amount: {
                required: function(element){
                    return $("#driver_cash_limit").val()==1;
                },
                min: 0,
                max: 1000000
            },
            is_geofence: {
                required: function(element){
                    return $("#geofence_module").val()==1;
                }
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#country-area-step2").validate({
        rules: {
            "vehicle_type":{
                required: true,
                digits:true
            },
            "vehicle_doc": {
                required: true,
                minlength: 1
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        },
        // errorPlacement: function (error, element) {
        //     if (element.is(":radio") || element.is(":checkbox")) {
        //         error.insertAfter(element.parent());
        //     } else {
        //         error.insertAfter(element.parent());
        //     }
        // },
    });

    $("#advertisement-banner").validate({
        rules: {
            name:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            image: {
                required: function(element){
                    return $("#banner_id").val()=="";
                },
            },
            sequence: {
                required: true,
                digits: true
            },
            banner_for: {
                required: true,
            },
            home_screen: {
                required: true,
            },
            status: {
                required: true,
            },
            validity: {
                required: true,
            },
            activate_date: {
                required: true,
            },
            redirect_url: {
                required: function(element){
                    return $("#action_type").val()=="URL";
                }
            },
            category_id: {
                required: function(element){
                    return $("#action_type").val()=="CATEGORY";
                }
            },
            product_id: {
                required: function(element){
                    return $("#action_type").val()=="PRODUCT";
                }
            }
            // expire_date: {
            //     required: function(element){
            //         return $("#validity").val()==2;
            //     }
            // }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#weight-unit").validate({
        rules: {
            name:{
                required: true,
                maxlength: 30,
                // pattern:pattner_string
            },
            description: {
                required: true,
                maxlength: 40,
            },
            "segment[]": {
                required: true,
                minlength: 1
            },
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#option-type-form").validate({
        rules: {
            type:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            charges_type: {
                required: true,
            },
            select_type: {
                required: true,
            },
            max_option_on_app: {
                required: true,
                digits: true,
                min: 0,
            },
            status: {
                required: true,
            },
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#style-management").validate({
        rules: {
            style_name:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            status: {
                required: true,
            },
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#vehicle-type-add").validate({
        groups: {  // consolidate messages into one
            names: "ride_now ride_later"
        },
        rules: {
            vehicle_name:{
                required: true,
                maxlength: 30,
                // pattern:pattner_string
            },
            vehicle_rank: {
                required: true,
                digits: true,
                min: 1
            },
            sequence: {
                required: true,
                digits: true,
                min: 1,
                maxlength:10
            },
            model_expire_year: {
                required: function(element){
                    return $("#vehicle_model_expire_enable").val()==1;
                },
                digits: true,
                min: 1,
                mix: 50,
            },
            vehicle_image: {
                required: function() {
                    return $('#gallery_image').val() === '';
                },
                file: true
            },
            gallery_image: {
                required: function() {
                    return $('#vehicle_image').val() === '';
                },
                file: true
            },
            description: {
                maxlength:500
            },
            vehicle_map_image:{
                required: true,
            },
            status: {
                required: true,
            },
            ride_now: {
                require_from_group: [1, ".ride_type"]
            },
            ride_later: {
                require_from_group: [1, ".ride_type"]
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        },
        errorPlacement: function (error, element) {
            if (element.is(":radio") || element.is(":checkbox")) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element.parent());
            }
        },
        messages: {
            vehicle_image: {
                required: "Either vehicle image or gallery image is required."
            },
            gallery_image: {
                required: "Either vehicle image or gallery image is required."
            }
        }
    });

    $("#vehicle-type-edit").validate({
        groups: {  // consolidate messages into one
            names: "ride_now ride_later"
        },
        rules: {
            vehicle_name:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            vehicle_rank: {
                required: true,
                digits: true,
                min: 1
            },
            sequence: {
                required: true,
                digits: true,
                min: 1,
                maxlength: 10
            },
            model_expire_year: {
                required: function(element){
                    return $("#vehicle_model_expire_enable").val()==1;
                },
                digits: true,
                min: 1,
                mix: 50,
            },
            description: {
                // required: true,
                maxlength:500
            },
            vehicle_map_image:{
                required: true,
            },
            status: {
                required: true,
            },
            ride_now: {
                require_from_group: [1, ".ride_type"]
            },
            ride_later: {
                require_from_group: [1, ".ride_type"]
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        },
        errorPlacement: function (error, element) {
            if (element.is(":radio") || element.is(":checkbox")) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element.parent());
            }
        }
    });

    $("#vehicle-make").validate({
        rules: {
            vehicle_make:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            description: {
                // required: true,
                maxlength:500
            },
            vehicle_make_logo:{
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#vehicle-model").validate({
        rules: {
            vehicle_model:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            vehicletype:{
                required: true,
                digits: true
            },
            vehiclemake:{
                required: true,
                digits: true
            },
            description: {
                required: true,
            },
            vehicle_seat:{
                required: true,
                digits: true,
                min: 0,
                max: 200,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#document-form").validate({
        rules: {
            documentname:{
                required:true,
                maxlength:70,
            },
            name:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            documentNeed:{
                required: true,
            },
            expire_date:{
                required: true,
            },
            document_number_required: {
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#pricing-parameter-form").validate({
        rules: {
            parametername:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            "segment[]":{
                required: true,
                minlenght:1
            },
            parameter_display_name: {
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            sequence:{
                required: true,
                digits:true
            },
            parameterType: {
                required: true,
            },
            "price_type[]": {
                required: true,
                minlenght:1
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#pricing-parameter-form").validate({
        rules: {
            parametername:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            "segment[]":{
                required: true,
                minlenght:1
            },
            parameter_display_name: {
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            sequence:{
                required: true,
                digits:true
            },
            parameterType: {
                required: true,
            },
            "price_type[]": {
                required: true,
                minlenght:1
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#account-type-form").validate({
        rules: {
            name:{
                required: true,
                maxlength: 30,
                pattern:pattner_string
            },
            "status":{
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#pricecard_form").validate({
        rules: {
            price_card_name:{
                required: true,
                maxlength: 100,
                pattern:pattner_string
            },
            country_area_id:{
                required: function(element){
                    return $("#id").val()=="";
                },
                digits:true
            },
            vehicle_type_id: {
                required: function(element){
                    return $("#id").val()=="";
                },
                digits: true
            },
            segment_id: {
                required: function(element){
                    return $("#id").val()=="";
                },
                digits: true
            },
            service_type_id:{
                required: function(element){
                    return $("#id").val()=="";
                },
                digits: true
            },
            price_type:{
                required: true,
                digits:true
            },
            minimum_wallet_amount: {
                required: function(element){
                    return $("#user_wallet_status").val()==1;
                },
                digits:true,
                minlength:0,
                maxlength:10
            },
            cancel_charges: {
                required: function(element){
                    return $("#cancel_charges_enable").val()==1 && $("#cancel_charges").val()==1;
                },
            },
            cancel_time: {
                required: function(element){
                    return $("#cancel_charges_enable").val()==1 && $("#cancel_charges").val()==1;
                },
                digits:true,
                minlength:0,
                maxlength:10
            },
            cancel_amount: {
                required: function(element){
                    return $("#cancel_charges_enable").val()==1 && $("#cancel_charges").val()==1;
                },
                // digits:true,
                minlength:0,
                maxlength:10
            },
            commission_method: {
                required: true,
                digits:true
            },
            commission_value: {
                required: true,
                digits:true,
                minlength:0,
                maxlength:10
            },
            taxi_commission_method: {
                required: function(element){
                    return $("#taxi_company_enable").val()==1;
                },
                digits:true
            },
            taxi_commission: {
                required: function(element){
                    return $("#taxi_company_enable").val()==1;
                },
                digits:true,
                minlength:0,
                maxlength:10
            },
            hotel_commission_method: {
                required: function(element){
                    return $("#hotel_enable").val()==1;
                },
                digits:true
            },
            hotel_commission: {
                required: function(element){
                    return $("#hotel_enable").val()==1;
                },
                digits:true,
                minlength:0,
                maxlength:10
            },
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#food-grocery-form").validate({
        rules: {
            country_area_id:{
                required: true,
                digits:true
            },
            segment_id: {
                required: true,
                digits: true
            },
            service_type_id:{
                required: true,
                digits: true
            },
            pick_up_fee:{
                required: function(element){
                    return $("#price_card_for").val()==1;
                },
                maxlength: 10,
                // digits: true
            },
            drop_off_fee:{
                required: function(element){
                    return $("#price_card_for").val()==1;
                },
                maxlength: 10,
                // digits: true
            },
            status:{
                required: true,
                digits:true
            },
            cancel_charges: {
                required: true,
                digits: true,
                min:0
            },
            cancel_amount:{
                required: function(element){
                    return $("#cancel_charges").val()==1;
                },
                // digits:true
            },
            cancel_time: {
                required: function(element){
                    return $("#cancel_charges").val()==1;
                },
                digits:true,
            },
            "distance_from[]": {
                required: true,
                minlength:1,
                min:0
            },
            "distance_to[]": {
                required: true,
                minlength:1,
                min:0
            },
            "cart_amount[]": {
                required: function(element){
                    return $("#price_card_for").val()==2;
                },
                minlength:1,
                min:0
            },
            "condition[]": {
                required: function(element){
                    return $("#price_card_for").val()==2;
                },
                minlength:1
            },
            "slab_amount[]": {
                minlength:1,
                maxlength:10,
                required: true,
                min:0
            },
            "detail_status[]": {
                required: true,
                minlength:1
            },
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#handyman-pricecard-form").validate({
        rules: {
            country_area_id:{
                required: true,
                digits:true
            },
            segment_id: {
                required: true,
                digits: true
            },
            price_type:{
                required: true,
                digits: true
            },
            minimum_booking_amount:{
                required: true,
                min:0
            },
            status:{
                required: true,
                digits:true
            },
            fixed_amount:{
                required: function(element){
                    return $("#price_type").val()!="";
                }
            },
            handyman_cancellation_charge:{
                min:0,
                maxlength:15
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#handyman-commission-form").validate({
        rules: {
            country_area_id:{
                required: true,
                digits:true
            },
            segment_id: {
                required: true,
                digits: true
            },
            commission_method: {
                required: true,
                digits: true
            },
            commission: {
                required: true,
                // digits: true,
                minlength:0,
                maxlength:10
            },
            status:{
                required: true,
                digits:true
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#promocode-form").validate({
        rules: {
            area:{
                required: true,
                digits:true
            },
            segment_id: {
                required: true,
                digits: true
            },
            promocode:{
                required: true,
                maxlength: 30
            },
            promo_code_value_type: {
                required: true,
                digits: true
            },
            promo_code_value: {
                required: true,
                digits: true,
                minlength:0,
                maxlength:10
            },
            promo_code_description: {
                required: true,
                maxlength: 100,
            },
            promo_code_validity: {
                required: true,
                digits: true,
            },
            start_date: {
                required: function(element){
                    return $("#promo_code_validity_custom").val()==2;
                }
            },
            end_date: {
                required: function(element){
                    return $("#promo_code_validity_custom").val()==2;
                }
            },
            applicable_for:{
                required: true,
                digits:true
            },
            promo_code_limit:{
                required: true,
                digits:true,
                minlength:0,
                maxlength:10
            },
            promo_code_limit_per_user:{
                required: true,
                digits:true,
                minlength:0,
                maxlength:10
            },
            order_minimum_amount:{
                required: true,
                digits:true,
                minlength:0,
                maxlength:10
            },
            promo_percentage_maximum_discount:{
                required: function(element){
                    return $("#promo_code_value_type").val()==2;
                },
                digits:true
            },
            promo_code_name:{
                required: true,
                maxlength: 30
            },
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#rental-package-form").validate({
        rules: {
            name:{
                required: true,
                maxlength:30,
                pattern:pattner_string
            },
            service_type_id: {
                required: true,
                digits: true
            },
            description:{
                required: true,
                minlength: 0,
                maxlength: 30
            },
            terms_conditions: {
                required: true,
                minlength: 0,
                maxlength: 30
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#service-time-slot-form").validate({
        rules: {
            service_type_id: {
                required: true,
                digits: true
            },
            segment_id: {
                required: true,
                digits: true
            },
            max_slot:{
                required: true,
                digits: true,
                min:1,
                max:24
            },
            terms_conditions: {
                required: true,
                digits: true,
            },
            start_time:{
                required: true,
            },
            end_time:{
                required: true,
            },
            status:{
                required: true,
                digits: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#business-segment-form").validate({
        rules: {
            full_name:{
                required: true,
                maxlength: 60,
                pattern:pattner_string
            },
            email: {
                required: true,
                email:true
            },
            password: {
                required: true,
                minlength: 8,
                maxlength: 40,
            },
            country_id:{
                required: true,
                digits: true
            },
            phone_number: {
                required: true
            },
            is_popular:{
                required: true,
            },
            landmark:{
                required: true,
            },
            business_logo:{
                required: function(element){
                    return $("#id").val()=="";
                },
                accept: "image/*"
            },
            status:{
                required: true,
            },
            order_request_receiver:{
                required: true,
            },
            rating:{
                required: true,
                min:0,
                max:5,
                step:0.1
            },
            commission_method:{
                required: true,
            },
            commission:{
                required: true,
                min:0,
                step:0.01
            },
            "open_time[]":{
                required: true,
                minlength:1,
            },
            "close_time[]":{
                required: true,
                minlength:1,
            },
            bank_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            account_holder_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            account_number: {
                required: true,
                maxlength: 30,
                minlength: 3,
            },
            bank_code: {
                required: true,
                minlength: 3,
                maxlength: 50,
            },
            delivery_time: {
                required: function(element){
                    return $("#slug").val()=="FOOD";
                }
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#delivery-product-form").validate({
        rules: {
            product_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            weight_unit:{
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#driver-register").validate({
        rules: {
            country: {
                required: function(element){
                    return $("#driver_id").val()=="";
                },
                digits: true,
            },
            area:{
                required: function(element){
                    return $("#driver_id").val()=="";
                },
                digits: true,
            },
            phone: {
                required: true,
            },
            first_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            last_name: {
                maxlength: 50,
                pattern:pattner_string
            },
            image:{
                required: function(element){
                    return $("#driver_id").val()=="";
                },
                accept: "image/*"
            },
            email: {
                required:function(element){
                    return $("#driver_email_enable").val()==1;
                },
                email:true
            },
            password: {
                required: function(element){
                    return $("#driver_id").val()=="";
                },
                minlength: 8,
                maxlength: 40,
            },
            password_confirmation: {
                required: function(element){
                    return $("#driver_id").val()=="";
                },
                minlength: 8,
                maxlength: 40,
                equalTo: "#password"
            },
            segment_group_id: {
                required: function(element){
                    return $("#single_group").val()!="";
                },
            },
            driver_gender: {
                required: function(element){
                    return $("#driver_gender_enable").val()==1;
                },
            },
            dob: {
                required: function(element){
                    return $("#stripe_connect_enable").val()==1;
                },
            },
            smoker_type: {
                required: function(element){
                    return $("#smoker_enable").val()==1;
                },
            },
            bank_name: {
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            account_holder_name: {
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            account_number: {
                digits: true,
                maxlength: 30,
                minlength: 3,
            },
            online_transaction: {
                minlength: 3,
                maxlength: 50,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#driver-vehicle-form").validate({
        rules: {
            vehicle_type_id: {
                required: true,
                digits: true,
            },
            vehicle_make_id:{
                required: true,
            },
            vehicle_model_id: {
                required: true,
            },
            vehicle_number: {
                required: true,
                pattern:pattner_string
            },
            car_image:{
                required: function(element){
                    return $("#vehicle_id").val()=="";
                },
                accept: "image/*"
            },
            // car_number_plate_image: {
            //     required: function(element){
            //         return $("#vehicle_id").val()=="";
            //     },
            //     accept: "image/*"
            // },
            vehicle_color: {
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });
    $("#driver-bus-form").validate({
        rules: {
            vehicle_type_id: {
                required: true,
                digits: true,
            },
            vehicle_make_id:{
                required: true,
            },
            vehicle_model_id: {
                required: true,
            },
            vehicle_number: {
                required: true,
                // pattern:pattner_string
            },
            car_image:{
                required: function(element){
                    return $("#vehicle_id").val()=="";
                },
                accept: "image/*"
            },
            car_number_plate_image: {
                required: function(element){
                    return $("#vehicle_id").val()=="";
                },
                accept: "image/*"
            },
            vehicle_color: {
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#user-register").validate({
        rules: {
            rider_type: {
                required: true,
            },
            first_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            last_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            corporate_id:{
                required: function(element){
                    return $("#rider_type").val()==1;
                },
                digits: true,
            },
            corporate_email:{
                required: function(element){
                    return $("#rider_type").val()==1;
                },
                email: true,
            },
            country: {
                required: true,
            },
            user_phone: {
                required: true,
            },
            user_email: {
                required: true,
                email: true,
            },
            profile:{
                required: true,
                accept: "image/*"
            },
            user_gender: {
                required: function(element){
                    return $("#user_gender_enable").val()==1;
                },
            },
            smoker_type: {
                required: function(element){
                    return $("#smoker_enable").val()==1;
                },
            },
            password: {
                required:true,
                minlength: 8,
                maxlength: 40,

            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#user-register-edit").validate({
        rules: {
            first_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            last_name: {
                // required: true,
                // minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            user_phone: {
                //required: true,
            },
            user_email: {
               // required: true,
                // email: true,
            },
            profile:{
                accept: "image/*"
            },
            user_gender: {
                required: function(element){
                    return $("#user_gender_enable").val()==1;
                },
            },
            smoker_type: {
                required: function(element){
                    return $("#smoker_enable").val()==1;
                },
            },
            password: {
                required:function(element){
                    return $("#edit_password").is(":checked");
                },
                minlength: 8,
                maxlength: 40,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#sos-form").validate({
        rules: {
            name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            application: {
                required: function(element){
                    return $("#id").val()=="";
                },
            },
            number: {
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#cms-form").validate({
        rules: {
            page: {
                required: function(element){
                    return $("#id").val()=="";
                },
            },
            country: {
                required: function(element){
                    return $("#id").val()=="";
                },
            },
            application: {
                required: function(element){
                    return $("#id").val()=="";
                },
            },
            title: {
                required: true,
                minlength: 3,
                maxlength: 50,
                // pattern:pattner_string
            },
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#promotion-all-form").validate({
        rules: {
            application: {
                required: true,
            },
            title: {
                required: true,
                minlength: 3,
                maxlength: 50,
            },
            message: {
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#promotion-area-form").validate({
        rules: {
            area: {
                required: true,
            },
            title: {
                required: true,
                minlength: 3,
                maxlength: 50,
            },
            message: {
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#promotion-update-form").validate({
        rules: {
            title: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            message: {
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#wallet-recharge-form").validate({
        rules: {
            application: {
                required: true,
            },
            receiver_id: {
                required: true,
                digits:true
            },
            payment_method: {
                required: true,
            },
            receipt_number: {
                required: true,
            },
            transaction_type: {
                required: true,
            },
            amount: {
                required: true,
                // digits:true,
                min:0
            },
            description: {
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#subadmin-form").validate({
        rules: {
            first_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            last_name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            phone_number: {
                required: true,
            },
            email: {
                required: true,
                email: true,
            },
            password: {
                required: true,
            },
            admin_type: {
                required: true,
            },
            "area_list[]": {
                required: function(element){
                    return $("#admin_type").val()==2;
                },
            },
            role_id: {
                required: true,
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#role-form").validate({
        rules: {
            name: {
                required: true,
                minlength: 3,
                maxlength: 50,
                pattern:pattner_string
            },
            description: {
                required: true,
            },
            "permission[]":{
                required: true,
                minlength: 1
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#cancel-reason-form").validate({
        rules: {
            reason_for: {
                required: true,
                digits:true
            },
            segment_id: {
                required: true,
                digits:true
            },
            reason: {
                required: true,
                maxlength:100
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#cancel-reason-edit-form").validate({
        rules: {
            segment_id: {
                required: true,
                digits:true
            },
            reason: {
                required: true,
                maxlength:100
            }
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });

    $("#handyman-category-form").validate({
        rules: {
            segment_id: {
                required: true,
                digits:true
            },
            category: {
                required: true,
                minlength: 3,
                maxlength: 30,
                pattern:pattner_string
            },
            status: {
                required: true,
            },
            "service_types[]": {
                required: true,
                minlength: 1
            },
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });
});
