<?php

namespace Drupal\mailhandler\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'mailhandler_gpg' widget.
 *
 * @FieldWidget(
 *   id = "mailhandler_gpg",
 *   label = @Translation("GPG Key widget"),
 *   field_types = {
 *     "mailhandler_gpg"
 *   }
 * )
 */
class GpgKeyWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'rows' => '20',
      'placeholder' => t("Begins with '-----BEGIN PGP PUBLIC KEY BLOCK-----'"),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'fieldset';
    $element['#tree'] = TRUE;

    $element['public_key'] = [
      '#type' => 'textarea',
      '#title' => $items[$delta]->get('public_key')->getDataDefinition()->getLabel(),
      '#description' => $items[$delta]->get('public_key')->getDataDefinition()->getDescription(),
      '#default_value' => $items[$delta]->public_key,
      '#rows' => $this->getSetting('rows'),
      '#format' => 'varchar_ascii',
      '#placeholder' => $this->getSetting('placeholder'),
    ];
    $element['fingerprint'] = [
      '#type' => 'textfield',
      '#default_value' => $items[$delta]->fingerprint,
      '#disabled' => TRUE,
      '#title' => $items[$delta]->get('fingerprint')->getDataDefinition()->getLabel(),
      '#description' => $items[$delta]->get('fingerprint')->getDataDefinition()->getDescription(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['rows'] = [
      '#type' => 'number',
      '#title' => t('Rows'),
      '#default_value' => $this->getSetting('rows'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Number of rows: @rows', ['@rows' => $this->getSetting('rows')]);
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }

    return $summary;
  }

}
