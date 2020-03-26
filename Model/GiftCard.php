<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GiftCardSampleData
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GiftCardSampleData\Model;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Api\GuestCartItemRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Mageplaza\GiftCard\Helper\Data;
use Mageplaza\GiftCard\Model\LogsFactory;

/**
 * Class GiftCard
 * @package Mageplaza\GiftCardSampleData\Model
 */
class GiftCard
{
    /**
     * @var FixtureManager
     */
    private $fixtureManager;

    /**
     * @var Csv
     */
    protected $csvReader;


    /**
     * @var File
     */
    private $file;

    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var StockItemInterfaceFactory
     */
    private $stockItemInterfaceFactory;
    /**
     * @var GuestCartManagementInterface
     */
    private $guestCartManagement;
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var CartItemInterfaceFactory
     */
    private $cartItemFactory;
    /**
     * @var GuestCartItemRepositoryInterface
     */
    private $itemRepository;
    /**
     * @var \Mageplaza\GiftCard\Helper\Template
     */
    private $templateHelper;

    /**
     * @var WriteInterface
     */
    protected $mediaDirectory;
    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    private $moduleReader;

    protected $viewDir = '';
    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $ioFile;
    /**
     * @var \Mageplaza\GiftCard\Model\TemplateFactory
     */
    private $templateFactory;

    protected $templates = [];

    /**
     * FreeShippingBar constructor.
     *
     * @param SampleDataContext $sampleDataContext
     * @param File $file
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param StockItemInterfaceFactory $stockItemInterfaceFactory
     * @param GuestCartManagementInterface $guestCartManagement
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param CartRepositoryInterface $cartRepository
     * @param CartItemInterfaceFactory $cartItemFactory
     * @param GuestCartItemRepositoryInterface $itemRepository
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        File $file,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        StockItemInterfaceFactory $stockItemInterfaceFactory,
        GuestCartManagementInterface $guestCartManagement,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository,
        CartItemInterfaceFactory $cartItemFactory,
        GuestCartItemRepositoryInterface $itemRepository,
        \Mageplaza\GiftCard\Helper\Template $templateHelper,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Mageplaza\GiftCard\Model\TemplateFactory $templateFactory
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->file = $file;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->stockItemInterfaceFactory = $stockItemInterfaceFactory;
        $this->guestCartManagement = $guestCartManagement;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
        $this->cartItemFactory = $cartItemFactory;
        $this->itemRepository = $itemRepository;
        $this->templateHelper = $templateHelper;
        $this->mediaDirectory = $templateHelper->getMediaDirectory();
        $this->moduleReader = $moduleReader;
        $this->ioFile = $ioFile;
        $this->templateFactory = $templateFactory;
    }

    /**
     * @param array $fixtures
     *
     * @throws Exception
     */
    public function install(array $fixtures)
    {
        foreach ($fixtures as $fileName) {
            $file = $this->fixtureManager->getFixture($fileName);
            if (!$this->ioFile->fileExists($file)) {
                continue;
            }

            $rows = $this->csvReader->setEnclosure("'")->getData($file);
            $header = array_shift($rows);

            switch ($fileName) {
                case 'Mageplaza_GiftCardSampleData::fixtures/mageplaza_giftcard_template.csv':
                    foreach ($rows as $row) {
                        $data = [];
                        foreach ($row as $key => $value) {
                            $data[$header[$key]] = $value;
                        }

                        $data = $this->processTemplateData($data);
                        $template = $this->templateFactory->create()
                            ->addData($data)
                            ->save();
                        $this->templates[] = $template->getId();
                    }
                    break;
                case 'Mageplaza_GiftCardSampleData::fixtures/giftcard.csv':
                    foreach ($rows as $rowKey => $row) {
                        $data = [];
                        foreach ($row as $key => $value) {
                            $data[$header[$key]] = $value;
                        }

                        $data = $this->processProductData($data);

                        $product = $this->productFactory->create();

                        $product = $this->addProductImage($product, $data['image']);

                        unset($data['image']);

                        $product->addData($data)
                            ->setTypeId(\Mageplaza\GiftCard\Model\Product\Type\GiftCard::TYPE_GIFTCARD)
                            ->setAttributeSetId(4)
                            ->setVisibility(Visibility::VISIBILITY_BOTH)
                            ->setStatus(Status::STATUS_ENABLED);

                        /** @var StockItemInterface $stockItem */
                        $stockItem = $this->stockItemInterfaceFactory->create();
                        $stockItem->setQty($data['qty'])
                            ->setIsInStock(true);
                        $extensionAttributes = $product->getExtensionAttributes();
                        $extensionAttributes->setStockItem($stockItem);

                        /** @var ProductRepositoryInterface $productRepository */
                        $productRepository = $this->productRepository;
                        $productRepository->save($product);
                    }
                    break;
                default:
                    return null;
            }
        }
    }

    /**
     * @param Product $product
     * @param $imgPath
     * @return mixed
     * @throws Exception
     */
    protected function addProductImage($product, $imgPath)
    {
        if ($imgPath) {
            $filePath = ltrim($imgPath, '/');
            $pathInfo = $this->ioFile->getPathInfo($filePath);
            $fileName = $pathInfo['basename'];
            $dispersion = $pathInfo['dirname'];
            $file = $this->getFilePath('/files/product/' . $filePath);
            $this->ioFile->checkAndCreateFolder('pub/media/catalog/product/' . $dispersion);
            $fileName = Uploader::getCorrectFileName($fileName);
            $fileName = Uploader::getNewFileName(
                $this->mediaDirectory->getAbsolutePath('/catalog/product/' . $dispersion . '/' . $fileName)
            );
            $destinationFile = $this->mediaDirectory->getAbsolutePath(
                '/catalog/product/' . $dispersion . '/' . $fileName
            );

            $destinationFilePath = $this->mediaDirectory->getAbsolutePath($destinationFile);
            $this->ioFile->cp($file, $destinationFilePath);
            $product->addImageToMediaGallery(
                $destinationFilePath,
                ['image', 'small_image', 'thumbnail']
            );
        }

        return $product;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function processProductData($data)
    {
        $data['gift_card_amounts'] = $data['gift_card_amounts']
            ? Data::jsonDecode($data['gift_card_amounts'])
            : $data['gift_card_amounts'];

        $data['gift_product_template'] = $this->templates;

        return $data;
    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    protected function processTemplateData($data)
    {
        $imagesData = $this->copyTemplateImages(Data::jsonDecode($data['images']));

        $data['images'] = Data::jsonEncode($imagesData);

        if ($data['background_image']) {
            $fileName = $this->copyTemplateBackgroundImage($data['background_image']);
            $data['background_image'] = str_replace('\\', '/', $fileName);
        }

        return $data;
    }

    /**
     * @param $filePath
     * @return string
     * @throws Exception
     */
    protected function copyTemplateBackgroundImage($filePath)
    {
        if (!$filePath) {
            return '';
        }
        $filePath = ltrim($filePath, '/');
        $fileName = $this->ioFile->getPathInfo($filePath)['basename'];
        $file = $this->getFilePath('/files/template/background-image/' . $filePath);
        $this->ioFile->checkAndCreateFolder('pub/media/mageplaza/giftcard');
        $fileName = Uploader::getCorrectFileName($fileName);
        $fileName =
            Uploader::getNewFileName($this->mediaDirectory->getAbsolutePath('/mageplaza/giftcard/' . $fileName));
        $destinationFile = $this->templateHelper->getMediaPath($fileName);
        $destinationFilePath = $this->mediaDirectory->getAbsolutePath($destinationFile);
        $this->ioFile->cp($file, $destinationFilePath);

        return $fileName;
    }

    /**
     * @param $imagesData
     * @return mixed
     * @throws Exception
     */
    protected function copyTemplateImages($imagesData)
    {
        foreach ($imagesData as &$image) {
            $filePath = ltrim($image['file'], '/');
            if (!$filePath) {
                continue;
            }
            $fileName = $this->ioFile->getPathInfo($filePath)['basename'];
            $file = $this->getFilePath('/files/template/images/' . $filePath);
            $fileName = Uploader::getCorrectFileName($fileName);
            $dispersionPath = Uploader::getDispersionPath($fileName);
            $fileName = $dispersionPath . '/' . $fileName;
            $fileName = $this->templateHelper->getNotDuplicatedFilename($fileName, $dispersionPath);
            $destinationFile = $this->templateHelper->getMediaPath($fileName);
            $destinationFilePath = $this->mediaDirectory->getAbsolutePath($destinationFile);
            $pathInfo = $this->ioFile->getPathInfo($destinationFilePath);
            $this->ioFile->checkAndCreateFolder($pathInfo['dirname']);
            $this->ioFile->cp($file, $destinationFilePath);
            $image['file'] = str_replace('\\', '/', $fileName);
        }

        return $imagesData;
    }

    /**
     * @param $path
     * @return string
     */
    protected function getFilePath($path)
    {
        if (!$this->viewDir) {
            $this->viewDir = $this->moduleReader->getModuleDir(
                \Magento\Framework\Module\Dir::MODULE_VIEW_DIR,
                'Mageplaza_GiftCardSampleData'
            );
        }

        return $this->viewDir . $path;
    }
}
