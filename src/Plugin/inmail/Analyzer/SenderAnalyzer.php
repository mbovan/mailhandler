<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Analyzer;

use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface;

/**
 * A sender analyzer.
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "sender",
 *   label = @Translation("Finds the user.")
 * )
 */
class SenderAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MessageInterface $message, ProcessorResultInterface $processor_result) {
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResult $result */
    $result = $processor_result->ensureAnalyzerResult(MailhandlerAnalyzerResult::TOPIC, MailhandlerAnalyzerResult::createFactory());

    $this->findSender($message, $result);
  }

  /**
   * Finds the message sender.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzed message result.
   */
  protected function findSender(MessageInterface $message, MailhandlerAnalyzerResultInterface $result) {
    $sender = NULL;
    $user = NULL;
    $matches = [];
    $from = $message->getFrom();

    preg_match('/[^@<\s]+@[^@\s>]+/', $from, $matches);
    if (!empty($matches)) {
      $sender = reset($matches);
    }

    $matched_users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $sender]);
    if (!empty($matched_users)) {
      $user = reset($matched_users);
    }

    $result->setSender($sender);
    if ($user && !$result->isUserAuthenticated()) {
      $result->setUser($user);
    }
  }

}
