<?php

namespace Drupal\commerce_option_pricing\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_option_pricing\Event\OptionPriceResolverEvent;
use Drupal\commerce_option_pricing\Event\OptionPricingEvents;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\Resolver\PriceResolverInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Calculates the price based on base price and the option price.
 */
class OptionPriceResolver implements PriceResolverInterface {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * OptionPriceResolver constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    if ($entity->id() !== $this->tempStoreFactory->get('commerce_option')->get('variation_id')) {
      return NULL;
    }

    $optionIds = $this->tempStoreFactory->get('commerce_option')->get('options');
    $options = $this->entityTypeManager
      ->getStorage('commerce_option_option_value')
      ->loadMultiple($optionIds);

    if (!$options) {
      return NULL;
    }

    $addedPrice = 0;
    foreach ($options as $option) {
      $addedPrice = $addedPrice + $option->pricing->first()->toPrice()->getNumber();
    }

    $field_name = $context->getData('field_name', 'price');
    if ($field_name == 'price') {
      $price = $entity->getPrice()->getNumber();
      $totalPrice = Calculator::multiply(Calculator::add($price, $addedPrice), $quantity);
      $currencyCode = $entity->getPrice()->getCurrencyCode();

      $event = new OptionPriceResolverEvent($entity, $quantity, $options, $totalPrice);
      $this->eventDispatcher
        ->dispatch(OptionPricingEvents::OPTION_PRICING_RESOLVE, $event);
      $totalPrice = $event->getTotalPrice();

      return new Price($totalPrice, $currencyCode);
    }
    elseif ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
      $price = $entity->get($field_name)->first()->toPrice()->getNumber();
      $totalPrice = Calculator::multiply(Calculator::add($price, $addedPrice), $quantity);;
      $currencyCode = $entity->get($field_name)->first()->toPrice()->getCurrencyCode();

      $event = new OptionPriceResolverEvent($entity, $quantity, $options, $totalPrice);
      $this->eventDispatcher
        ->dispatch(OptionPricingEvents::OPTION_PRICING_RESOLVE, $event);
      $totalPrice = $event->getTotalPrice();

      return new Price($totalPrice, $currencyCode);
    }

    return NULL;
  }

}
