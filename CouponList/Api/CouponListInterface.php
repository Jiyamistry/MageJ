<?php
namespace MageJ\CouponList\Api;

/**
 * Coupon list service interface.
 */
interface CouponListInterface
{
    /**
     * Returns list of valid coupon in a specified cart.
     *
     * @param int $cartId the cart ID
     *
     * @return string[] the coupon list data
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException the specified cart does not exist
     */
    public function get($cartId);
}
