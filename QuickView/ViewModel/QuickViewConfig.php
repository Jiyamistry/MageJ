<?php

namespace MageJ\QuickView\ViewModel;

use MageJ\QuickView\Model\Config;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class QuickViewConfig implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->config->isEnabled();
    }

    /**
     * @return bool
     */
    public function isShowAddToCartEnabled()
    {
        return $this->config->isShowAddToCartEnabled();
    }

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return 'quickview/product/view';
    }
}