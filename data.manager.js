$(function() {
    settings.id    = 0;
    settings.month = (new Date).getMonth() + 1;
    settings.year  = (new Date).getFullYear();

    $('#data-period-month-year').find('option:last').prop('selected', true);
});

function popupData(id) {
    if (settings.loading || settings.MANAGER != settings.type) return;

    let $popup = $('#data-popup');
    $popup.find('input:text, select').removeClass('is-invalid').siblings('.invalid-feedback').remove();
    $popup.find('h5.modal-title').text((id ? 'редактировать' : 'добавить') + ' запись');

    $('#data-popup-place').prop('disabled', (id > 0)).find('option:first').prop('selected', true);
    $('#data-popup-date').val(moment(Date.now()).format('DD.MM.YYYY'));
    $('#data-popup-amount').val('');

    settings.error = null;
    settings.id    = id;

    if (settings.id) {
        let $this = $('#' + settings.id);
        $('#data-popup-place').val($this.data('place'));
        $('#data-popup-date').val(moment(1000 * $this.data('date')).format('DD.MM.YYYY'));
        $('#data-popup-amount').val(($this.data('amount') / 100).currency().replace(' ', ''));
    }

    $popup.modal('show');
    window.setTimeout(function() {  }, 500);
}

function saveData() {
    if (settings.error || settings.loading || settings.MANAGER != settings.type) return;

    let $popup = $('#data-popup');
    $popup.find('input:text').removeClass('is-invalid').siblings('.invalid-feedback').remove();

    let post = Object.create(null);

    let keys = settings.id ? ['amount'] : ['amount', 'date', 'place'];

    keys.forEach(key => {
        settings.error = null;
        let $this = $('#data-popup-' + key), value = $this.val().trim(), regex;

        if ('amount' == key) {
            regex = new RegExp(/^\d+\.{0,1}\d{0,2}$/g);
            value = value.replace(/,/g, '.').replace(/[^\d.]/g, '');
            $this.val(value);

            if (value.length == 0) {
                settings.error = 'не указано значение';
            } else if (!regex.test(value)) {
                settings.error = 'неверный формат';
            } else {
                post[key] = Math.round(100 * value);
                $this.val((post[key] / 100).currency());
            }
        } else if ('date' == key) {
            regex = new RegExp(/^[\d]{2}.[\d]{2}.[\d]{4}$/);
            value = value.replace(/[^\d.]/g, '');

            if (value.length == 0) {
                settings.error = 'не указано значение';
            } else if (!regex.test(value)) {
                settings.error = 'неверный формат';
            } else {
                value = moment(value, 'DD.MM.YYYY').toDate() / 1000;
                if (value > moment(Date.now()) / 1000) {
                    settings.error = 'неверное значение';
                } else {
                    post[key] = value;
                }
            }
        } else if ('place' == key) {
            post[key] = value.toInt();
        }

        if (settings.error) {
            $this.focus().addClass('is-invalid').after('<div class="invalid-feedback">' + settings.error + '</div>');
        }
    });

    if ($popup.find('div.invalid-feedback').length) return;

    if (settings.id) {
        post.method = 'updateData';
        post.id     = settings.id;
    } else {
        post.method = 'createData';
    }

    settings.loading = true;

    $.ajax({
        data    : post,
        success : function(data) {
            if (data.success) {
                settings.loading = false;

                if (post.method == 'createData') {
                    let params = Object.create(null);
                    params.month = (new Date(post.date.toDate())).getMonth() + 1;
                    params.year  = (new Date(post.date.toDate())).getFullYear();

                    $('#data-period-month-year')
                    .find('option[data-month="' + params.month + '"][data-year="' + params.year + '"]')
                    .prop('selected', true);

                    showData();
                } else if (post.method == 'updateData') {
                    let $this = $('#' + post.id);
                    $this.data('amount', post.amount);
                    $this.find('a[rel="amount"]').text((post.amount / 100).currency());
                    recalc();
                }

                $popup.modal('hide');
            } else {
                $('#data-popup-place').focus().addClass('is-invalid')
                .after('<div class="invalid-feedback">' + data.error + '</div>');
            }
        }
    });
}

function showData() {
    if (settings.loading) return;

    let $period = $('#data-period-month-year').find('option:selected');

    let $body = $('#data-table-body'), $foot = $('#data-table-foot');
    $body.empty(); $foot.empty();

    let cols = $body.siblings('thead').find('th').length;

    let post = Object.create(null);
    post.method = 'showData';
    post.month  = $period.data('month');
    post.year   = $period.data('year');

    settings.loading = true;

    $.ajax({
        data    : post,
        url     : 'ajax.php',
        success : function(data) {
            if (data.success) {
                if (data.hasOwnProperty('values')) {

                    data.values.forEach((item, i) => {
                        let amount = (item.amount / 100).currency();

                        let h = [];
                        h.push('<tr id="' + item.id + '">');
                        h.push('<td>' + (i + 1) + '</td>');
                        h.push('<td>' + moment(1000 * item.date).format('DD.MM.YYYY') + '</td>');
                        h.push('<td>' + data.places[item.place] + '</td>');
                        if (post.year == settings.year && post.month == settings.month) {
                            h.push('<td><a href="javascript:void(popupData(' + item.id + '))" rel="amount">' + amount + '</a></td>');
                        } else {
                            h.push('<td>' + amount + '</td>');
                        }
                        h.push('</tr>');

                        $body.append(h.join(''));

                        let $this = $('#' + item.id);

                        $this.data('date', item.date);
                        $this.data('place', item.place);
                        $this.data('amount', item.amount);

                        $this.find('td').addClass('text-nowrap');
                        $this.find('td:last').addClass('text-right');
                    });

                    let h = [];
                    h.push('<tr class="table-info">');
                    h.push('<td colspan="' + (cols - 1) + '">итого</td>');
                    h.push('<td rel="amount"></td>');
                    h.push('</tr>');

                    $foot.html(h.join('')).find('td').addClass('font-weight-bold').addClass('text-nowrap').addClass('text-right');

                    recalc();
                } else {
                    $body.html('<tr class="table-warning"><td colspan="' + cols + '">нет данных за выбранный период</td></tr>');
                    $body.find('td').addClass('font-weight-bold').addClass('text-center');
                }
            } else {
                $body.html('<tr class="table-danger"><td colspan="' + cols + '">' + data.error + '</td></tr>');
                $body.find('td').addClass('font-weight-bold').addClass('text-center');
            }
        }
    });
}

function recalc() {
    let amount = 0;

    $.each($('#data-table-body').find('tr'), function() {
        amount += $(this).data('amount');
    });

    $('#data-table-foot').find('td[rel="amount"]').text((amount / 100).currency());
}