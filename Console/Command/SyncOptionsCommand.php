<?php
/**
 * Sync custom options between products in the same gold_variant_group
 */

declare(strict_types=1);

namespace ElielWeb\ProductConfigurator\Console\Command;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncOptionsCommand extends Command
{
    private const GROUP_OPTION = 'group';
    private const SOURCE_SKU_OPTION = 'source';

    public function __construct(
        private readonly CollectionFactory $productCollectionFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductCustomOptionRepositoryInterface $optionRepository,
        private readonly ProductCustomOptionInterfaceFactory $optionFactory,
        private readonly ProductCustomOptionValuesInterfaceFactory $optionValuesFactory,
        private readonly State $state,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('elielweb:sync-options')
            ->setDescription('Sync custom options between products in the same gold_variant_group')
            ->addOption(
                self::GROUP_OPTION,
                'g',
                InputOption::VALUE_REQUIRED,
                'Gold variant group name to sync'
            )
            ->addOption(
                self::SOURCE_SKU_OPTION,
                's',
                InputOption::VALUE_OPTIONAL,
                'Source product SKU (optional, uses first product in group if not specified)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Area already set
        }

        $groupName = $input->getOption(self::GROUP_OPTION);
        $sourceSku = $input->getOption(self::SOURCE_SKU_OPTION);

        if (empty($groupName)) {
            $output->writeln('<error>Please specify a group name with --group option</error>');
            return Command::FAILURE;
        }

        $output->writeln("<info>Syncing options for group: {$groupName}</info>");

        // Get all products in the group
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('gold_variant_group', $groupName);

        $products = $collection->getItems();

        if (count($products) < 2) {
            $output->writeln('<error>Need at least 2 products in the group to sync</error>');
            return Command::FAILURE;
        }

        // Determine source product
        $sourceProduct = null;
        if ($sourceSku) {
            foreach ($products as $product) {
                if ($product->getSku() === $sourceSku) {
                    $sourceProduct = $product;
                    break;
                }
            }
            if (!$sourceProduct) {
                $output->writeln("<error>Source SKU '{$sourceSku}' not found in group</error>");
                return Command::FAILURE;
            }
        } else {
            // Use first product as source
            $sourceProduct = reset($products);
        }

        $output->writeln("<info>Source product: {$sourceProduct->getSku()}</info>");

        // Get options from source product
        $sourceOptions = $this->optionRepository->getProductOptions($sourceProduct);

        if (empty($sourceOptions)) {
            $output->writeln('<error>Source product has no custom options</error>');
            return Command::FAILURE;
        }

        $output->writeln("<info>Found " . count($sourceOptions) . " options to sync</info>");

        // Sync to other products
        $synced = 0;
        foreach ($products as $product) {
            if ($product->getId() === $sourceProduct->getId()) {
                continue;
            }

            $output->writeln("  Syncing to: {$product->getSku()}");

            // Delete existing options
            $existingOptions = $this->optionRepository->getProductOptions($product);
            foreach ($existingOptions as $existingOption) {
                $this->optionRepository->delete($existingOption);
            }

            // Copy options from source
            foreach ($sourceOptions as $sourceOption) {
                $newOption = $this->optionFactory->create();
                $newOption->setProductSku($product->getSku())
                    ->setTitle($sourceOption->getTitle())
                    ->setType($sourceOption->getType())
                    ->setSortOrder($sourceOption->getSortOrder())
                    ->setIsRequire($sourceOption->getIsRequire())
                    ->setPrice($sourceOption->getPrice())
                    ->setPriceType($sourceOption->getPriceType())
                    ->setMaxCharacters($sourceOption->getMaxCharacters())
                    ->setImageSizeX($sourceOption->getImageSizeX())
                    ->setImageSizeY($sourceOption->getImageSizeY())
                    ->setFileExtension($sourceOption->getFileExtension());

                // Copy option values for select-type options
                $sourceValues = $sourceOption->getValues();
                if ($sourceValues) {
                    $newValues = [];
                    foreach ($sourceValues as $sourceValue) {
                        $newValue = $this->optionValuesFactory->create();
                        $newValue->setTitle($sourceValue->getTitle())
                            ->setSortOrder($sourceValue->getSortOrder())
                            ->setPrice($sourceValue->getPrice())
                            ->setPriceType($sourceValue->getPriceType())
                            ->setSku($sourceValue->getSku());
                        $newValues[] = $newValue;
                    }
                    $newOption->setValues($newValues);
                }

                $this->optionRepository->save($newOption);
            }

            $synced++;
        }

        $output->writeln("<info>Successfully synced options to {$synced} products</info>");

        return Command::SUCCESS;
    }
}
