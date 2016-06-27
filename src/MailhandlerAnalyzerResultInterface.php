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
   * Returns the detected entity type.
   *
   * @return string|null
   *   The detected entity type.
   */
  public function getEntityType();

  /**
   * Sets the detected entity type.
   *
   * @param string $entity_type
   *   The detected entity type.
   */
  public function setEntityType($entity_type);

  /**
   * Returns the detected bundle.
   *
   * @return string|null
   *   The detected bundle.
   */
  public function getBundle();

  /**
   * Sets the detected bundle.
   *
   * @param string $bundle
   *   The detected bundle.
   */
  public function setBundle($bundle);

  /**
   * Returns TRUE if message is signed. Otherwise, FALSE.
   *
   * @return bool
   *   A flag whether a message is signed or not.
   */
  public function isSigned();

  /**
   * Returns the PGP signature.
   *
   * @return string|bool
   *   The PGP signature or FALSE if PGP type is "inline".
   */
  public function getSignature();

  /**
   * Sets the PGP signature.
   *
   * @param string $signature
   *   The PGP signature.
   */
  public function setSignature($signature);

  /**
   * Returns the signed text.
   *
   * @return string
   *   The signed text.
   */
  public function getSignedText();

  /**
   * Sets the signed text.
   *
   * @param string $signed_text
   *   The signed text.
   */
  public function setSignedText($signed_text);

  /**
   * Returns the PGP type.
   *
   * @return string
   *   The PGP type.
   */
  public function getPgpType();

  /**
   * Sets the PGP type.
   *
   * @param string $pgp_type
   *   The PGP type.
   */
  public function setPgpType($pgp_type);

  /**
   * A flag whether signed message is verified or not.
   *
   * @param bool $verified
   *   The verification status.
   *
   * @return bool
   *   TRUE if signed message is verified. Otherwise, FALSE.
   */
  public function setVerified($verified);

  /**
   * A flag whether a message is verified or not.
   *
   * @return bool
   *   TRUE if signed message is verified.
   */
  public function isVerified();

}
