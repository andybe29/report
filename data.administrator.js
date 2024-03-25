$(function() {
    settings.id    = 0;
    settings.month = (new Date).getMonth() + 1;
    settings.year  = (new Date).getFullYear();

    settings.managers = [];
    $.each($('#data-manager').find('option'), function() {
        let $this = $(this), id = $this.data('id');
        if (id) {
            settings.managers[id] = $this.text();
        }
    });

    $('#data-period-month-year').find('option:last').prop('selected', true);
});

function showData() {
    if (settings.loading) return;

    $('#data-report').prop('hidden', true);
    $('#data-table').prop('hidden', false);

    let $period = $('#data-period-month-year').find('option:selected');

    let $body = $('#data-table-body'), $foot = $('#data-table-foot');
    $body.empty(); $foot.empty();

    let cols = $body.siblings('thead').find('th').length;

    let post = Object.create(null);
    post.method  = 'showData';
    post.month   = $period.data('month');
    post.year    = $period.data('year');
    post.manager = $('#data-manager').find('option:selected').data('id');

    settings.loading = true;

    $.ajax({
        data    : post,
        url     : 'ajax.php',
        success : function(data) {
            if (data.success) {
                if (data.hasOwnProperty('values')) {

                    data.values.forEach((item, i) => {
                        let h = [];
                        h.push('<tr id="' + item.id + '">');
                        h.push('<td>' + (i + 1) + '</td>');
                        h.push('<td>' + moment(1000 * item.date).format('DD.MM.YYYY') + '</td>');
                        h.push('<td>' + settings.managers[item.manager] + '</td>');
                        h.push('<td>' + data.places[item.place] + '</td>');
                        h.push('<td>' + (item.amount / 100).currency() + '</td>');
                        h.push('</tr>');

                        $body.append(h.join(''));

                        let $this = $('#' + item.id);

                        $this.data('date', item.date);
                        $this.data('manager', item.manager);
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

function showReport() {
    if (settings.loading) return;

    $('#data-report').prop('hidden', false);
    $('#data-table').prop('hidden', true);

    let $period = $('#data-period-month-year').find('option:selected');

    let $body = $('#data-report-body'), $foot = $('#data-report-foot');
    $body.empty(); $foot.empty();

    let cols = $body.siblings('thead').find('th').length;

    let post = Object.create(null);
    post.method  = 'showReport';
    post.month   = $period.data('month');
    post.year    = $period.data('year');

    settings.loading = true;

    $.ajax({
        data    : post,
        url     : 'ajax.php',
        success : function(data) {
            if (data.success) {
                if (data.hasOwnProperty('values')) {
                    let amount = 0;

                    data.values.forEach((item, i) => {
                        amount += item.amount;

                        let h = [];
                        h.push('<tr>');
                        h.push('<td>' + (i + 1) + '</td>');
                        h.push('<td>' + settings.managers[item.manager] + '</td>');
                        h.push('<td>' + (item.amount / 100).currency() + '</td>');
                        h.push('</tr>');

                        $body.append(h.join(''));

                        let $this = $body.find('tr:last');
                        $this.find('td').addClass('text-nowrap');
                        $this.find('td:last').addClass('text-right');
                    });

                    let h = [];
                    h.push('<tr class="table-info">');
                    h.push('<td colspan="' + (cols - 1) + '">итого</td>');
                    h.push('<td>' + (amount / 100).currency() + '</td>');
                    h.push('</tr>');

                    $foot.html(h.join('')).find('td').addClass('font-weight-bold').addClass('text-nowrap').addClass('text-right');
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