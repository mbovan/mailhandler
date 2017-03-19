<?php

namespace Drupal\mailhandler\Plugin\inmail\Analyzer;

use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Finds the sender based on "From" mail header field.
 *
 * This analyzer extracts the email address from "From" mail header field and
 * based on this information finds the corresponding user. As this option is not
 * entirely safe, it is disabled by default.
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
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    $result = $processor_result->getAnalyzerResult();

    $this->findSender($message, $result);
  }

  /**
   * Finds the message sender.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   The mail message.
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   */
  protected function findSender(MimeMessageInterface $message, DefaultAnalyzerResult $result) {
    $sender = NULL;
    $user = NULL;
    $matches = [];
    // @todo: Support multiple addresses in https://www.drupal.org/node/2861923
    $from = $message->getFrom()[0]->getAddress();

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
