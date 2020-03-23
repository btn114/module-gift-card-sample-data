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
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Api\GuestCartItemRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
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
     * @var LogsFactory
     */
    protected $logFactory;

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
     * @param LogsFactory $logFactory
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
        LogsFactory $logFactory
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
        $this->logFactory = $logFactory;
    }

    /**
     * @param array $fixtures
     *
     * @throws Exception
     */
    public function install(array $fixtures)
    {
        // check product is exists
        try {
            $product = $this->productRepository->get('mageplaza_abandoned_cart_sample_product');
        } catch (NoSuchEntityException $e) {
            $product = null;
        }

        // create new sample product if not exits
        if (!$product || !$product->getId()) {

            /** @var Product $product */
            $product = $this->productFactory->create();

        }

        $product->setTypeId('simple')
            ->setAttributeSetId(4)
            ->setName('Mageplaza Abandoned Cart Sample Product')
            ->setSku('mageplaza_abandoned_cart_sample_product')
            ->setPrice(0.01)
            ->setQty(100)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED);

        /** @var StockItemInterface $stockItem */
        $stockItem = $this->stockItemInterfaceFactory->create();
        $stockItem->setQty(100)
            ->setIsInStock(true);
        $extensionAttributes = $product->getExtensionAttributes();
        $extensionAttributes->setStockItem($stockItem);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->productRepository;
        $productRepository->save($product);

        // create new abandoned cart with sample product
        $quoteIdMask = $this->guestCartManagement->createEmptyCart();

        $cartId = $this->maskedQuoteIdToQuoteId->execute($quoteIdMask);
        $cart = $this->cartRepository->get($cartId);
        $cart->setCustomerEmail('nghiabt@mageplaza.com');

        /** @var CartItemInterface $cartItem */
        $cartItem = $this->cartItemFactory->create();
        $cartItem->setQuoteId($quoteIdMask);
        $cartItem->setQty(1);
        $cartItem->setSku('mageplaza_abandoned_cart_sample_product');
        $cartItem->setProductType(Type::TYPE_SIMPLE);

        $this->itemRepository->save($cartItem);

        $this->cartRepository->save($cart);

        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!$this->file->isExists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;
                $row['quote_id'] = $cartId;

                $this->logFactory->create()
                    ->addData($row)
                    ->save();
            }
        }
    }
}
