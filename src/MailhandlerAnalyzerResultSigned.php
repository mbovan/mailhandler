<?php

namespace Drupal\mailhandler_d8;

/**
 * Contains Mailhandler analyzer results for signed messages.
 *
 * @ingroup analyzer
 */
class MailhandlerAnalyzerResultSigned extends MailhandlerAnalyzerResultBase {

  /**
   * Identifies this class in relation to other analyzer results.
   *
   * Use this as the $topic argument for ProcessorResultInterface methods.
   *
   * @see \Drupal\inmail\ProcessorResultInterface
   */
  const TOPIC = 'mailhandler_signed';

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
   * {@inheritdoc}
   */
  public function label() {
    return t('Signed analyzer result');
  }

  /**
   * Returns the PGP signature.
   *
   * @return string|bool
   *   The PGP signature or FALSE if PGP type is "inline".
   */
  public function getSignature() {
    return $this->signature;
  }

  /**
   * Sets the PGP signature.
   *
   * @param string $signature
   *   The PGP signature.
   */
  public function setSignature($signature) {
    $this->signature = $signature;
  }

  /**
   * Returns the signed text.
   *
   * @return string
   *   The signed text.
   */
  public function getSignedText() {
    return $this->signedText;
  }

  /**
   * Sets the signed text.
   *
   * @param string $signed_text
   *   The signed text.
   */
  public function setSignedText($signed_text) {
    $this->signedText = $signed_text;
  }

  /**
   * Returns the PGP type.
   *
   * @return string
   *   The PGP type.
   */
  public function getPgpType() {
    return $this->pgpType;
  }

  /**
   * Sets the PGP type.
   *
   * @param string $pgp_type
   *   The PGP type.
   */
  public function setPgpType($pgp_type) {
    $this->pgpType = $pgp_type;
  }

}
