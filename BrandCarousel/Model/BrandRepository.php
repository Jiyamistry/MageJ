<?php

namespace MageJ\BrandCarousel\Model;

use MageJ\BrandCarousel\Api\BrandRepositoryInterface;
use MageJ\BrandCarousel\Api\Data\BrandSearchResultsInterface;
use MageJ\BrandCarousel\Api\Data\BrandInterface;
use MageJ\BrandCarousel\Model\BrandFactory;
use MageJ\BrandCarousel\Model\ResourceModel\Brand as BrandResourceModel;
use MageJ\BrandCarousel\Model\ResourceModel\Brand\Collection;
use MageJ\BrandCarousel\Model\ResourceModel\Brand\CollectionFactory as BrandCollectionFactory;
use MageJ\BrandCarousel\Api\Data\BrandSearchResultsInterfaceFactory as ResultsInterfaceFactory;
use MageJ\BrandCarousel\Model\ResourceModel\Store\Relation as StoreRelation;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Repository class to save, load or delete Brand logo
 */
class BrandRepository implements BrandRepositoryInterface
{
    /** @var BrandResourceModel  */
    protected $resource;

    /** @var BrandFactory */
    protected $brandFactory;

    /** @var BrandCollectionFactory  */
    protected $brandCollectionFactory;

    /** @var ResultsInterfaceFactory  */
    protected $searchResultsFactory;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var StoreRelation */
    protected $storeRelation;

    /**
     * @param BrandResourceModel $resource
     * @param \MageJ\BrandCarousel\Model\BrandFactory $brandFactory
     * @param BrandCollectionFactory $brandCollectionFactory
     * @param ResultsInterfaceFactory $searchResultsFactory
     * @param StoreManagerInterface $storeManager
     * @param StoreRelation $storeRelation
     * @param CollectionProcessorInterface|null $collectionProcessor
     */
    public function __construct(
        BrandResourceModel $resource,
        BrandFactory $brandFactory,
        BrandCollectionFactory $brandCollectionFactory,
        ResultsInterfaceFactory $searchResultsFactory,
        StoreManagerInterface $storeManager,
        StoreRelation $storeRelation,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->resource = $resource;
        $this->brandFactory = $brandFactory;
        $this->brandCollectionFactory = $brandCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->storeManager = $storeManager;
        $this->storeRelation = $storeRelation;
    }

    /**
     * Save Brand data
     *
     * @param BrandInterface $brand
     * @return BrandInterface
     * @throws CouldNotSaveException
     */
    public function save(BrandInterface $brand)
    {
        try {
            $storeIds = $brand->getStoreId();
            $brand->unsetStoreIds();
            $this->resource->save($brand);
            $brandId = $brand->getId();
            if (!empty($brandId)) {
                $this->storeRelation->processRelations($brandId, $storeIds);
            }
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $brand;
    }

    /**
     * Load Brand data by given Brand Identity
     *
     * @param int $brandId
     * @return BrandInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $brandId): BrandInterface
    {
        $brand = $this->brandFactory->create();
        $this->resource->load($brand, $brandId);

        if (!$brand->getId()) {
            throw new NoSuchEntityException(__('The Brand with the "%1" ID doesn\'t exist.', $brandId));
        }
        return $brand;
    }

    /**
     * Load Brand data collection by given search criteria
     *
     * @param SearchCriteriaInterface $criteria
     * @return \MageJ\BrandCarousel\Api\Data\BrandSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria): BrandSearchResultsInterface
    {
        /** @var \MageJ\BrandCarousel\Model\ResourceModel\Brand\Collection $collection */
        $collection = $this->brandCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        /** @var \MageJ\BrandCarousel\Api\Data\BrandSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Delete Brand
     *
     * @param BrandInterface $brand
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(BrandInterface $brand): bool
    {
        try {
            $this->resource->delete($brand);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete Brand by given Brand Identity
     *
     * @param string $brandId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $brandId): bool
    {
        return $this->delete($this->getById($brandId));
    }

    /**
     * Get brand logo collection.
     *
     * @return \MageJ\BrandCarousel\Model\ResourceModel\Brand\Collection
     */
    public function getBrandCollection(): Collection
    {
        $storeViewId = $this->storeManager->getStore()->getId();

        /** @var \MageJ\BrandCarousel\Model\ResourceModel\Brand\Collection $brandCollection */
        $brandCollection = $this->brandCollectionFactory->create()
            ->addFieldToFilter('status', Status::STATUS_ENABLED)
            ->addFieldToFilter('store_id', ['in' => [0,$storeViewId]])
            ->setOrder('sort_order', 'ASC');

        return $brandCollection;
    }
}
