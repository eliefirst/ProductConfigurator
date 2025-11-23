<?php
/**
 * Observer to sync custom options between products in the same gold_variant_group
 */

declare(strict_types=1);

namespace ElielWeb\ProductConfigurator\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterfaceFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class SyncProductOptionsObserver implements ObserverInterface
{
    public function __construct(
        private readonly CollectionFactory $productCollectionFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductCustomOptionRepositoryInterface $optionRepository,
        private readonly ProductCustomOptionInterfaceFactory $optionFactory,
        private readonly ProductCustomOptionValuesInterfaceFactory $optionValuesFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        if (!$product) {
            return;
        }

        $variantGroup = $product->getData('gold_variant_group');

        // Only sync if product has a variant group
        if (empty($variantGroup)) {
            return;
        }

        // Get options from this product
        $sourceOptions = $this->optionRepository->getProductOptions($product);

        // Only sync if product has options
        if (empty($sourceOptions)) {
            return;
        }

        try {
            // Get all other products in the same group
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('*')
                ->addAttributeToFilter('gold_variant_group', $variantGroup)
                ->addAttributeToFilter('entity_id', ['neq' => $product->getId()]);

            foreach ($collection as $targetProduct) {
                $this->syncOptionsToProduct($sourceOptions, $targetProduct);
            }

            $this->logger->info(
                "Synced options from {$product->getSku()} to " . $collection->getSize() . " products in group '{$variantGroup}'"
            );
        } catch (\Exception $e) {
            $this->logger->error("Error syncing options: " . $e->getMessage());
        }
    }

    /**
     * Sync options to a target product
     */
    private function syncOptionsToProduct(array $sourceOptions, $targetProduct): void
    {
        // Delete existing options
        $existingOptions = $this->optionRepository->getProductOptions($targetProduct);
        foreach ($existingOptions as $existingOption) {
            $this->optionRepository->delete($existingOption);
        }

        // Copy options from source
        foreach ($sourceOptions as $sourceOption) {
            $newOption = $this->optionFactory->create();
            $newOption->setProductSku($targetProduct->getSku())
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
    }
}
