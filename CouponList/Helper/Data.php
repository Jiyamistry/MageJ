<?php
namespace MageJ\CouponList\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    // const MODULE_NAME = 'couponlist/';
    const XML_PATH_IS_ENABLE = 'couponlist/general/enable';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * check if module enabled or not.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_IS_ENABLE);
    }

    /**
     * Get Current Customer group Id.
     *
     * @return int customerGroupId
     */
    public function getCustomerGroupId()
    {
        if ($this->_customerSession->isLoggedIn()) {
            return $this->_customerSession->getCustomer()->getGroupId();
        } else {
            return 0;
        }
    }

    /**
     * Get Current Website Id.
     *
     * @return int websiteId
     */
    public function getWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }
}
