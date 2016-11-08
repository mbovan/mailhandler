<?php

namespace Drupal\mailhandler\Plugin\inmail\Analyzer;

use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * A message body analyzer.
 *
 * @ingroup analyzer
 *
 * This analyzer works with a message body processed by other analyzers in the
 * queue or with a original message body in case there are no body-related
 * analyzer. As of now, it is very primitive in its features. The only thing it
 * does is trimming the white spaces before and after the message body. Also,
 * in case of HTML body, this analyzer converts new lines to HTML <br /> tags.
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
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    $result = $processor_result->getAnalyzerResult();

    $this->analyzeBody($message, $result);
  }

  /**
   * Analyzes the message body and updates it.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   A mail message to be analyzed.
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   */
  protected function analyzeBody(MimeMessageInterface $message, DefaultAnalyzerResult $result) {
    // Get the processed body if available. Otherwise, fallback to default one.
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
