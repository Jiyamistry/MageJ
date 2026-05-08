<?php

namespace MageJ\QuickView\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const XML_PATH_ENABLED = 'quickview/general/enabled';
    const XML_PATH_SHOW_ADD_TO_CART = 'quickview/general/show_add_to_cart';
    const XML_PATH_SHOW_ON_SLIDER = 'quickview/general/show_on_slider';
    const XML_PATH_SHOW_ON_RELATED = 'quickview/general/show_on_related';
    const XML_PATH_SHOW_ON_UPSELL = 'quickview/general/show_on_upsell';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isShowAddToCartEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_ADD_TO_CART,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @return bool
     */
    public function isShowOnSliderEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_ON_SLIDER,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isShowOnRelatedEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_ON_RELATED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function isShowOnUpsellEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_ON_UPSELL,
            ScopeInterface::SCOPE_STORE
        );
    }
}