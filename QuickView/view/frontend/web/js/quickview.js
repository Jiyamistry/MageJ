define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/core/app' , 
    'uiRegistry'
], function ($, modal, app, registry) {
    'use strict';

    var MODAL_ID = 'iflw-quickview-modal';
    var COMPONENT_NAME = 'iflwQuickView';

    function ensureModal(config) {
        var $modal = $('#' + MODAL_ID);

        if ($modal.length) {
            return $modal;
        }

        $modal = $('<div/>', {
            id: MODAL_ID,
            class: 'iflw-quickview-modal'
        });

        // IMPORTANT: proper KO scope binding
        $modal.append(
            '<div class="iflw-quickview-content">' +
                '<!-- ko scope: "' + COMPONENT_NAME + '" -->' +
                    '<!-- ko template: getTemplate() --><!-- /ko -->' +
                '<!-- /ko -->' +
            '</div>'
        );
        $('body').append($modal);

        modal({
            type: 'popup',
            responsive: true,
            innerScroll: true,
            buttons: [],
            modalClass: 'iflw-quickview-popup',
            title: ''
        }, $modal);

        // Initialize component properly
        app({
            components: {
                [COMPONENT_NAME]: {
                    component: 'MageJ_QuickView/js/quickview-modal',
                    ajaxUrl: config.ajaxUrl,
                    showAddToCart: !!config.showAddToCart,
                }
            }
        });

        // require(['ko'], function (ko) {
        //     var modalEl = document.getElementById(MODAL_ID);
        //     if (modalEl) {
        //         ko.applyBindings({}, modalEl);
        //     }
        // });
        require([
            'ko',
            'mage/utils/wrapper'
        ], function (ko) {
            var modalEl = document.getElementById(MODAL_ID);

            if (modalEl && !ko.dataFor(modalEl)) {
                ko.applyBindings({}, modalEl);
            }
        });
        return $modal;
    }

    $(document).on('ajax:addToCart', function () {
        $('#iflw-quickview-modal').modal('closeModal');
    });
    
    return function (config) {
        if (!config || !config.ajaxUrl) {
            return;
        }

        var $modal = ensureModal(config);

        $('body').on('click', '[data-role="quickview"]', function (e) {
            e.preventDefault();

            var productId = parseInt($(this).attr('data-product-id'), 10);
            if (!productId) {
                return;
            }

            $modal.modal('openModal');

            // DIRECT CALL (NO registry)
            registry.async(COMPONENT_NAME)(function (component) {
                if (component && typeof component.load === 'function') {
                    component.load(productId);
                } else {
                    console.error('QuickView component not ready');
                }
            });
        });
    };
});
