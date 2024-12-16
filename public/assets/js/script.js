//$(document).ready(function () {
    function initValidate(selector) {
        $(selector).validate({
            errorElement: "div",
            errorPlacement: function (error, element) {
                // Remove any existing error messages within the form-group
                element.closest(".form-group").find(".invalid-feedback").remove();

                error.addClass("invalid-feedback");
                // Append error message only if it doesn't already exist
                if (element.closest(".form-group").find(".invalid-feedback").length === 0) {
                    element.closest(".form-group").append(error);
                }
            },
            highlight: function (element, errorClass, validClass) {
                $(element).addClass("is-invalid");
                $(element).closest(".form-group").addClass("has-error");
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
                $(element).closest(".form-group").removeClass("has-error");
            }
        });
    }

    /*------------------- form submit ajax new --------------------*/

    function getCsrfToken() {
        return $.get("/csrf-token"); // An endpoint that returns a new CSRF token
    }
    
    function ajax_form_submit(e, form, callback) {

        e.preventDefault();
        
        if (form.valid()) {
            var btn = $(form).find('button[type="submit"]');
            var btn_text = $(btn).html();
            $(btn).html('please wait... <i class="las la-spinner la-spin"></i>');
            $(btn).css("opacity", "0.7");
            $(btn).css("pointer-events", "none");
            var action = form.attr("action");
            var data = new FormData(form[0]); // Corrected to form[0] to get the raw DOM element
    
            getCsrfToken()
                .done(function (response) {
                    var token = response.token;
                    data.append("_token", token);
    
                    $.ajax({
                        type: "POST",
                        url: action,
                        processData: false,
                        contentType: false,
                        dataType: "json",
                        data: data,
                        success: function (response) {
                            resetButton(btn, btn_text);
                            if (response.status === "success") {
                                AIZ.plugins.notify('success', response.message);
                                callback(response);
                            } else {
                                var errors = "";
                                if (Array.isArray(response.message)) {
                                    $.each(response.message, function (key, msg) {
                                        errors += "<div>" + (key + 1) + ". " + msg + "</div>";
                                    });
                                    // AIZ.plugins.notify('danger', errors); // Display all errors
                                } else {
                                    AIZ.plugins.notify('danger', response.message); // If it's not an array, show the single message
                                }
                            }
                        },
                        error: function (xhr, status, error) {
                            resetButton(btn, btn_text);
                            AIZ.plugins.notify('danger', error);
                        },
                    });
                })
                .fail(function () {
                    resetButton(btn, btn_text);
                    AIZ.plugins.notify('danger', 'Failed to retrieve CSRF token');
                });
        } else {
            AIZ.plugins.notify('danger', 'Please make sure to fill all the necessary fields');
            resetButton($(form).find('button[type="submit"]'), btn_text);
        }
    }
    
    function resetButton(btn, btn_text) {
        $(btn).html(btn_text);
        $(btn).css("opacity", "1");
        $(btn).css("pointer-events", "inherit");
    }

    /*------------------- form submit ajax new --------------------*/
//});