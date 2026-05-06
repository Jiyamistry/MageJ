<?php
namespace MageJ\BrandCarousel\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class AddBrandCarouselBlock implements ObserverInterface
{
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ){
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $layout = $observer->getLayout();
        if (!$layout) {
            return;
        }

        // Check if module is enabled
        $enabled = $this->scopeConfig->isSetFlag(
            'brandcarousel/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$enabled) {
            return;
        }

        // Get container from admin config
        $container = trim((string) $this->scopeConfig->getValue(
            'brandcarousel/general/display_container',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ));

        // If container is empty, do not render
        if (empty($container)) {
            return;
        }

        // Check if container exists in layout
        if (!$layout->hasElement($container)) {
            return;
        }

        $blockName = 'brandcarousel.dynamic';

        // Prevent duplicate block
        if ($layout->hasElement($blockName)) {
            return;
        }

        // Add block to selected container
        $layout->addBlock(
            \MageJ\BrandCarousel\Block\Index\BrandCarousel::class,
            $blockName,
            $container
        )->setTemplate('MageJ_BrandCarousel::brand-slider.phtml');
    }
}
