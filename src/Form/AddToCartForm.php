<?php

namespace Drupal\commerce_option\Form;

use Drupal\commerce_cart\Form\AddToCartForm as CommerceAddToCartForm;
use Drupal\commerce\Context;

use Drupal\Core\Form\FormStateInterface;

/**
 * Alters the add to cart form to pass the commerce options to price resolver.
 */
class AddToCartForm extends CommerceAddToCartForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->entity;
    $purchased_entity = $form_state->getValue('purchased_entity');

    // Set the options value in order item entity.
    foreach ($purchased_entity as $variation) {
      foreach ($variation['field_options'] as $option) {
        $options[] = ['target_id' => $option];
      }
    }
    if ($order_item->hasField('field_options')) {
      $order_item->field_options = $options;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $options = [];

    $purchased_entity = $form_state->getValue('purchased_entity');
    foreach ($purchased_entity as $variation) {
      $options = $variation['field_options'];
    }

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    // Now that the purchased entity is set, populate the title and price.
    $purchased_entity = $entity->getPurchasedEntity();
    $entity->setTitle($purchased_entity->getOrderItemTitle());
    if (!$entity->isUnitPriceOverridden()) {
      $store = $this->selectStore($purchased_entity);

      // Pass in options via context to the price resolver.
      $context = new Context(
        $this->currentUser,
        $store,
        NULL,
        ['commerce_product_option' => $options]
      );

      $resolved_price = $this->chainPriceResolver
        ->resolve($purchased_entity, $entity->getQuantity(), NULL, $context);
      $entity->setUnitPrice($resolved_price);
    }

    return $entity;
  }

}
