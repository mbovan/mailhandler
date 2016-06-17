<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Analyzer;

use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResultSigned;
use Drupal\mailhandler_d8\Plugin\inmail\Handler\MailhandlerNode;

/**
 * A message footer analyzer.
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "footer",
 *   label = @Translation("Footer Analyzer")
 * )
 */
class FooterAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MessageInterface $message, ProcessorResultInterface $processor_result) {
    // In order to work with signed messages, get the result of
    // Mailhandler analyzer plugin.
    if (MailhandlerNode::isMessageSigned($processor_result)) {
      /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultSigned $result */
      $result = $processor_result->ensureAnalyzerResult(MailhandlerAnalyzerResultSigned::TOPIC, MailhandlerAnalyzerResultSigned::createFactory());
    }
    else {
      $result = $processor_result->ensureAnalyzerResult(MailhandlerAnalyzerResult::TOPIC, MailhandlerAnalyzerResult::createFactory());
    }

    // Get a message body.
    $body = $result->getBody();

    // @todo: Analyze the body. Identify signature.
  }

}
