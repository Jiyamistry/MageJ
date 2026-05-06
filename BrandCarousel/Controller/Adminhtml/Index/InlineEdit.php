<?php

namespace MageJ\BrandCarousel\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use MageJ\BrandCarousel\Model\BrandFactory;

class InlineEdit extends Action
{
    protected $jsonFactory;
    protected $brandFactory;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        BrandFactory $brandFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->brandFactory = $brandFactory;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!count($postItems)) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data.')],
                'error' => true,
            ]);
        }

        foreach (array_keys($postItems) as $brand_id) {
            $brand = $this->brandFactory->create()->load($brand_id);
            try {
                $brandData = $postItems[$brand_id];
                $brand->addData($brandData);
                $brand->save();
            } catch (\Exception $e) {
                $messages[] = "[Brand ID: $brand_id] " . $e->getMessage();
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error,
        ]);
    }
}
