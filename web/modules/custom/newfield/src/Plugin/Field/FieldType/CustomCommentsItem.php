<?php

namespace Drupal\newfield\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'custom_comments' field type.
 *
 * @FieldType(
 *   id = "custom_comments",
 *   label = @Translation("Custom Comments"),
 *   description = @Translation("Field type for custom comments."),
 *   category = @Translation("Custom"),
 *   default_widget = "custom_comments_default_widget",
 *   default_formatter = "custom_comments_default_formatter"
 * )
 */
class CustomCommentsItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['name'] = DataDefinition::create('string')
      ->setLabel(t('name'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'name' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
        ],
      ],
    ];

    return $schema;
  }
}
