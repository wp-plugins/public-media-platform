var PMP = PMP || {};

(function() {
    var $ = jQuery;

    // Models and Collections
    PMP.Doc = Backbone.Model.extend({
        initialize: function(attributes, options) {
            Backbone.Model.prototype.initialize.apply(this, arguments);
            this.set('items', new PMP.DocCollection(this.get('items')));
        },

        profileAliases: {
            'ef7f170b-4900-4a20-8b77-3142d4ac07ce': 'audio',
            '8bf6f5ae-84b1-4e52-a744-8e1ac63f283e': 'contributor',
            '42448532-7a6f-47fb-a547-f124d5d9053e': 'episode',
            '5f4fe868-5065-4aa2-86e6-2387d2c7f1b6': 'image',
            '88506918-b124-43a8-9f00-064e732cbe00': 'property',
            'c07bd70c-8644-4c5d-933a-40d5d7032036': 'series',
            'b9ce545e-01a2-44d0-9a15-a73da4ed304b': 'story',
            '3ffa207f-cfbe-4bcd-987c-0bd8e29fdcb6': 'topic',
            '85115aa1-df35-4324-9acd-2bb261f8a541': 'video',
        },

        getProfile: function() {
            var links = this.get('links');

            if (links) {
                var profile = links.profile;

                if (typeof profile !== 'undefined') {
                    if (typeof profile[0] !== 'undefined')
                        return profile[0];
                    else
                        return null;
                }
            }
            return null;
        },

        getProfileAlias: function() {
            var link = this.getProfile();
            if (link && link.href) {
                var guidOrAlias = link.href.split('/');
                guidOrAlias = _.last(guidOrAlias);
                return (this.profileAliases[guidOrAlias])? this.profileAliases[guidOrAlias] : guidOrAlias;
            } else
                return null;
        },

        getCreator: function() {
            var links = this.get('links');

            if (links) {
                var creator = links.creator;

                if (typeof creator[0] !== 'undefined')
                    return creator[0];
                else
                    return null;
            }
            return null;
        },

        getCreatorAlias: function() {
            var creator = this.getCreator();

            if (creator && creator.href) {
                var parts = creator.href.split('/'),
                    last = _.last(parts);

                return (PMP.creators[last])? PMP.creators[last] : null;
            } else
                return null;
        },

        getImage: function() {
            if (this.getProfileAlias() == 'image')
                return this;

            return this.get('items').find(function(item) {
                if (item.getProfileAlias() == 'image')
                    return item;
            });
        },

        getImageCrop: function(crop) {
            var image = this.getImage(),
                ret;

            if (image) {
                ret = _.find(image.get('links').enclosure, function(enc) {
                    if (enc.meta && enc.meta.crop == crop)
                        return enc;
                });
            }
            return ret;
        },

        getBestThumbnail: function() {
            var thumbnail = null,
                sizes = ['small', 'thumb', 'standard', 'primary'];

            for (var idx in sizes) {
                thumbnail = this.getImageCrop(sizes[idx]);
                if (thumbnail) { break; }
            }

            if (!thumbnail && this.getImage()) {
                var fallback = this.getImage();
                thumbnail = fallback.get('links').enclosure[0];
            }

            return thumbnail;
        },

        getFirstEnclosure: function() {
            return (this.get('links').enclosure)? this.get('links').enclosure[0] : null;
        },

        draft: function() {
            this.createPost(true);
            return false;
        },

        publish: function() {
            this.createPost(false);
            return false;
        },

        createPost: function(draft) {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var self = this,
                action = (draft)? 'pmp_draft_post' : 'pmp_publish_post',
                data = {
                    action: action,
                    security: PMP.ajax_nonce,
                };

            var post_data = this.toJSON();
            post_data.attachment = (this.getImage())? this.getImage().toJSON() : null;
            data.post_data = JSON.stringify(post_data);

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(result) {
                    if (result.success)
                        window.location = result.data.edit_url;
                    return false;
                },
                error: function(response) {
                    alert('There was an error processing your request. Message: "' + response.responseJSON.message + '"');
                    window.location.reload(true);
                }
            };

            this.ongoing = $.ajax(opts);
            return this.ongoing;
        },

        toJSON: function() {
            var attrs = _.clone(this.attributes);
            attrs.items = attrs.items.toJSON();
            return attrs;
        }
    });

    PMP.DocCollection = Backbone.Collection.extend({
        model: PMP.Doc,

        initialize: function() {
            this.attributes = new Backbone.Model();
            Backbone.Collection.prototype.initialize.apply(this, arguments);
        },

        search: function(query) {
            if (typeof this.ongoing !== 'undefined' && $.inArray(this.ongoing.state(), ['resolved', 'rejected']) == -1)
                return false;

            var self = this,
                data = {
                    action: 'pmp_search',
                    security: PMP.ajax_nonce,
                    query: JSON.stringify(query)
                };

            var opts = {
                url: ajaxurl,
                dataType: 'json',
                data: data,
                method: 'post',
                success: function(result) {
                    if (result.success) {
                        self.reset(result.data.items);

                        var attrs = _.extend({
                            query: query
                        }, result.data);

                        self.attributes.set(attrs);
                    }
                },
                error: function(response) {
                    self.trigger('error', response);
                }
            };

            this.ongoing = $.ajax(opts);

            return this.ongoing;
        }
    });

    // Views
    PMP.BaseView = Backbone.View.extend({
        showSpinner: function() {
            this.$el.find('.spinner').css('display', 'inline-block');
        },

        hideSpinner: function() {
            this.$el.find('.spinner').css('display', 'none');
        }
    });

    PMP.Modal = PMP.BaseView.extend({
        actions: null,

        content: null,

        events: {
            "click .close": "close"
        },

        initialize: function(options) {
            var self = this;

            this.$el.addClass('pmp-modal');

            Backbone.View.prototype.initialize.apply(this, arguments);
            this.template = _.template($('#pmp-modal-tmpl').html());

            if (!this.content)
                this.content = (typeof options.content !== 'undefined')? options.content : '';

            if (!this.actions)
                this.actions = (typeof options.actions !== 'undefined')? options.actions : {};

            this.setEvents();

            $('body').append(this.$el);
            if ($('#pmp-modal-overlay').length == 0)
                $('body').append('<div id="pmp-modal-overlay" />');
        },

        render: function() {
            this.$el.html(this.template({
                content: this.content,
                actions: this.actions
            }));
            this.setEvents();
            this.open();
        },

        setEvents: function() {
            var events = {};
            _.each(this.actions, function(v, k) { events['click .' + k] = v; });
            this.delegateEvents(_.extend(this.events, events));
        },

        open: function() {
            $('body').addClass('pmp-modal-open');
            this.$el.removeClass('hide');
            this.$el.addClass('show');
            return false;
        },

        close: function() {
            $('body').removeClass('pmp-modal-open');
            this.$el.removeClass('show');
            this.$el.addClass('hide');
            return false;
        }
    });

})();
