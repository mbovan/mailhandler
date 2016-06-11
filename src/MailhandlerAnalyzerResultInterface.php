<?php

namespace Drupal\mailhandler_d8;

/**
 * An analyzer result collects analysis reports within a certain topic.
 *
 * Every inheriting class should provide setters and getters for properties
 * within the topic that it covers.
 *
 * @ingroup analyzer
 */
interface MailhandlerAnalyzerResultInterface {

  /**
   * Sets the sender mail address.
   *
   * @param string $sender
   *   The address of the sender.
   */
  public function setSender($sender);

  /**
   * Returns the sender of the message.
   *
   * @return string|null
   *   The address of the sender, or NULL if it is found.
   */
  public function getSender();

  /**
   * Sets a user.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user.
   */
  public function setUser($user);

  /**
   * Returns a user object.
   *
   * @return \Drupal\user\UserInterface
   *   The user object.
   */
  public function getUser();

  /**
   * Tells the status of user authentication.
   *
   * @return bool
   *   TRUE if user is authenticated. Otherwise, FALSE;
   */
  public function isUserAuthenticated();
}
