(function() {
    var $ = jQuery;

    $('a#pmp_client_secret_reset').click(function() {
        var tmpl = _.template($('#pmp_client_secret_input_tmpl').html());
        $(this).parent().append(tmpl());
        $(this).remove();
    });
})();
