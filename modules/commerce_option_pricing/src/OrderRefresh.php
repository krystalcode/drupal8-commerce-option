<?php

namespace Drupal\commerce_option_pricing;

use Drupal\commerce_order\OrderRefresh as OrderRefreshBase;
use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Alter the order refresh class.
 *
 * Alter the refresh method so as to pass the options value to price resolver.
 */
class OrderRefresh extends OrderRefreshBase {

  /**
   * {@inheritdoc}
   */
  public function refresh(OrderInterface $order) {
    $commerce_product_options = [];
    parent::refresh($order);

    // Loop through each order item and re-calculate the price considering the
    // options price.
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();

      if (!$purchased_entity) {
        continue;
      }

      if ($order_item->isUnitPriceOverridden()) {
        continue;
      }

      if (!$order_item->hasField('field_options')) {
        continue;
      }

      $options = $order_item->field_options->getValue();
      foreach ($options as $option) {
        $commerce_product_options[$option['target_id']] = $option['target_id'];
      }

      $context = new Context(
        $order->getCustomer(),
        $order->getStore(),
        NULL,
        [
          'commerce_product_option' => $commerce_product_options,
        ]
      );
      $unit_price = $this->chainPriceResolver->resolve(
        $purchased_entity,
        $order_item->getQuantity(),
        $context
      );
      $order_item->setUnitPrice($unit_price);
    }
  }

}
