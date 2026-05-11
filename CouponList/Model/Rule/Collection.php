<?php
namespace MageJ\CouponList\Model\Rule;

use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory as CouponCollectionFactory;

class Collection
{
    protected $_helperData;
    protected $_collectionFactory;
    protected $couponCollectionFactory;

    public function __construct(
        \MageJ\CouponList\Helper\Data $helperData,
        CollectionFactory $collectionFactory,
        CouponCollectionFactory $couponCollectionFactory
    ) {
        $this->_helperData = $helperData;
        $this->_collectionFactory = $collectionFactory;
        $this->couponCollectionFactory = $couponCollectionFactory;
    }

    /**
     * Get rules collection
     */
    public function getRulesCollection()
    {
        $websiteId = $this->_helperData->getWebsiteId();
        $customerGroupId = $this->_helperData->getCustomerGroupId();

        return $this->_collectionFactory->create()
            ->addWebsiteGroupDateFilter($websiteId, $customerGroupId)
            ->addAllowedSalesRulesFilter()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('coupon_type', ['eq' => Rule::COUPON_TYPE_SPECIFIC]);
    }

    /**
     * Get valid coupons
     */
    public function getValidCouponList($quote)
    {
        $couponList = [];

        try {
            if (!$quote || !$quote->getId()) {
                $this->log("Quote not found or invalid.");
                return $couponList;
            }

            $quote->collectTotals();

            $rules = $this->getRulesCollection();

            if (!count($rules)) {
                $this->log("No sales rules found.");
                return $couponList;
            }

            foreach ($rules as $rule) {

                // Fetch coupon code
                $couponCollection = $this->couponCollectionFactory->create()
                    ->addFieldToFilter('rule_id', $rule->getId())
                    ->setPageSize(1);

                $couponCode = null;

                foreach ($couponCollection as $coupon) {
                    $couponCode = $coupon->getCode();
                    break;
                }

                if (!$couponCode) {
                    // log only if rule is specific but no coupon found
                    $this->log("No coupon found for rule ID: " . $rule->getId());
                    continue;
                }

                $address = $quote->getShippingAddress();

                if (!$address || !$address->getQuoteId()) {
                    $this->log("Invalid address for quote ID: " . $quote->getId());
                    continue;
                }

                // Validate rule
                $isApplicable = $rule->validate($address);

                /**
                 * Extra validation for Buy X Get Y rules
                 */
                if ($rule->getSimpleAction() === 'buy_x_get_y') {

                    $requiredQty = (int)$rule->getDiscountStep();
                    $cartQty = 0;

                    foreach ($quote->getAllVisibleItems() as $item) {
                        $cartQty += (int)$item->getQty();
                    }

                    if ($cartQty < $requiredQty) {
                        $isApplicable = false;
                    }
                }

                $couponList[] = [
                    'coupon' => $couponCode,
                    'name' => $rule->getName(),
                    'description' => $rule->getDescription() ?: '',
                    'is_applicable' => $isApplicable
                ];
            }

            if (empty($couponList)) {
                $this->log("No valid coupons matched for quote ID: " . $quote->getId());
            }

        } catch (\Exception $e) {
            $this->log("Exception: " . $e->getMessage());
        }

        return $couponList;
    }

    /**
     * Centralized logger
     */
    protected function log($message)
    {
        file_put_contents(
            BP . '/var/log/coupon_error.log',
            date('Y-m-d H:i:s') . ' - ' . $message . "\n",
            FILE_APPEND
        );
    }
}