<?php

namespace Drupal\commerce_option\Plugin\Field\FieldWidget;

use Drupal\commerce_option\Entity\ProductOptionInterface;
use Drupal\commerce_option\Resolver\OptionResolverInterface;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_product\ProductAttributeFieldManagerInterface;
use Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationWidgetBase;

use Drupal\Component\Utility\Html;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'commerce_product_variation_options' widget.
 *
 * @FieldWidget(
 *   id = "commerce_product_variation_options",
 *   label = @Translation("Product variation options"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ProductVariationOptionsWidget extends ProductVariationWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The option resolver.
   *
   * @var \Drupal\commerce_option\Resolver\OptionResolverInterface
   */
  protected $optionResolver;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new ProductVariationAttributesWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\commerce_product\ProductAttributeFieldManagerInterface $attribute_field_manager
   *   The product attribute field manager.
   * @param \Drupal\commerce_option\Resolver\OptionResolverInterface $option_resolver
   *   The option resolver.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    EntityTypeManagerInterface $entity_type_manager,
    EntityRepositoryInterface $entity_repository,
    ProductAttributeFieldManagerInterface $attribute_field_manager,
    OptionResolverInterface $option_resolver,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings,
      $entity_type_manager,
      $entity_repository
    );

    $this->optionResolver = $option_resolver;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('commerce_product.attribute_field_manager'),
      $container->get('commerce_option.option_resolver'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $form_state->get('product');
    $variations = $this->loadEnabledVariations($product);

    // Nothing to purchase, tell the parent form to hide itself.
    if (count($variations) === 0) {
      $form_state->set('hide_form', TRUE);
      $element['variation'] = [
        '#type' => 'value',
        '#value' => 0,
      ];

      return $element;
    }

    // This widget only supports one variation.
    if (count($variations) > 1) {
      $form_state->set('hide_form', TRUE);

      $element['variation'] = [
        '#type' => 'value',
        '#value' => 0,
      ];

      $this->loggerFactory
        ->get('commerce_option')
        ->error('
          Product variation options widget only supports one variation.
          Product with the id @id has @count.', [
            '@id' => $product->id(),
            '@count' => count($variations),
          ]
        );

      return $element;
    }

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $selected_variation */
    $selected_variation = reset($variations);

    $form_state->set('selected_variation', $selected_variation->id());

    $element['variation'] = [
      '#type' => 'value',
      '#value' => $selected_variation->id(),
    ];

    // Build the full options form.
    $wrapper_id = Html::getUniqueId('commerce-product-add-to-cart-form');
    $form += [
      '#wrapper_id' => $wrapper_id,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $element['options'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['option-widgets'],
      ],
    ];

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $items->getEntity();
    $form_state->set('order_item', $order_item);

    $purchasableEntity = $order_item->getPurchasedEntity();
    $options = $this->optionResolver->resolveOptions($purchasableEntity);

    foreach ($options as $id => $option) {
      $element['field_options'][$id] = [
        '#type' => $option->getElementType(),
        '#title' => $option->label(),
        '#options' => $this->getOptionValueNames($purchasableEntity, $option),
        '#default_value' => '',
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => [get_class($this), 'ajaxRefresh'],
          'wrapper' => $form['#wrapper_id'],
          // Prevent a jump to the top of the page.
          'disable-refocus' => TRUE,
        ],
      ];
    }

    return $element;
  }

  /**
   * Returns the option value names keyed by id.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasableEntity
   *   The purchasable entity.
   * @param \Drupal\commerce_option\Entity\ProductOptionInterface $option
   *   The option.
   *
   * @return \Drupal\commerce_option\Entity\ProductOptionValueInterface[]
   *   Array of product option value names keyed by id.
   */
  protected function getOptionValueNames(
    PurchasableEntityInterface $purchasableEntity,
    ProductOptionInterface $option
  ) {
    $optionValues = $this->optionResolver
      ->resolveOptionValues($purchasableEntity, $option);

    $names = [];
    foreach ($optionValues as $optionValue) {
      $names[$optionValue->id()] = $optionValue->getName();
    }

    return $names;
  }

}
