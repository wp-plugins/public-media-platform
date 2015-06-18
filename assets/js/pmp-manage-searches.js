var PMP = PMP || {};

(function() {
    var $ = jQuery;

    PMP.instances = PMP.instances || {};

    PMP.DeleteSavedSearchModal = PMP.Modal.extend({
        action: 'pmp_delete_saved_query',

        content: 'Are you sure you want to delete this saved search query?',

        actions: {
            'Yes': 'deleteQuery',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.search_id = options.search_id;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        deleteQuery: function() {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var self = this,
                data = {
                    action: this.action,
                    security: PMP.ajax_nonce,
                    data: JSON.stringify({
                        search_id: this.search_id
                    })
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(data) {
                    self.hideSpinner();
                    self.close();
                    $('div[data-search-id="' + self.search_id + '"]').remove();
                },
                error: function() {
                    self.hideSpinner();
                    alert('Something went wrong. Please try again.');
                }
            };

            this.showSpinner();
            this.ongoing = $.ajax(opts);
            return this.ongoing;
        }
    });

    $(document).ready(function() {
        $('a.pmp-delete-saved-search').click(function() {
            var search_id = $(this).data('search-id');
            PMP.instances.delete_saved_search_modal = new PMP.DeleteSavedSearchModal({
                search_id: search_id
            });
            PMP.instances.delete_saved_search_modal.render();
        });
    });
})();
