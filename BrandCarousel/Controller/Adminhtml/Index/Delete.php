<?php

namespace MageJ\BrandCarousel\Controller\Adminhtml\Index;
use Magento\Framework\Controller\ResultFactory;


class Delete extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'MageJ_BrandCarousel::brand_brands';

    protected $resultPageFactory = false;
    protected $brandFactory;
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \MageJ\BrandCarousel\Model\BrandFactory $brandFactory
    ){
        parent::__construct($context);
        $this->brandFactory = $brandFactory;
        $this->resultPageFactory = $resultPageFactory;

    }
    public function execute()
    {
        $brandId = $this->getRequest()->getParam('brand_id');
        // var_dump($brandId);
        // die();  
        try {
            $brand = $this->brandFactory->create()->setBrandId($brandId);
            $brand->delete();
            $this->messageManager->addSuccess(
                __('Delete successfully !')
            );
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
