/**
 * Copyright (c) 2026 Jiya Mistry
 * Licensed under MIT
 */

<?php
namespace MageJ\BrandCarousel\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use MageJ\BrandCarousel\Model\ResourceModel\Brand\CollectionFactory;

/**
 * Class Massdelete
 * Handles mass deletion of brands
 */
class Massdelete extends Action
{
    const ADMIN_RESOURCE = 'MageJ_BrandCarousel::brands_brand';

    /** @var Filter */
    protected $filter;

    /** @var CollectionFactory */
    protected $collectionFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();

            foreach ($collection as $brand) {
                $brand->delete();
            }

            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $collectionSize)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong: %1', $e->getMessage()));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setPath('*/*/index');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
