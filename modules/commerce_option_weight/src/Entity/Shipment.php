<?php

namespace Drupal\commerce_option_weight\Entity;

use Drupal\commerce_option\Entity\ProductOptionValue;

use Drupal\commerce_shipping\Entity\Shipment as CoreShipment;

use Drupal\commerce_order\Entity\OrderItem;

/**
 * Extends the shipment entity class and modifies the recalculate weight method.
 *
 * Adds the option weight value to shipment's total weight.
 */
class Shipment extends CoreShipment {

  /**
   * Recalculates the shipment's weight.
   */
  protected function recalculateWeight() {
    if (!$this->hasItems()) {
      // Can't calculate the weight if the items are still unavailable.
      return;
    }

    /** @var \Drupal\physical\Weight $weight */
    $weight = NULL;

    foreach ($this->getItems() as $shipment_item) {
      $shipment_item_weight = $shipment_item->getWeight();
      $weight = $weight ? $weight->add($shipment_item_weight) : $shipment_item_weight;

      if (!$shipment_item->getOrderItemId()) {
        continue;
      }

      $order_item = OrderItem::load(
        $shipment_item->getOrderItemId()
      );

      if (!$order_item->hasField('field_options')) {
        continue;
      }

      // Loop through each order item and add the product option weight to the
      // shipment weight.
      $options = $order_item->field_options->getValue();
      foreach ($options as $option) {
        $product_option = ProductOptionValue::load($option['target_id']);

        if (!$product_option->get('shipping_weight')->first()) {
          continue;
        }

        $weight = $weight->add(
          $product_option->get('shipping_weight')->first()->toMeasurement()
        );
      }

    }

    if ($package_type = $this->getPackageType()) {
      $package_type_weight = $package_type->getWeight();
      $weight = $weight->add($package_type_weight);
    }

    $this->setWeight($weight);
  }

}
