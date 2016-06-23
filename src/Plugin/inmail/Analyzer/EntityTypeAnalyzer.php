<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Analyzer;

use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface;

/**
 * An entity type and bundle analyzer.
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "entity_type",
 *   label = @Translation("Extracts the entity type and bundle from the mail subject")
 * )
 */
class EntityTypeAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MessageInterface $message, ProcessorResultInterface $processor_result) {
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResult $result */
    $result = $processor_result->ensureAnalyzerResult(MailhandlerAnalyzerResult::TOPIC, MailhandlerAnalyzerResult::createFactory());

    $this->findEntityType($message, $result);
  }

  /**
   * Analyzes the message subject.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzed message result.
   */
  protected function findEntityType(MessageInterface $message, MailhandlerAnalyzerResultInterface $result) {
    $subject = $message->getSubject();
    $entity_type = NULL;
    $bundle = NULL;

    if (preg_match('/^\[(\w+)\]\[(\w+)\]\s+/', $subject, $matches)) {
      $entity_type = \Drupal::entityTypeManager()->hasDefinition($matches[1]) ? $matches[1] : NULL;
      $bundle = $this->getBundle($entity_type, $matches[2]);
      $subject = str_replace(reset($matches), '', $subject);
    }

    $result->setEntityType($entity_type);
    $result->setBundle($bundle);
    $result->setSubject($subject);
  }

  /**
   * @param $entity_type
   * @param $bundle
   * @return null
   */
  protected function getBundle($entity_type, $bundle) {
    if (\Drupal::entityTypeManager()->getDefinition($entity_type, FALSE)->hasKey('bundle')) {
      $bundles = \Drupal::entityManager()->getBundleInfo($entity_type);
      if (in_array($bundle, $bundles)) {
        return $bundle;
      }
    }
    return NULL;
  }

}
