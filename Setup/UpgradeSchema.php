<?php

namespace Omise\Payment\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Omise\Payment\Model\Data\Card;


class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '2.3.1', '<')) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable(Card::TABLE))
                ->addColumn(
                    'customer_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false]
                )
                ->addColumn(
                    'omise_customer',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['unsigned' => true, 'nullable' => false]
                );
            $setup->getConnection()->createTable($table);
        }

        $setup->endSetup();
    }

}
