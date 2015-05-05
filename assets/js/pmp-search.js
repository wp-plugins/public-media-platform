(function() {
    var $ = jQuery,
        Doc = PMP.Doc,
        DocCollection = PMP.DocCollection,
        BaseView = PMP.BaseView,
        Modal = PMP.Modal;

    // Views
    var SearchForm = BaseView.extend({
        el: '#pmp-search-form',

        events: {
            "submit": "submit",
            "click #pmp-show-advanced a": "advanced",
            "change input": "change",
            "change select": "change"
        },

        initialize: function() {
            this.docs = new DocCollection();
            this.results = new ResultsList({ collection: this.docs });
            this.docs.on('reset', this.hideSpinner.bind(this));
            this.docs.on('error', this.hideSpinner.bind(this));
        },

        submit: function() {
            var serialized = this.$el.serializeArray();

            var query = {};
            _.each(serialized, function(val, idx) {
                if (val.value !== '')
                    query[val.name] = val.value;
            });

            this.showSpinner();
            this.docs.search(query);

            return false;
        },

        advanced: function(e) {
            var target = $(e.currentTarget);
            target.remove();
            this.$el.find('#pmp-advanced-search').show();
            return false;
        },

        change: function(e) {
            var target = $(e.currentTarget);

            if (target.attr('name') == 'profile') {
                if (target.val() !== '' && target.val() !== 'story') {
                    this.$el.find('#pmp-content-has-search').hide();
                    this.$el.find('#pmp-content-has-search select option').prop('selected', false);
                } else {
                    this.$el.find('#pmp-content-has-search').show();
                }
            }

            return false;
        }
    });

    var ResultsList = Backbone.View.extend({
        el: '#pmp-search-results',

        initialize: function(options) {
            this.collection = (typeof options.collection != 'undefined')? options.collection : new DocCollection();

            this.collection.attributes.on('change', this.renderPagingation.bind(this));
            this.collection.on('reset', this.render.bind(this));

            this.collection.on('error', this.renderError.bind(this));
        },

        renderError: function(response) {
            if (this.pagination) {
                this.pagination.remove();
                delete(this.pagination);
            }
            this.$el.html('');
            this.$el.append('<p class="error">' + response.responseJSON.message + '</p>');
        },

        render: function() {
            var self = this;

            this.$el.find('p.error').remove();
            this.$el.find('.pmp-search-result').remove();

            var template = _.template($('#pmp-search-result-tmpl').html());

            this.collection.each(function(model, idx) {
                var image = (model.getBestThumbnail())? model.getBestThumbnail().href : null;

                if (!image)
                    image = (model.getFirstEnclosure())? model.getFirstEnclosure().href : null;

                // HACK: get a MUCH smaller thumbnail for NPR images
                if (model.getCreatorAlias() == 'NPR') {
                    if (image && image.match(/media\.npr\.org/)) {
                        image = image.replace(/\.jpg$/, '-s200-c85.jpg');
                    }
                }

                var tmpl_vars = _.extend(model.toJSON().attributes, {
                        image: image,
                        creator: model.getCreatorAlias()
                    }),
                    res = $(template(tmpl_vars));

                new ResultActions({
                    el: res.find('.pmp-result-actions'),
                    model: model
                });

                self.$el.append(res);
            });

            return this;
        },

        renderPagingation: function() {
            if (!this.pagination) {
                this.pagination = new ResultsPagination({
                    collection: this.collection
                });
                this.$el.after(this.pagination.$el);
            }
            this.pagination.render();
        }
    });

    var ResultsPagination = BaseView.extend({
        initialize: function(options) {
            this.collection = (typeof options.collection != 'undefined')? options.collection : null;
            this.collection.on('reset', this.render.bind(this));
        },

        render: function() {
            this.hideSpinner();

            var attrs = this.collection.attributes;

            this.$el.html('');
            this.$el.append(
                _.template($('#pmp-search-results-pagination-tmpl').html(), {})
            );

            if (typeof attrs.get('total') == 'undefined')
                return this;

            if (attrs.get('page') <= 1)
                this.$el.find('.prev').addClass('disabled');
            else
                this.$el.find('.prev').removeClass('disabled');

            if (attrs.get('total_pages') > 1)
                this.$el.find('.next').removeClass('disabled');

            if (attrs.get('page') >= attrs.get('total_pages'))
                this.$el.find('.next').addClass('disabled');

            this.updateCount();

            return this;
        },

        events: {
            "click a.next": "next",
            "click a.prev": "prev"
        },

        next: function(e) {
            var target = $(e.currentTarget);

            if (target.hasClass('disabled'))
                return false;

            var query = this.collection.attributes.get('query');

            query.offset = this.collection.attributes.get('offset') + this.collection.attributes.get('count');

            this.showSpinner();
            this.collection.search(query);
            return false;
        },

        prev: function(e) {
            var target = $(e.currentTarget);

            if (target.hasClass('disabled'))
                return false;

            var query = this.collection.attributes.get('query');

            query.offset = this.collection.attributes.get('offset') - this.collection.attributes.get('count');

            this.showSpinner();
            this.collection.search(query);
            return false;
        },

        updateCount: function() {
            var attrs = this.collection.attributes;

            this.$el.find('.pmp-page').html(attrs.get('page'));
            this.$el.find('.pmp-total-pages').html(attrs.get('total_pages'));
        }
    });

    var ResultActions = Backbone.View.extend({
        events: {
            "click a.pmp-draft-action": "draft",
            "click a.pmp-publish-action": "publish"
        },

        draft: function() {
            var self = this,
                args = {
                    content: 'Are you sure you want to create a draft of this story?',
                    actions: {
                        'Yes': function() {
                            self.modal.showSpinner();
                            self.model.draft();
                            return false;
                        },
                        'Cancel': 'close'
                    }
                };

            this.renderModal(args);

            return false;
        },

        publish: function() {
            var self = this,
                args = {
                    content: 'Are you sure you want to publish this story?',
                    actions: {
                        'Yes': function() {
                            self.modal.showSpinner();
                            self.model.publish();
                            return false;
                        },
                        'Cancel': 'close'
                    }
                };

            this.renderModal(args);

            return false;
        },

        renderModal: function(args) {
            if (!this.modal) {
                this.modal = new Modal({
                    actions: args.actions,
                    content: args.content
                });
            } else {
                this.modal.actions = args.actions;
                this.modal.content = args.content;
            }

            this.modal.render();
        }
    });

    $(document).ready(function() {
        window.sf = new SearchForm();
    });
})();
