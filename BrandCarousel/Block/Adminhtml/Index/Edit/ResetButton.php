<?php

declare(strict_types=1);

namespace MageJ\BrandCarousel\Block\Adminhtml\Index\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ResetButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {

        $data = [];
        if ($this->getBrandId()) {
            $data = [
                'label'      => __('Reset'),
                'class'      => 'reset',
                'on_click'   => 'location.href;',
                'sort_order' => 30,
                'title'      => 'Resets input fields values',
            ];
        }
        return $data;
    }
}
