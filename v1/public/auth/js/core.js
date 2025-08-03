"use strict"

$(window).on("load", function () {
    $('.btn-forget').on('click', function (e) {
        e.preventDefault();
        var inputField = $(this).closest('form').find('input');
        if (inputField.attr('required') && inputField.val()) {
            $('.error-message').remove();
            $('.form-items', '.form-content').addClass('hide-it');
            $('.form-sent', '.form-content').addClass('show-it');
        } else {
            $('.error-message').remove();
            $('<small class="error-message">Please fill the field.</small>').insertAfter(inputField);
        }

    });

    $('.btn-tab-next').on('click', function (e) {
        e.preventDefault();
        $('.nav-tabs .nav-item > .active').parent().next('li').find('a').trigger('click');
    });
    $('.custom-file input[type="file"]').on('change', function () {
        var filename = $(this).val().split('\\').pop();
        $(this).next().text(filename);
    });
});

// document.onkeydown = function (e) {
//     return 123 != event.keyCode && !(e.ctrlKey && e.shiftKey && e.keyCode == "I".charCodeAt(0)
//         || e.ctrlKey && e.shiftKey && e.keyCode == "C".charCodeAt(0)
//         || e.ctrlKey && e.shiftKey && e.keyCode == "J".charCodeAt(0)
//         || e.ctrlKey && e.keyCode == "U".charCodeAt(0)) && void 0
// },
//     $(document).on("contextmenu", function (e) { return !1 }); var Tawk_API = Tawk_API
//         || {}, Tawk_LoadStart = new Date; !function () {
//             var e = document.createElement("script"),
//                 t = document.getElementsByTagName("script")[0];
//             e.async = !0,
//                 e.src = "https://embed.tawk.to/5c6d4867f324050cfe342c69/default",
//                 e.charset = "UTF-8", e.setAttribute("crossorigin", "*"),
//                 t.parentNode.insertBefore(e, t)
//         }();