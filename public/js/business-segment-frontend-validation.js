$(document).ready(function () {
    // Not allow special characters
    var pattner_string = /^[^\s!"#$%&'()*+,./:;<=>?@\[\]^`{|}~]+( [^\s!"#$%&'()*+,./:;<=>?@\[\]^`{|}~]+)*$/;

    // Add custom method for file extension validation
    $.validator.addMethod("excelFile", function(value, element) {
        return this.optional(element) || /\.(xls|xlsx)$/i.test(value);
    }, "Please select a valid Excel file (xls or xlsx).");

    $("#bulk-import-product").validate({
        rules: {
            import_file: {
                required: true,
                excelFile: true
            }
        },
        messages:{},
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid").next('label').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid").next('label').removeClass('error');
        }
    });
});
