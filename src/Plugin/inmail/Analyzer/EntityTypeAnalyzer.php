<?php

namespace Drupal\mailhandler\Plugin\inmail\Analyzer;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\DefaultAnalyzerResultInterface;
use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * An entity type and bundle analyzer.
 *
 * @ingroup analyzer
 *
 * Entity type analyzer parses the message subject in order to extract entity
 * type and bundle information.
 *
 * Mail messages intended for Mailhandler processing have
 * "[{entity_type}][{bundle}]" pattern at the beginning of it.
 *
 * This analyzer uses regular expressions and partial matching to extract those
 * data. Both parameters are validated before they are attached to "entity_type"
 * context. In case of a match (entity type and/or bundle is/are detected),
 * those parameters are removed from the processed subject.
 *
 * @Analyzer(
 *   id = "entity_type",
 *   label = @Translation("Entity type and bundle Analyzer")
 * )
 */
class EntityTypeAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MessageInterface $message, ProcessorResultInterface $processor_result) {
    $result = $processor_result->getAnalyzerResult(DefaultAnalyzerResult::TOPIC);

    $this->findEntityType($message, $result);
  }

  /**
   * Analyzes the message subject to extract entity type and bundle information.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\inmail\DefaultAnalyzerResultInterface $result
   *   The analyzed result.
   */
  protected function findEntityType(MessageInterface $message, DefaultAnalyzerResultInterface $result) {
    $subject = $result->getSubject() ?: $message->getSubject();
    $entity_type = NULL;
    $bundle = NULL;

    // Match entity type.
    if (preg_match('/^\[(\w+)\]/', $subject, $matches)) {
      $entity_type = \Drupal::entityTypeManager()->hasDefinition($matches[1]) ? $matches[1] : NULL;
      $subject = str_replace(reset($matches), '', $subject);
      // In case entity type was identified successfully, continue to bundle.
      if ($entity_type && preg_match('/^\[(\w+)\]\s+/', $subject, $matches)) {
        $bundle = $this->getBundle($entity_type, $matches[1]);
        $subject = str_replace(reset($matches), '', $subject);
      }
    }

    // Add entity type context.
    $context_data = [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ];
    $context_definition = new ContextDefinition('any', $this->t('Entity type context'));
    $context = new Context($context_definition, $context_data);
    $result->setContext('entity_type', $context);

    $result->setSubject($subject);
  }

  /**
   * Returns the extracted bundle name.
   *
   * @param string $entity_type
   *   The extracted entity type name.
   * @param string $bundle
   *   The extracted bundle name.
   *
   * @return string|null
   *   The bundle name or null if not valid.
   */
  protected function getBundle($entity_type, $bundle) {
    if (\Drupal::entityTypeManager()->getDefinition($entity_type, FALSE)->hasKey('bundle')) {
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
      if (in_array($bundle, array_keys($bundles))) {
        return $bundle;
      }
    }
    return NULL;
  }

}
