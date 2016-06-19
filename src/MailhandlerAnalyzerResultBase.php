<?php

namespace Drupal\mailhandler_d8;

use Drupal\inmail\AnalyzerResultInterface;

/**
 * Represents a base for MailhandlerResult objects.
 *
 * @ingroup analyzer
 */
abstract class MailhandlerAnalyzerResultBase implements AnalyzerResultInterface, MailhandlerAnalyzerResultInterface {

  /**
   * The sender.
   *
   * @var string
   */
  protected $sender;

  /**
   * The user
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The analyzed body of the message.
   *
   * @var string
   */
  protected $body;

  /**
   * The message footer
   *
   * @var string
   */
  protected $footer;

  /**
   * Returns a function closure that in turn returns a new class instance.
   *
   * @return callable
   *   A factory closure that returns a new MailhandlerAnalyzerResult object
   *   when called.
   */
  public static function createFactory() {
    return function() {
      return new static();
    };
  }

  /**
   * {@inheritdoc}
   */
  public function setSender($sender) {
    if (!isset($this->sender)) {
      $this->sender = $sender;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSender() {
    return $this->sender;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser($user) {
    if (!isset($this->user)) {
      $this->user = $user;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function isUserAuthenticated() {
    return $this->user ? $this->user->isAuthenticated() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summarize() {
    $summary = [];
    if ($this->getSender()) {
      $summary['sender'] = $this->getSender();
    }
    if ($this->getUser()) {
      $summary['user'] = $this->getUser()->getDisplayName();
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody($body) {
    $this->body = $body;
  }

  /**
   * {@inheritdoc}
   */
  public function getFooter() {
    return $this->footer;
  }

  /**
   * {@inheritdoc}
   */
  public function setFooter($footer) {
    $this->footer = $footer;
  }

}
