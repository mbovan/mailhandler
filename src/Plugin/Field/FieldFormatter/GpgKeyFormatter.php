<?php

namespace Drupal\mailhandler\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'mailhandler_gpg' formatter.
 *
 * @FieldFormatter(
 *   id = "mailhandler_gpg",
 *   label = @Translation("GPG Key formatter"),
 *   field_types = {
 *     "mailhandler_gpg",
 *   }
 * )
 */
class GpgKeyFormatter extends FormatterBase {

  /**
   * The display options
   *
   * @var string
   */
  protected $display_options;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display' => 'fingerprint',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $display_options = [
      'all' => t('Public key + Fingerprint'),
      'public_key' => t('Public key'),
      'fingerprint' => t('Fingerprint'),
    ];

    $element['display'] = [
      '#title' => t('Display'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('display'),
      '#options' => $display_options,
      '#description' => t('Select a property to display.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = t('Display: @property', ['@property' => $this->getSetting('display')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $display_options = $this->getSetting('display') === 'all' ? ['public_key', 'fingerprint'] : [$this->getSetting('display')];
      foreach ($display_options as $property) {
        $elements[$delta][$property] = [
          '#type' => 'item',
          '#title' => $item->get($property)->getDataDefinition()->getLabel(),
        ];
        $elements[$delta][$property][] = [
          '#type' => 'processed_text',
          '#text' => $item->$property,
          '#langcode' => $item->getLangcode(),
        ];
      }
    }

    return $elements;
  }

}
