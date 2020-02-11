<?php

namespace Drupal\commerce_option\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the product option value entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_option_option_value",
 *   label = @Translation("Product option value"),
 *   label_singular = @Translation("product option value"),
 *   label_plural = @Translation("product option values"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product option value",
 *     plural = "@count product optioin values",
 *   ),
 *   bundle_label = @Translation("Product option"),
 *   handlers = {
 *     "event" = "Drupal\commerce_option\Event\ProductOptionValueEvent",
 *     "storage" = "Drupal\commerce_option\ProductOptionValueStorage",
 *     "access" = "Drupal\commerce_option\ProductOptionValueAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\commerce\CommerceEntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   admin_permission = "administer commerce_option_product_option",
 *   translatable = TRUE,
 *   content_translation_ui_skip = TRUE,
 *   base_table = "commerce_option_option_value",
 *   data_table = "commerce_option_option_value_field_data",
 *   entity_keys = {
 *     "id" = "option_value_id",
 *     "bundle" = "product_option",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 *   bundle_entity_type = "commerce_option_product_option",
 *   field_ui_base_route = "entity.commerce_option_product_option.edit_form",
 * )
 */
class ProductOptionValue extends ContentEntityBase implements ProductOptionValueInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getProductOption() {
    $storage = $this->entityTypeManager()
      ->getStorage('commerce_option_product_option');
    return $storage->load($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getProductOptionId() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    // Override the label for the generated bundle field.
    $fields['product_option']->setLabel(t('Option'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The product option value name.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this product option value in relation to others.'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the product option value was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the product option value was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
