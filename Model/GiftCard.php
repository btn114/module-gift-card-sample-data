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
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\MediaStorage\Model\File\Uploader;
use Mageplaza\GiftCard\Helper\Data;
use Mageplaza\GiftCard\Helper\Template;
use Mageplaza\GiftCard\Model\GiftCardFactory;
use Mageplaza\GiftCard\Model\LogsFactory;
use Mageplaza\GiftCard\Model\PoolFactory;
use Mageplaza\GiftCard\Model\TemplateFactory;

/**
 * Class GiftCard
 * @package Mageplaza\GiftCardSampleData\Model
 */
class GiftCard
{
    /**
     * @var FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var Csv
     */
    protected $csvReader;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var StockItemInterfaceFactory
     */
    protected $stockItemInterfaceFactory;

    /**
     * @var Template
     */
    protected $templateHelper;

    /**
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var Reader
     */
    protected $moduleReader;

    /**
     * @var string
     */
    protected $viewDir = '';

    /**
     * @var File
     */
    protected $ioFile;

    /**
     * @var TemplateFactory
     */
    protected $templateFactory;

    /**
     * @var array
     */
    protected $templates = [];

    /**
     * @var PoolFactory
     */
    protected $poolFactory;

    /**
     * @var GiftCardFactory
     */
    protected $giftCardFactory;

    /**
     * GiftCard constructor.
     * @param SampleDataContext $sampleDataContext
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactory $productFactory
     * @param StockItemInterfaceFactory $stockItemInterfaceFactory
     * @param Template $templateHelper
     * @param Reader $moduleReader
     * @param File $ioFile
     * @param TemplateFactory $templateFactory
     * @param PoolFactory $poolFactory
     * @param GiftCardFactory $giftCardFactory
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        StockItemInterfaceFactory $stockItemInterfaceFactory,
        Template $templateHelper,
        Reader $moduleReader,
        File $ioFile,
        TemplateFactory $templateFactory,
        PoolFactory $poolFactory,
        GiftCardFactory $giftCardFactory
    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->stockItemInterfaceFactory = $stockItemInterfaceFactory;
        $this->templateHelper = $templateHelper;
        $this->mediaDirectory = $templateHelper->getMediaDirectory();
        $this->moduleReader = $moduleReader;
        $this->ioFile = $ioFile;
        $this->templateFactory = $templateFactory;
        $this->poolFactory = $poolFactory;
        $this->giftCardFactory = $giftCardFactory;
    }

    /**
     * @param array $fixtures
     *
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
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
                case 'Mageplaza_GiftCardSampleData::fixtures/mageplaza_giftcard_pool.csv':
                    foreach ($rows as $row) {
                        $data = [];
                        foreach ($row as $key => $value) {
                            $data[$header[$key]] = $value;
                        }

                        $data = $this->processPoolData($data);
                        $pool = $this->poolFactory->create()
                            ->addData($data)
                            ->save();
                        $giftCard = $this->giftCardFactory->create()
                            ->setData($pool->getData())
                            ->addData([
                                'pattern' => '[4AN]-[4A]-[4N]',
                                'pool_id' => $pool->getId(),
                                'extra_content' => 'admin',
                                'action_vars' => Data::jsonEncode(['pool_id' => $pool->getId()])
                            ]);

                        $giftCard->createMultiple(['qty' => 5]);
                    }
                    break;
                default:
            }
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function processPoolData($data)
    {
        if ($data['image']) {
            $fileName = $this->ioFile->getPathInfo($data['image'])['filename'];
            $template = $this->templateFactory->create()->getCollection()
                ->addFieldToFilter('images', ['like' => '%' . $fileName . '%'])->getFirstItem();
            if ($templateId = $template->getId()) {
                $data['template_id'] = $templateId;
            } else {
                unset($data['image']);
            }
        }

        return $data;
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
                Dir::MODULE_VIEW_DIR,
                'Mageplaza_GiftCardSampleData'
            );
        }

        return $this->viewDir . $path;
    }
}
