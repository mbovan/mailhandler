<?php

namespace Drupal\mailhandler\Plugin\inmail\Analyzer;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\MIME\MimeMultipartEntity;
use Drupal\inmail\MIME\MimeMultipartMessage;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * An analyzer for PGP signed messages.
 *
 * @ingroup analyzer
 *
 * PGP (Pretty Good Analyzer) has a purpose to support PGP-signed mail messages.
 * Two types of signed messages are supported: Cleartext (inline) and PGP/MIME
 * signed messages.
 * In case the message is identified as a PGP-signed message, the signature is
 * verified using gnu_pg PHP extension. This analyzer also updates the account
 * (sender) and processed body.
 *
 * @Analyzer(
 *   id = "pgp",
 *   label = @Translation("Pretty Good Privacy Analyzer")
 * )
 */
class PGPAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    $result = $processor_result->getAnalyzerResult();

    $context = [];
    // Check if we are dealing with PGP-signed message.
    if ($this->isSigned($message, $result, $context)) {
      try {
        $this->findSender($message, $result, $context);
        $this->verifySignature($result);
        $this->findBody($message, $result, $context);
      }
      catch (\Exception $e) {
        // Log error in case verification or authentication fails.
        \Drupal::logger('mailhandler')->log(RfcLogLevel::WARNING, $e->getMessage());
      }
    }

  }

  /**
   * Returns flag whether the message is signed.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   The message to check signature.
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   * @param array $context
   *   An array to provide context data in case the message is signed.
   *
   * @return bool
   *   TRUE if message is signed. Otherwise, FALSE.
   */
  protected function isSigned(MimeMessageInterface $message, DefaultAnalyzerResult $result, array &$context) {
    // Support PGP/MIME signed messages.
    if ($message instanceof MimeMultipartMessage) {
      $parameters = $message->getContentType()['parameters'];
      // As per https://tools.ietf.org/html/rfc2015#section-4, content type must
      // have a protocol parameter with "application/pgp-signature" value.
      if (!empty($parameters['protocol']) && $parameters['protocol'] == 'application/pgp-signature') {
        foreach ($message->getParts() as $index => $part) {
          // Check the subtype of a content type.
          if ($part->getContentType()['subtype'] == 'pgp-signature') {
            $signature = $part->getBody();

            // In order to find a signed text part of the message, we need to
            // skip the signature.
            $message_parts = array_diff(array_keys($message->getParts()), [$index]);
            $signed_text_index = reset($message_parts);
            $signed_text_part = $message->getPart($signed_text_index);
            // Add index of the signed message part to the context.
            $context['signed_text_index'] = $signed_text_index;
            $context_definition = new ContextDefinition('any', $this->t('PGP context'));
            $context_data = [
              'pgp_type' => 'mime',
              // Include headers into the signed text.
              'signed_text' => $signed_text_part->toString(),
              'signature' => $signature,
            ];
            $result->setContext('pgp', new Context($context_definition, $context_data));

            // Update the subject field.
            if ($signed_text_part->getHeader()->hasField('Subject')) {
              $result->setSubject($signed_text_part->getHeader()->getFieldBody('Subject'));
            }

            return TRUE;
          }
        }
      }
    }
    // Support clear-signing.
    else {
      // Cleartext signed message validation was implemented by following
      // RFC 4880. See https://tools.ietf.org/html/rfc4880#section-7
      $message_body = $message->getBody();
      $starts_with_pgp_header = strpos($message_body, "-----BEGIN PGP SIGNED MESSAGE-----\nHash:") === 0;
      if ($starts_with_pgp_header) {
        $has_pgp_signature = (bool) strpos($message_body, "-----BEGIN PGP SIGNATURE-----\n");
        $pgp_signature_end = '-----END PGP SIGNATURE-----';
        $ends_with_pgp_signature = trim(strstr($message_body, "\n$pgp_signature_end")) === $pgp_signature_end;
        if ($has_pgp_signature && $ends_with_pgp_signature) {
          // Add a PGP context.
          $context_definition = new ContextDefinition('any', $this->t('PGP context'));
          $context_data = [
            'pgp_type' => 'inline',
            'signed_text' => $message_body,
            'signature' => FALSE,
          ];
          $result->setContext('pgp', new Context($context_definition, $context_data));

          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Verifies the PGP signature.
   *
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result instance containing PGP context.
   *
   * @throws \Exception
   *   Throws an exception in case verification fails.
   */
  protected function verifySignature(DefaultAnalyzerResult $result) {
    if (!extension_loaded('gnupg')) {
      throw new \Exception('PHP extension "gnupg" has to enabled to verify the signature.');
    }
    $pgp_context = $result->getContext('pgp');

    // Initialize GnuPG resource.
    $gpg = gnupg_init();

    // Verify PGP signature.
    $verification = gnupg_verify($gpg, $pgp_context->getContextValue()['signed_text'], $pgp_context->getContextValue()['signature']);

    // Only support "full" and "ultimate" trust levels.
    if (!$verification || $verification[0]['validity'] < GNUPG_VALIDITY_FULL) {
      throw new \Exception('Failed to analyze the message. PGP signature cannot be verified.');
    }

    // Get a fingerprint for the GPG public key.
    $fingerprint = $verification[0]['fingerprint'];
    $key_info = gnupg_keyinfo($gpg, $fingerprint);
    $key_info = reset($key_info);

    // Compare the fingerprint with the identified user's fingerprint.
    if ($fingerprint != $result->getAccount()->get('mailhandler_gpg_key')->fingerprint) {
      throw new \Exception('Failed to analyze the message. GPG key fingerprint mismatch.');
    }

    // Do not accept disabled, expired or revoked public keys.
    if ($key_info['disabled'] || $key_info['expired'] || $key_info['revoked']) {
      throw new \Exception('Failed to analyze the message. GPG public key was either disabled, expired or revoked.');
    }

    // Set a message verification flag to the context.
    $result->setContext('verified', new Context(new ContextDefinition('string'), TRUE));
  }

  /**
   * Analyzes the body part of the given message.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   The mail message.
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   * @param array $context
   *   The array with context data.
   *
   * @return string
   *   The analyzed message body.
   */
  protected function findBody(MimeMessageInterface $message, DefaultAnalyzerResult $result, array &$context) {
    // By default, use original message body.
    $body = $message->getBody();

    // Extract body from PGP/MIME messages.
    if ($result->getContext('pgp')->getContextValue()['pgp_type'] == 'mime') {
      /** @var \Drupal\inmail\MIME\MimeMultipartMessage $message */
      /** @var \Drupal\inmail\MIME\MimeMultipartEntity $signed_message_part */
      $signed_message_part = $message->getPart($context['signed_text_index']);
      $body = '';
      foreach ($signed_message_part->getParts() as $part) {
        // Extract the body from HTML messages.
        if ($part instanceof MimeMultipartEntity) {
          foreach ($part->getParts() as $message_part) {
            if ($message_part->getContentType()['subtype'] == 'html') {
              $body .= $message_part->getBody();
            }
          }
        }
        else {
          $body .= $part->getBody();
        }
      }
    }
    // Support for clear-text signed messages.
    if ($result->getContext('pgp')->getContextValue()['pgp_type'] == 'inline') {
      // Since the message was already checked for valid PGP signature, we
      // can use the analyzed result instead of the raw message body.
      $pgp_parts = explode("-----BEGIN PGP SIGNATURE-----", $result->getContext('pgp')->getContextValue()['signed_text']);
      // Get the message digest by following RFC 4880 recommendations.
      // See https://tools.ietf.org/html/rfc4880#section-7.
      // Remove PGP message header.
      $body = preg_replace("/^.*\n/", "", reset($pgp_parts));
      // In case there is a "Hash" header, remove it.
      $body = preg_replace("/Hash:.*\n/i", "", $body);
      // Remove empty line before the message digest.
      $body = preg_replace("/^.*\n/", "", $body);
    }

    // @todo: Support analysis of unsigned Multipart messages.
    $result->setBody($body);
    return $body;
  }

  /**
   * Finds the sender from given mail message.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   The mail message.
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   * @param array $context
   *   The array with context data.
   * @throws \Exception
   *   Throws an exception if user is not authenticated.
   */
  protected function findSender(MimeMessageInterface $message, DefaultAnalyzerResult $result, array &$context) {
    $sender = NULL;
    $user = NULL;
    $matches = [];
    // @todo: Support multiple addresses in https://www.drupal.org/node/2861923
    $from = $message->getFrom()[0]->getAddress();

    // Use signed headers to extract "from" address for PGP/MIME messages.
    if ($result->getContext('pgp')->getContextValue()['pgp_type'] == 'mime') {
      /** @var \Drupal\inmail\MIME\MimeMultipartEntity $message */
      $signed_text_part = $message->getPart($context['signed_text_index']);
      $from = $signed_text_part->getHeader()->getFieldBody('From') ?: $from;
    }

    preg_match('/[^@<\s]+@[^@\s>]+/', $from, $matches);
    if (!empty($matches)) {
      $sender = reset($matches);
      $matched_users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $sender]);
      if (!empty($matched_users)) {
        $result->setAccount(reset($matched_users));
      }
    }

    $result->setSender($sender);
    if (!$result->isUserAuthenticated()) {
      throw new \Exception('User is not authenticated.');
    }
  }

}
