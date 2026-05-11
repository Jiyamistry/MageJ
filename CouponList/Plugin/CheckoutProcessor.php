<?php
namespace MageJ\CouponList\Plugin;

class CheckoutProcessor
{
    /**
     * @var \MageJ\CouponList\Helper\Data
     */
    private $helperData;

    /**
     * CheckoutProcessor constructor.
     */
    public function __construct(
        \MageJ\CouponList\Helper\Data $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * Checkout LayoutProcessor after process plugin.
     *
     * @param array $jsLayout
     *
     * @return                                        array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $processor, $jsLayout)
    {
        $paymentConfig = $jsLayout['components']['checkout']['children']['steps']['children']['billing-step'];

        if (!$this->helperData->isEnabled()) {
            unset($paymentConfig['children']['payment']['children']['afterMethods']['children']['coupon-link']);
            unset($paymentConfig['children']['payment']['children']['afterMethods']['children']['coupon-list']);
        }

        return $jsLayout;
    }
}
