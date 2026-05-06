<?php

namespace MageJ\BrandCarousel\Controller\Adminhtml\Index;
use Magento\Framework\Controller\ResultFactory;


class Add extends \Magento\Backend\App\Action
{
   public const ADMIN_RESOURCE = 'MageJ_BrandCarousel::brand_brands';
     /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
   {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $id=$this->getRequest()->getParam('brand_id');
         if(!empty($id)){
            $resultPage->getConfig()->getTitle()->prepend((__('Edit Brand')));            
         }else{
            $resultPage->getConfig()->getTitle()->prepend((__('Add Brand')));
         }
        return $resultPage;
   }
       protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
