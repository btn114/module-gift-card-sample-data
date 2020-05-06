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

namespace Mageplaza\GiftCardSampleData\Setup;

use Exception;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Mageplaza\GiftCard\Model\Product\Type\GiftCard;

/**
 * Class Uninstall
 * @package Mageplaza\GiftCardSampleData\Setup
 */
class Uninstall implements UninstallInterface
{
    /**
     * @var CollectionFactory
     */
    private $prdCollectionFactory;
    /**
     * @var State
     */
    private $state;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * Uninstall constructor.
     * @param State $state
     * @param Registry $registry
     * @param CollectionFactory $prdCollectionFactory
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        State $state,
        Registry $registry,
        CollectionFactory $prdCollectionFactory,
        CategoryFactory $categoryFactory
    ) {
        $this->prdCollectionFactory = $prdCollectionFactory;
        $this->state = $state;
        $this->registry = $registry;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws LocalizedException
     * @throws Exception
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);
        $this->registry->register('isSecureArea', true);
        $this->prdCollectionFactory->create()->addFilter('type_id', GiftCard::TYPE_GIFTCARD)->delete();
        $this->registry->unregister('isSecureArea');

        $category = $this->categoryFactory->create()->loadByAttribute('url_key', 'mageplaza-giftcard');

        if ($category) {
            $category->delete();
        }

        $connection = $setup->getConnection();

        $tables = ['mageplaza_giftcard', 'mageplaza_giftcard_pool', 'mageplaza_giftcard_history'];
        foreach ($tables as $tableName) {
            $table = $setup->getTable($tableName);
            $connection->delete($table);
        }
    }
}
