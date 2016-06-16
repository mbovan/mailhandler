<?php

namespace Drupal\mailhandler_d8;

use Drupal\inmail\AnalyzerResultInterface;
use Drupal\user\UserInterface;

/**
 * Contains Mailhandler analyzer results.
 *
 * The setter methods only have effect the first time they are called, so values
 * are only writable once.
 *
 * @ingroup analyzer
 */
class MailhandlerAnalyzerResult implements AnalyzerResultInterface {

  /**
   * Identifies this class in relation to other analyzer results.
   *
   * Use this as the $topic argument for ProcessorResultInterface methods.
   *
   * @see \Drupal\inmail\ProcessorResultInterface
   */
  const TOPIC = 'mailhandler';

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
   * Sets the sender mail address.
   *
   * @param string $sender
   *   The address of the sender.
   */
  public function setSender($sender) {
    if (!isset($this->sender)) {
      $this->sender = $sender;
    }
  }

  /**
   * Returns the sender of the message.
   *
   * @return string|null
   *   The address of the sender, or NULL if it is found.
   */
  public function getSender() {
    return $this->sender;
  }

  /**
   * Sets a user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   */
  public function setUser(UserInterface $user) {
    if (!isset($this->user)) {
      $this->user = $user;
    }
  }

  /**
   * Returns a user object.
   *
   * @return \Drupal\user\UserInterface
   *   The user object.
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Tells the status of user authentication.
   *
   * @return bool
   *   TRUE if user is authenticated. Otherwise, FALSE;
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
  public function label() {
    return t('Mailhandler');
  }

}
