var PMP = PMP || {};

(function() {
    var $ = jQuery;

    // Models & Collections
    PMP.MultiCollection = PMP.DocCollection.extend({
        search: function(query) {
            query = _.defaults(query || {}, {
                writeable: 'true',
                profile: PMP.profile,
                limit: 100
            });
            PMP.DocCollection.prototype.search.apply(this, [query, ]);
        }
    });

    // Views
    PMP.CollectionList = PMP.BaseView.extend({

        events: {
            'click .pmp-collection-modify': 'modify_collection',
            'click .pmp-collection-default': 'set_default',
        },

        initialize: function(options) {
            options = options || {};
            this.collection = options.collection || new PMP.MultiCollection();
            this.collection.on('reset', this.render.bind(this));
            this.collection.on('error', this.renderError.bind(this));

            this.showSpinner();
            if (!options.collection)
                this.collection.search();

            PMP.BaseView.prototype.initialize.apply(this, arguments);
        },

        renderError: function(response) {
            this.hideSpinner();
            this.$el.find('#pmp-collection-list').html(response.responseJSON.message);
        },

        render: function() {
            var self = this,
                template = _.template($('#pmp-collection-items-tmpl').html());

            this.$el.find('#pmp-collection-list').html('');
            this.$el.find('#pmp-collection-list').append(template({
                collection: this.collection
            }));
            this.hideSpinner();
            return this;
        },

        modify_collection: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                collection = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.collection_modify_modal) {
                this.collection_modify_modal = new PMP.ModifyCollectionModal({
                    collection: collection
                });
            } else
                this.collection_modify_modal.collection = collection;

            this.collection_modify_modal.render();
        },

        set_default: function(e) {
            var target = e.currentTarget,
                guid = $(target).data('guid'),
                collection = this.collection.find(function(g) {
                    return g.get('attributes').guid == guid;
                });

            if (!this.collection_default_modal)
                this.collection_default_modal = new PMP.DefaultCollectionModal({ collection: collection});
            else
                this.collection_default_modal.collection = collection;

            this.collection_default_modal.render();
        }
    });

    PMP.BaseCollectionModal = PMP.Modal.extend({
        className: 'pmp-collection-modal',

        saveCollection: function() {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var valid = this.validate();
            if (!valid) {
                alert('Please complete all required fields before submitting.');
                return false;
            }

            var serialized = this.$el.find('form').serializeArray();

            var collection = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    collection[val.name] = val.value;
            });

            var self = this,
                data = {
                    action: this.action,
                    security: PMP.ajax_nonce,
                    collection: JSON.stringify({ attributes: collection }),
                    profile: PMP.profile
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(data) {
                    self.hideSpinner();
                    self.close();
                    PMP.instances.collection_list.showSpinner();
                    PMP.instances.collection_list.collection.search();
                },
                error: function() {
                    self.hideSpinner();
                    alert('Something went wrong. Please try again.');
                }
            };

            this.showSpinner();
            this.ongoing = $.ajax(opts);
            return this.ongoing;
        },

        validate: function() {
            var inputs = this.$el.find('form input'),
                valid = true;

            _.each(inputs, function(v, i) {
                if (!v.validity.valid)
                    valid = false;
            });

            return valid;
        }
    });

    PMP.CreateCollectionModal = PMP.BaseCollectionModal.extend({
        content: _.template($('#pmp-create-new-collection-form-tmpl').html(), {}),

        action: 'pmp_create_collection',

        actions: {
            'Create': 'saveCollection',
            'Cancel': 'close'
        }
    });

    PMP.ModifyCollectionModal = PMP.BaseCollectionModal.extend({
        action: 'pmp_modify_collection',

        actions: {
            'Save': 'saveCollection',
            'Cancel': 'close'
        },

        initialize: function(options) {
            this.collection = options.collection;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-modify-collection-form-tmpl').html());
            this.content = template({ collection: this.collection });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    PMP.DefaultCollectionModal = PMP.BaseCollectionModal.extend({
        action: 'pmp_default_collection',

        actions: {
            'Yes': 'saveCollection',
            'Cancel': 'close'
        },

        saveCollection: function() {
            PMP.default_collection = this.collection.get('attributes').guid;
            PMP.BaseCollectionModal.prototype.saveCollection.apply(this, arguments);
        },

        initialize: function(options) {
            this.collection = options.collection;
            PMP.Modal.prototype.initialize.apply(this, arguments);
        },

        render: function() {
            var template = _.template($('#pmp-default-collection-form-tmpl').html());
            this.content = template({ collection: this.collection });
            PMP.Modal.prototype.render.apply(this, arguments);
        }
    });

    $(document).ready(function() {
        PMP.instances = {};

        PMP.instances.collection_list = new PMP.CollectionList({
            el: $('#pmp-collection-container'),
            collection: new PMP.MultiCollection((PMP.pmp_collection)? PMP.pmp_collection.items:[])
        });

        PMP.instances.collection_list.render();

        $('#pmp-create-collection').click(function() {
            if (!PMP.instances.collection_create_modal)
                PMP.instances.collection_create_modal = new PMP.CreateCollectionModal();
            PMP.instances.collection_create_modal.render();
        });
    });
})();
