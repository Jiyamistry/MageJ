<?php
namespace MageJ\BrandCarousel\Controller\Adminhtml\Index;

use Magento\Backend\App\Action as AppAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends AppAction implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageJ_BrandCarousel::brand_brands';

    /** @var PageFactory */
    private $pageFactory;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(Context $context, PageFactory $pageFactory)
    {
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Brand List'));

        return $page;
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
