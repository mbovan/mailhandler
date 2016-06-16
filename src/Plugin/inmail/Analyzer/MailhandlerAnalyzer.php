<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Analyzer;

use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;

/**
 * Analyzer for Mailhandler handler plugins.
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "mailhandler",
 *   label = @Translation("Mailhandler Analyzer")
 * )
 */
class MailhandlerAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MessageInterface $message, ProcessorResultInterface $processor_result) {
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResult $result */
    $result = $processor_result->ensureAnalyzerResult(MailhandlerAnalyzerResult::TOPIC, MailhandlerAnalyzerResult::createFactory());

    $this->findSender($message, $result);
    $this->findUser($result);
  }

  /**
   * Finds the sender from given mail message.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResult $result
   *   The analyzer result.
   *
   * @return string|null
   *   The sender of the mail message or null if not found.
   */
  protected function findSender(MessageInterface $message, MailhandlerAnalyzerResult $result) {
    $sender = NULL;
    $matches = [];
    preg_match('/[^@<\s]+@[^@\s>]+/', $message->getFrom(), $matches);

    if (!empty($matches)) {
      $sender = reset($matches);
    }
    $result->setSender($sender);

    return $sender;
  }

  /**
   * Finds a user based on the message sender.
   *
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResult $result
   *   The analyzed message result containing the sender.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user or null if not found.
   */
  protected function findUser(MailhandlerAnalyzerResult $result) {
    $user = NULL;
    $matched_users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $result->getSender()]);

    if (!empty($matched_users)) {
      $user = reset($matched_users);
    }
    $result->setUser($user);

    return $user;
  }

}
