let settings = Object.create(null);
settings.error   = false;
settings.loading = false;

$(function() {
    $.each($('[rel="settings"]:hidden'), function() {
        let key = $(this).data('key'), value = $(this).data('value');

        if (typeof value == 'object') {
            settings[key] = Object.create(null);
            for (let k in value) settings[key][k] = value[k];
        } else {
            settings[key] = value;
        }
    });

    settings.DATE_REGEXP = new RegExp(/^[\d]{2}.[\d]{2}.[\d]{4}$/);

    $('.form-control[required]').css({'background-color': '#fcf8e3'});
    $('input[readonly]').css({'background-color': '#fff'});

    $.ajaxSetup({
        url         : 'ajax.php',
        type        : 'post',
        dataType    : 'json',
        cache       : false,
        timeout     : 600000,
        beforeSend  : function() { $('body').addClass('loading'); },
        complete    : function() {
            settings.loading = false;
            $('body').removeClass('loading');
        },
        error       : function(xhr, status) {
            if (status == 'timeout') {
                alert('Превышено время ожидания ответа');
            }
        }
    });
});

function logout() {
    if (settings.loading) return;

    let post = Object.create(null);
    post.method = 'logout';

    settings.loading = true;

    $.ajax({
        data    : post,
        url     : 'ajax.php',
        success : function(data) {
            if (data.success) {
                location.href = 'login.php';
            } else {
                console.log(data.error);
            }
        }
    });
}

Number.prototype.currency = function() {
    var n = (this + '').replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+n) ? 0 : +n;
    var prec = !isFinite(+2) ? 0 : Math.abs(2), sep = ' ', dec = '.', s = '';
    var toFixedFix = function (n, prec) {
        var k = Math.pow(10, prec);
        return '' + Math.round(n * k) / k;
    };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1).join('0');
    }
    return s.join(dec).replace(/-/, '- ');
}
Number.prototype.toDate = function() {
    return new Date(1000 * this);
}
String.prototype.toInt = function() {
    return parseInt(this, 10);
}