/**
 * Copyright (c) 2026 Jiya Mistry
 * Licensed under MIT
 */

<?php

declare(strict_types=1);

namespace MageJ\BrandCarousel\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    protected $storeId;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeId     = $storeManager->getStore()->getStoreId();
    }

    /**
     * {@inheritdoc}
     */
    public function isProductPageBrandLogoEnabled()
    {
        return $this->scopeConfig->getValue(
            'brand/brand_logo/isProductPageBrandLogoEnabled',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getProductPageBrandLogoImageWidth()
    {
        $productListBrandLogoImageWidth = $this->scopeConfig->getValue(
            'brand/brand_logo/ProductPageBrandLogoImageWidth',
            ScopeInterface::SCOPE_STORE,
            $this->storeId
        );

        if (!$productListBrandLogoImageWidth) {
            $productListBrandLogoImageWidth = 30;
        }

        return $productListBrandLogoImageWidth;
    }
}
