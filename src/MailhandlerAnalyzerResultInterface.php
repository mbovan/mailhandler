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

  /**
   * Returns the analyzed body of the message.
   *
   * @return string
   *   The analyzed body of the message.
   */
  public function getBody();

  /**
   * Sets the analyzed message body.
   *
   * @param string $body
   *   The analyzed message body.
   */
  public function setBody($body);

  /**
   * Returns the message footer.
   *
   * @return string
   *   The footer of the message.
   */
  public function getFooter();

  /**
   * Sets the message footer.
   *
   * @param string $footer
   *   The message footer.
   */
  public function setFooter($footer);

  /**
   * Returns the analyzed message subject.
   *
   * @return string
   *   The subject of the message.
   */
  public function getSubject();

  /**
   * Sets the actual message subject.
   *
   * @param string $subject
   *   The analyzed message subject.
   */
  public function setSubject($subject);

  /**
   * Returns the detected content type.
   *
   * @return string|null
   *   The detected content type of the message.
   */
  public function getContentType();

  /**
   * Sets the detected content type.
   *
   * @param string $detected_content_type
   *   The detected content type.
   */
  public function setContentType($detected_content_type);

}
