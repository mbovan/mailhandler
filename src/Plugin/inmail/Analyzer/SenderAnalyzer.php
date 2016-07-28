<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Analyzer;

use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\DefaultAnalyzerResultInterface;
use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Finds the sender based on "From" mail header field.
 *
 * As this option is not entirely safe, it is disabled by default.
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "sender",
 *   label = @Translation("User Sender Analyzer")
 * )
 */
class SenderAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MessageInterface $message, ProcessorResultInterface $processor_result) {
    $result = $processor_result->getAnalyzerResult(DefaultAnalyzerResult::TOPIC);

    $this->findSender($message, $result);
  }

  /**
   * Finds the message sender.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\inmail\DefaultAnalyzerResultInterface $result
   *   The analyzer result.
   */
  protected function findSender(MessageInterface $message, DefaultAnalyzerResultInterface $result) {
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

    // Do not override a sender/account in case there is already one set.
    if (!$result->getSender()) {
      $result->setSender($sender);
    }
    if ($user && !$result->isUserAuthenticated()) {
      $result->setAccount($user);
    }
  }

}
