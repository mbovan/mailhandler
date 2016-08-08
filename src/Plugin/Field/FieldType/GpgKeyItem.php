<?php

namespace Drupal\mailhandler\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'mailhandler_gpg' field type.
 *
 * @FieldType(
 *   id = "mailhandler_gpg",
 *   label = @Translation("GPG Key"),
 *   module = "mailhandler",
 *   description = @Translation("Represents a GPG Key field."),
 *   default_widget = "mailhandler_gpg",
 *   default_formatter = "mailhandler_gpg"
 * )
 */
class GpgKeyItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'public_key' => [
          'type' => 'text',
          'size' => 'big',
          'description' => 'GPG Public key.',
        ],
        'fingerprint' => [
          'type' => 'text',
          'size' => 'normal',
          'description' => 'Fingerprint of the corresponding public key.',
        ],
      ],
      'indexes' => [
        'fingerprint' => ['fingerprint'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'public_key';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $public_key = $this->get('public_key')->getValue();
    return $public_key === NULL || $public_key === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['public_key'] = DataDefinition::create('string')
      ->setLabel(t('Public key'))
      ->setDescription(t('GPG Public key.'));

    $properties['fingerprint'] = DataDefinition::create('string')
      ->setLabel(t('Fingerprint'))
      ->setDescription(t('Fingerprint of the corresponding public key. This property will be automatically populated.'));

    return $properties;
  }

}
