$(function() {
    $('#login').focus();
});

function login() {
    if (settings.loading) return;

    let post = Object.create(null);
    post.method = 'login';
    ['passw', 'login'].forEach(key => {
        let $this = $('#' + key), value = $this.val().trim();

        $this.val(value);
        $this.removeClass('is-invalid').siblings('.invalid-feedback').remove();

        if (value.length) {
            post[key] = value;
        } else {
            $this.focus().addClass('is-invalid').after('<div class="invalid-feedback"> не указан ' + key + '</div>');
        }
    });

    if ($.find('div.invalid-feedback').length) return;

    settings.loading = true;

    $.ajax({
        data    : post,
        success : function(data) {
            if (data.success) {
                location.href = 'data.php';
            } else {
                $('#login').addClass('is-invalid').after('<div class="invalid-feedback">' + data.error + '</div>');
            }
        }
    });
}