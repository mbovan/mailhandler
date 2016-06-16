<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Analyzer;

use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\MIME\MultipartMessage;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResultSigned;

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
    // Ensure analyzer result instance.
    $context = [];
    if ($this->isSigned($message, $context)) {
      /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultSigned $result */
      $result = $processor_result->ensureAnalyzerResult(MailhandlerAnalyzerResultSigned::TOPIC, MailhandlerAnalyzerResultSigned::createFactory());
      // Populate properties specific to signed messages.
      $result->setPgpType($context['pgp_type']);
      $result->setSignature($context['signature']);
      $result->setSignedText($context['signed_text']);
    }
    else {
      $result = $processor_result->ensureAnalyzerResult(MailhandlerAnalyzerResult::TOPIC, MailhandlerAnalyzerResult::createFactory());
    }

    // Populate general properties.
    $this->findSender($message, $result);
    $this->findUser($result);
  }

  /**
   * Returns flag whether the message is signed.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The message to check signature.
   * @param array $context
   *   An array to provide context data in case the message is signed.
   *
   * @return bool
   *   TRUE if message is signed. Otherwise, FALSE.
   */
  protected function isSigned(MessageInterface $message, array &$context) {
    // Support PGP/MIME signed messages.
    if ($message instanceof MultipartMessage) {
      $parameters = $message->getContentType()['parameters'];
      // As per https://tools.ietf.org/html/rfc2015#section-4, content type must
      // have a protocol parameter with "application/pgp-signature" value.
      if (!empty($parameters['protocol']) && $parameters['protocol'] == 'application/pgp-signature') {
        foreach ($message->getParts() as $index => $part) {
          // Check the subtype of a content type.
          if ($part->getContentType()['subtype'] == 'pgp-signature') {
            // Line endings must be converted to <CR><LF> sequence before the
            // signature is verified.
            // See https://tools.ietf.org/html/rfc2015#section-5.
            $context['pgp_type'] = 'mime';
            $context['signature'] = preg_replace('~\R~u', "\r\n", $part->getBody());

            // In order to find a signed text part of the message, we need to
            // skip the signature.
            $signed_text_index = reset(array_diff(array_keys($message->getParts()), [$index]));
            $signed_text_part = $message->getPart($signed_text_index);
            // Include headers into the signed text.
            $context['signed_text'] = preg_replace('~\R~u', "\r\n", $signed_text_part->toString());

            return TRUE;
          }
        }
      }
    }
    // Support clear-signing.
    else {
      // Cleartext signed message validation was implemented by following
      // RFC 4880. See https://tools.ietf.org/html/rfc4880#section-7
      $starts_with_pgp_header = strpos($message->getBody(), "-----BEGIN PGP SIGNED MESSAGE-----\nHash:") === 0;
      if ($starts_with_pgp_header) {
        $has_pgp_signature = (bool) strpos($message->getBody(), "-----BEGIN PGP SIGNATURE-----\n");
        $pgp_signature_end = '-----END PGP SIGNATURE-----';
        $ends_with_pgp_signature = trim(strstr($message->getBody(), "\n$pgp_signature_end")) === $pgp_signature_end;
        if ($has_pgp_signature && $ends_with_pgp_signature) {
          // Populate the context array with signature data.
          $context['pgp_type'] = 'inline';
          $context['signed_text'] = preg_replace('~\R~u', "\r\n", $message->getBody());
          $context['signature'] = FALSE;

          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Finds the sender from given mail message.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzer result.
   *
   * @return string|null
   *   The sender of the mail message or null if not found.
   */
  protected function findSender(MessageInterface $message, MailhandlerAnalyzerResultInterface $result) {
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
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzed message result containing the sender.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user or null if not found.
   */
  protected function findUser(MailhandlerAnalyzerResultInterface $result) {
    $user = NULL;
    $matched_users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $result->getSender()]);

    if (!empty($matched_users)) {
      $user = reset($matched_users);
    }
    $result->setUser($user);

    return $user;
  }

}
