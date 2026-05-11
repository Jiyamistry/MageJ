<?php
namespace MageJ\CouponList\Model;

use MageJ\CouponList\Api\CouponListInterface;

/**
 * Coupon list object.
 */
class CouponList implements CouponListInterface
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Sales Rules collection.
     *
     * @var \MageJ\CouponList\Model\Rule\Collection
     */
    protected $ruleCollection;

    /**
     * Constructs a coupon read service object.
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \MageJ\CouponList\Model\Rule\Collection $ruleCollection
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->ruleCollection = $ruleCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function get($cartId)
    {
        /**
         * @var \Magento\Quote\Model\Quote $quote 
        */
        $quote = $this->quoteRepository->getActive($cartId);

        return $this->ruleCollection->getValidCouponList($quote);
    }
}
