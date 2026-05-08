define([
    'uiComponent',
    'jquery',
    'ko',
    'mage/storage',
    'mage/translate'
], function (Component, $, ko, storage, $t) {
    'use strict';

    window.BASE_URL = window.BASE_URL || window.location.origin + '/';

    return Component.extend({
        defaults: {
            template: 'MageJ_QuickView/quick-view',
            ajaxUrl: '',
            showAddToCart: true
        },

        initialize: function () {
            this._super();

            this.isLoading = ko.observable(false);
            this.errorMessage = ko.observable('');

            this.productId = ko.observable(null);
            this.name = ko.observable('');
            this.sku = ko.observable('');
            this.productUrl = ko.observable('');

            this.price = ko.observable('');
            this.specialPrice = ko.observable(null);

            this.isSalable = ko.observable(false);
            this.breadcrumb = ko.observableArray([]);

            this.galleryImages = ko.observableArray([]);
            this.activeImage = ko.observable(null);

            this.showAddToCartResolved = ko.observable(!!this.showAddToCart);
            this.addToCart = ko.observable(null);

            this.productType = ko.observable('');
            this.configurable = ko.observable(null);

            this.formId = ko.observable('iflw-quickview-form');
            this.swatchContainerId = ko.observable('iflw-quickview-swatches');
            this.needsWidgetInit = ko.observable(false);

            this.isConfigurable = ko.pureComputed(function () {
                return this.productType() === 'configurable' && !!this.configurable();
            }, this);

            return this;
        },

        load: function (productId) {
            var self = this;

            self.isLoading(true);
            self.errorMessage('');
            self.needsWidgetInit(false);

            self.productId(productId);
            self.formId('iflw-quickview-form-' + productId);
            self.swatchContainerId('iflw-quickview-swatches-' + productId);

            return storage.get(self.ajaxUrl + '?id=' + encodeURIComponent(productId), false)
                .done(function (data) {
                    if (!data || data.error) {
                        self.errorMessage(
                            (data && data.message)
                                ? data.message
                                : $t('Unable to load product.')
                        );
                        return;
                    }

                    self.name(data.name || '');
                    self.sku(data.sku || '');
                    self.productUrl(data.product_url || '');
                    self.productType(data.product_type || '');
                    self.isSalable(!!data.is_salable);
                    self.breadcrumb(data.breadcrumb || []);

                    self.price(data.price || '');
                    self.specialPrice(data.special_price || null);

                    self.showAddToCartResolved(!!data.show_add_to_cart);
                    self.addToCart(data.add_to_cart || null);

                    var images = (data.gallery && data.gallery.images)
                        ? data.gallery.images
                        : [];

                    self.galleryImages(images);
                    self.activeImage(images.length ? images[0] : null);

                    self.configurable(data.configurable || null);

                    self.needsWidgetInit(
                        self.isConfigurable() &&
                        self.showAddToCartResolved() &&
                        self.isSalable()
                    );
                })
                .fail(function () {
                    self.errorMessage($t('Unable to load product.'));
                })
                .always(function () {
                    self.isLoading(false);

                    setTimeout(function () {
                        if (self.isConfigurable()) {
                            self.initPriceBox();
                            self.initConfigurableWidgets();
                        }

                        self.initAddToCart();
                    }, 700);
                });
        },

        selectImage: function (image) {
            this.activeImage(image);
        },

        initAddToCart: function () {
            var self = this;
            var $form = $('#' + self.formId());

            if (!$form.length) {
                return;
            }

            require([
                'jquery',
                'Magento_Catalog/js/catalog-add-to-cart'
            ], function ($) {
                $form.catalogAddToCart({
                    bindSubmit: true
                });
            });
        },

        initConfigurableWidgets: function () {
            var self = this;
            var cfg = self.configurable();

            if (!cfg || !cfg.jsonConfig || !cfg.jsonSwatch) {
                return;
            }

            var $form = $('#' + self.formId());
            var $swatches = $('#' + self.swatchContainerId());

            if (!$form.length || !$swatches.length) {
                return;
            }

            var jsonConfig = typeof cfg.jsonConfig === 'string'
                ? JSON.parse(cfg.jsonConfig)
                : cfg.jsonConfig;

            var jsonSwatch = typeof cfg.jsonSwatch === 'string'
                ? JSON.parse(cfg.jsonSwatch)
                : cfg.jsonSwatch;

            require([
                'jquery',
                'Magento_ConfigurableProduct/js/configurable',
                'Magento_Swatches/js/swatch-renderer',
                'jquery-ui-modules/widget'
            ], function ($) {
                try {
                    $swatches.SwatchRenderer({
                        jsonConfig: jsonConfig,
                        jsonSwatchConfig: jsonSwatch,

                        selectorProduct: '.product-info-main',
                        selectorProductPrice: '[data-role=priceBox]',

                        mediaCallback: window.BASE_URL + 'swatches/ajax/media/',

                        onlySwatches: false,
                        enableControlLabel: true,
                        inProductList: false
                    });

                    var sw = $swatches.data('mage-SwatchRenderer');

                    if (sw) {
                        sw.productForm = $form;
                        sw.options.selectorProduct = '#' + self.formId();
                    }

                    $swatches.on('click', '.swatch-option', function () {
                        setTimeout(function () {
                            var sw = $swatches.data('mage-SwatchRenderer');

                            if (!sw || !sw.getProduct()) {
                                return;
                            }

                            var simpleProductId = sw.getProduct();

                            if (
                                !jsonConfig.images ||
                                !jsonConfig.images[simpleProductId]
                            ) {
                                return;
                            }

                            var images = jsonConfig.images[simpleProductId].map(function (img) {
                                return {
                                    url: img.full || img.img || img.thumb,
                                    label: img.caption || self.name()
                                };
                            });

                            self.galleryImages(images);
                            self.activeImage(images[0]);
                        }, 500);
                    });
                } catch (e) {
                    console.error(e);
                }
            });
        },

        initPriceBox: function () {
            var self = this;
            var cfg = self.configurable();

            if (!cfg || !cfg.jsonConfig) {
                return;
            }

            var jsonConfig = typeof cfg.jsonConfig === 'string'
                ? JSON.parse(cfg.jsonConfig)
                : cfg.jsonConfig;

            var $priceBox = $('#' + self.formId()).find('[data-role="priceBox"]');

            if (!$priceBox.length) {
                return;
            }

            require([
                'jquery',
                'Magento_Catalog/js/price-box'
            ], function ($) {
                $priceBox.priceBox({
                    priceConfig: jsonConfig,
                    prices: jsonConfig.prices
                });
            });
        }
    });
});