<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Analyzer;

use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface;

/**
 * A message body analyzer.
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "body",
 *   label = @Translation("Body Analyzer")
 * )
 */
class BodyAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MessageInterface $message, ProcessorResultInterface $processor_result) {
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result */
    $result = $processor_result->ensureAnalyzerResult(MailhandlerAnalyzerResult::TOPIC, MailhandlerAnalyzerResult::createFactory());

    $this->analyzeBody($message, $result);
  }

  /**
   * Analyzes the message body.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   A mail message to be analyzed.
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzer result.
   */
  protected function analyzeBody(MessageInterface $message, MailhandlerAnalyzerResultInterface $result) {
    // Use message processed body if available.
    $body = $result->getBody() ?: $message->getBody();

    // Remove the empty spaces from the beginning and from the end of message.
    $body = trim($body);

    // Add HTML line breaks before all newlines if body doesn't contain tags.
    if($body == strip_tags($body)) {
      $body = nl2br($body);
    }

    // Update the processed body.
    $result->setBody($body);
  }

}
