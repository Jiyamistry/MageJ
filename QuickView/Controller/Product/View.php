<?php

namespace MageJ\QuickView\Controller\Product;

use MageJ\QuickView\Model\Config;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableBlock;
use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchBlock;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Registry;

class View implements ActionInterface, HttpGetActionInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param FormKey $formKey
     * @param EncoderInterface $urlEncoder
     * @param LayoutFactory $layoutFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Config $config
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        FormKey $formKey,
        EncoderInterface $urlEncoder,
        LayoutFactory $layoutFactory,
        CategoryRepositoryInterface $categoryRepository,
        Config $config,
        Registry $registry
    ) {
        $this->context = $context;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->formKey = $formKey;
        $this->urlEncoder = $urlEncoder;
        $this->layoutFactory = $layoutFactory;
        $this->categoryRepository = $categoryRepository;
        $this->config = $config;
        $this->registry = $registry;
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->config->isEnabled()) {
            return $result->setData(['error' => true]);
        }

        $productId = (int)$this->context->getRequest()->getParam('id');

        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return $result->setData(['error' => true]);
        }

        $regular = (float)$product->getPriceInfo()
            ->getPrice('regular_price')
            ->getValue();

        $final = (float)$product->getPriceInfo()
            ->getPrice('final_price')
            ->getValue();

        $galleryImages = [];

        if ($product->getMediaGalleryImages()) {
            foreach ($product->getMediaGalleryImages() as $img) {
                $galleryImages[] = [
                    'url' => $img->getUrl(),
                    'label' => $img->getLabel()
                ];
            }
        }

        $productUrl = $product->getProductUrl();

        $data = [
            'name' => $product->getName(),
            'price' => $this->priceCurrency->convertAndFormat($regular),

            'special_price' => $final < $regular
                ? $this->priceCurrency->convertAndFormat($final)
                : null,

            'sku' => $product->getSku(),
            'product_url' => $productUrl,
            'product_type' => $product->getTypeId(),
            'is_salable' => $product->isSalable(),

            'gallery' => [
                'images' => $galleryImages
            ],

            'add_to_cart' => [
                'action' => $this->context
                    ->getUrl()
                    ->getUrl('checkout/cart/add'),

                'form_key' => $this->formKey->getFormKey(),

                'uenc' => $this->urlEncoder->encode($productUrl),

                'product' => $product->getId(),

                'qty' => 1
            ],

            'configurable' => null,

            'show_add_to_cart' => $this->config
                ->isShowAddToCartEnabled()
        ];

        if ($product->getTypeId() === ConfigurableType::TYPE_CODE) {

            $this->registry->unregister('current_product');
            $this->registry->register('current_product', $product);

            $layout = $this->layoutFactory->create();

            $configurableBlock = $layout
                ->createBlock(ConfigurableBlock::class)
                ->setProduct($product);

            $swatchBlock = $layout
                ->createBlock(SwatchBlock::class)
                ->setProduct($product);

            $data['configurable'] = [
                'jsonConfig' => $configurableBlock->getJsonConfig(),
                'jsonSwatch' => $swatchBlock->getJsonSwatchConfig()
            ];
        }

        return $result->setData($data);
    }
}