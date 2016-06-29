<?php

namespace Drupal\mailhandler_d8;

use Drupal\inmail\AnalyzerResultInterface;

/**
 * Contains Mailhandler analyzer results.
 *
 * The setter methods only have effect the first time they are called, so values
 * are only writable once.
 *
 * @ingroup analyzer
 */
class MailhandlerAnalyzerResult implements AnalyzerResultInterface, MailhandlerAnalyzerResultInterface {

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
   * The message subject.
   *
   * @var string
   */
  protected $subject;

  /**
   * The detected entity type.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The detected bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The PGP signature.
   *
   * In case the PGP type is "inline", the signature is FALSE.
   *
   * @var string|bool
   */
  protected $signature;

  /**
   * The signed text.
   *
   * @var string
   */
  protected $signedText;

  /**
   * The PGP type.
   *
   * Represents the type of the message. Could be "inline" (message text is
   * signed and embedded as a plain text into message body)
   * or "mime" (message is sent as PGP/MIME type).
   *
   * @var string
   */
  protected $pgpType;

  /**
   * A flag that determines whether message is verified.
   *
   * @var bool
   */
  protected $verified;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return t('Mailhandler');
  }

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
    if ($this->getSubject()) {
      $summary['subject'] = $this->getSubject();
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

  /**
   * @inheritDoc
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * @inheritDoc
   */
  public function setSubject($subject) {
    $this->subject = $subject;
  }

  /**
   * @inheritDoc
   */
  public function getEntityType() {
    return $this->entity_type;
  }

  /**
   * @inheritDoc
   */
  public function setEntityType($entity_type) {
    $this->entity_type = $entity_type;
  }

  /**
   * @inheritDoc
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * @inheritDoc
   */
  public function setBundle($bundle) {
    $this->bundle = $bundle;
  }

  /**
   * @inheritDoc
   */
  public function isSigned() {
    return (bool) $this->signature;
  }

  /**
   * @inheritDoc
   */
  public function getSignature() {
    return $this->signature;
  }

  /**
   * @inheritDoc
   */
  public function setSignature($signature) {
    $this->signature = $signature;
  }

  /**
   * @inheritDoc
   */
  public function getSignedText() {
    return $this->signedText;
  }

  /**
   * @inheritDoc
   */
  public function setSignedText($signed_text) {
    $this->signedText = $signed_text;
  }

  /**
   * @inheritDoc
   */
  public function getPgpType() {
    return $this->pgpType;
  }

  /**
   * @inheritDoc
   */
  public function setPgpType($pgp_type) {
    $this->pgpType = $pgp_type;
  }

  /**
   * @inheritDoc
   */
  public function setVerified($verified) {
    $this->verified = $verified;
  }

  /**
   * @inheritDoc
   */
  public function isVerified() {
    return $this->verified;
  }

}
