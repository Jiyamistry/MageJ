<?php

namespace MageJ\BrandCarousel\Block\Index;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use MageJ\BrandCarousel\Model\ResourceModel\Brand\CollectionFactory;

class BrandCarousel extends Template
{
    protected $scopeConfig;
    protected $brandCollectionFactory;

    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $brandCollectionFactory,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->brandCollectionFactory = $brandCollectionFactory;
        parent::__construct($context, $data);
    }
    // General configuration
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag('brandcarousel/general/enabled', ScopeInterface::SCOPE_STORE);
    }
    public function getSliderHeading()
    {
        return $this->scopeConfig->getValue('brandcarousel/general/slider_heading', ScopeInterface::SCOPE_STORE);
    }
    // Slide configuration
    public function slideToShow()
    {
        return $this->scopeConfig->getValue('brandcarousel/slider/show', ScopeInterface::SCOPE_STORE);
        
    }

    public function slideToScroll()
    {
        return $this->scopeConfig->getValue('brandcarousel/slider/scroll', ScopeInterface::SCOPE_STORE);
    }

    public function isAutoplayEnabled()
    {
        return $this->scopeConfig->isSetFlag('brandcarousel/slider/autoplay', ScopeInterface::SCOPE_STORE);
    }
    public function isNavEnabled()
    {
        return $this->scopeConfig->isSetFlag('brandcarousel/slider/nav', ScopeInterface::SCOPE_STORE);
    }

    public function isDotsEnabled()
    {
        return $this->scopeConfig->isSetFlag('brandcarousel/slider/dots', ScopeInterface::SCOPE_STORE);
    }

    public function getBrandCollection()
    {
        $storeId = $this->_storeManager->getStore()->getId();

        return $this->brandCollectionFactory->create()
                        ->setOrder('sort_order', 'ASC')
                        ->addFieldToFilter('store_id', ['in' => [0, $storeId]])
                        ->addFieldToFilter('status', 1);
    }

    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }
    public function getDisplayPage()
    {
        return $this->scopeConfig->getValue(
            'brandcarousel/general/display_page',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if slider can be shown on current page
     */
    public function canShowSlider()
    {
        $displayPage = $this->getDisplayPage();
        $fullActionName = $this->getRequest()->getFullActionName();

        if ($displayPage === 'all') {
            return true;
        }

        if ($displayPage === 'home' && $fullActionName === 'cms_index_index') {
            return true;
        }

        if ($displayPage === 'category' && $fullActionName === 'catalog_category_view') {
            return true;
        }

        if ($displayPage === 'product' && $fullActionName === 'catalog_product_view') {
            return true;
        }

        return false;
    }
    public function getAnimationType()
    {
        return $this->scopeConfig->getValue(
            'brandcarousel/slider/animation',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) ?: 'slide';
    }
}
