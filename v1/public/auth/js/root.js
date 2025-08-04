function getTokenAndRetry(route) {
    return $.get(route, function (response) {
        $('meta[name="csrf-token"]').attr('content', response.token);
        // console.log(response.token);
    });
}

var toastTimeout;

function showSuccessMessage(message, time = 1) {
    clearTimeout(toastTimeout);

    var toastHTML = `<div class="toast fade hide" data-delay="3000">
    <div class="toast-header">
        <i class="bi bi-check-circle m-r-5"></i>
        <strong class="mr-auto">Success</strong>
        <small class="text-muted float-right">` + time + ` second(s) ago</small>
        <button type="button" class="ml-2 close" data-dismiss="toast" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="toast-body text-success">` +
        message +
        `</div>
    </div>`

    $('#notification-toast').html(toastHTML)
    $('#notification-toast .toast').toast('show');
    toastTimeout = setTimeout(function () {
        $('#notification-toast .toast:first-child').remove();
    }, 3000);
}


function showErrorMessage(message, time = 1) {
    clearTimeout(toastTimeout);

    var toastHTML = `<div class="toast fade hide" data-delay="3000">
    <div class="toast-header">
        <i class="bi bi-exclamation-triangle m-r-5"></i>
        <strong class="mr-auto">Error</strong>
        <small class="text-muted float-right">` + time + ` second(s) ago</small>
        <button type="button" class="ml-2 close btn-close" data-dismiss="toast" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="toast-body text-danger">` +
        message +
        `</div>
    </div>`

    $('#notification-toast').html(toastHTML)
    $('#notification-toast .toast').toast('show');
    toastTimeout = setTimeout(function () {
        $('#notification-toast .toast:first-child').remove();
    }, 3000);
}
