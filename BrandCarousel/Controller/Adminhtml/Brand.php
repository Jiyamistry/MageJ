/**
 * Copyright (c) 2026 Jiya Mistry
 * Licensed under MIT
 */

<?php

namespace MageJ\BrandCarousel\Controller\Adminhtml;

use Magento\Framework\Controller\Result\Redirect;

/**
 * Brand Abstract Action
 * @author Agile Codex
 */
abstract class Brand extends \Magento\Backend\App\Action
{
    public const PARAM_CRUD_ID = 'brand_id';

    /**
     * Check if admin has permissions to visit related pages.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageJ_BrandCarousel::brand_brands');
    }

    /**
     * Get back result redirect after add/edit.
     *
     * @param Redirect $resultRedirect
     * @param int|null $paramCrudId
     * @return Redirect
     */
    protected function _getBackResultRedirect(
        \Magento\Framework\Controller\Result\Redirect $resultRedirect,
        $paramCrudId = null
    ) {
        switch ($this->getRequest()->getParam('back')) {

            case 'edit':
                return $resultRedirect->setPath(
                    'brandcarousel/index/add',
                    [
                        static::PARAM_CRUD_ID => $paramCrudId
                    ]
                );

            case 'new':
                return $resultRedirect->setPath(
                    'brandcarousel/index/add'
                );

            default:
                return $resultRedirect->setPath(
                    'brandcarousel/index/index'
                );
        }
    }
}
