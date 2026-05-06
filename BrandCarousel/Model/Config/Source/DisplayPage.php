<?php
namespace MageJ\BrandCarousel\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DisplayPage implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'home', 'label' => __('Homepage Only')],
            ['value' => 'category', 'label' => __('Category Page')],
            ['value' => 'product', 'label' => __('Product Page')],
            ['value' => 'all', 'label' => __('All Pages')],
        ];
    }
}
