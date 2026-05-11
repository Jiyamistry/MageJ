define([
    'ko',
    'jquery',
    'uiComponent'
], function (ko, $, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MageJ_CouponList/coupon/link'
        },

        openPopup: function () {
            $('.coupon-list-view-popup').modal('openModal');
        }
    });
});