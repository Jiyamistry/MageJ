<?php

namespace MageJ\CouponList\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    protected $_ruleCollection;
    protected $_checkoutSession;
    protected $_helperData;

    public function __construct(
        \MageJ\CouponList\Model\Rule\Collection $ruleCollection,
        \MageJ\CouponList\Helper\Data $helperData,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->_ruleCollection = $ruleCollection;
        $this->_helperData = $helperData;
        $this->_checkoutSession = $checkoutSession;
    }

    public function getConfig()
    {
        file_put_contents(BP . '/var/log/coupon_debug.log', "CONFIG PROVIDER CALLED\n", FILE_APPEND);

        if (!$this->_helperData->isEnabled()) {
            file_put_contents(BP . '/var/log/coupon_debug.log', "MODULE DISABLED\n", FILE_APPEND);
            return [];
        }

        $quote = $this->_checkoutSession->getQuote();

        if (!$quote || !$quote->getId()) {
            file_put_contents(BP . '/var/log/coupon_debug.log', "NO QUOTE FOUND\n", FILE_APPEND);
            return [];
        }

        if (!count($quote->getAllVisibleItems())) {
            file_put_contents(BP . '/var/log/coupon_debug.log', "NO ITEMS IN QUOTE\n", FILE_APPEND);
            return [];
        }

        $list = $this->_ruleCollection->getValidCouponList($quote);

        file_put_contents(BP . '/var/log/coupon_debug.log', "FINAL LIST: ".print_r($list, true)."\n", FILE_APPEND);

        return [
            'couponList' => $list
        ];
    }
}