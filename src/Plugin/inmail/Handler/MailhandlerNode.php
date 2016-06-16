<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Handler;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Handler\HandlerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResultSigned;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Message handler that creates a node from a mail message.
 *
 * @Handler(
 *   id = "mailhandler_node",
 *   label = @Translation("Node"),
 *   description = @Translation("Creates a node from a mail message.")
 * )
 */
class MailhandlerNode extends HandlerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    return [
      '#type' => 'item',
      '#markup' => $this->t('Creates a node from a mail message.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function invoke(MessageInterface $message, ProcessorResultInterface $processor_result) {
    try {
      // Check if we are dealing with signed messages.
      if ($this->isMessageSigned($processor_result)) {
        /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultSigned $result */
        $result = $processor_result->getAnalyzerResult(MailhandlerAnalyzerResultSigned::TOPIC);

        // Check if the user is authenticated.
        $this->authenticateUser($result);
        // Verify PGP signature.
        $this->verifySignature($result);
      }
      else {
        // The message was not signed.
        $result = $processor_result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);
        // Check if the user is authenticated.
        $this->authenticateUser($result);
      }

      // Check if the user is authorized to create a node.
      $user = $this->authorizeUser($result);

      // Create a node.
      $node = $this->createNode($message, $result);

      \Drupal::logger('mailhandler')->log(RfcLogLevel::NOTICE, "\"{$node->label()}\" has been created by \"{$user->getDisplayName()}\".");
    }
    catch (\Exception $e) {
      // Log error in case verification, authentication or authorization fails.
      \Drupal::logger('mailhandler')->log(RfcLogLevel::WARNING, $e->getMessage());
    }
  }

  /**
   * Checks if the user is authorized.
   *
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzer result.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity.
   *
   * @throws \Exception
   *   Throws an exception in case user is not authorized.
   */
  protected function authorizeUser(MailhandlerAnalyzerResultInterface $result) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $result->getUser();
    $node_type = $this->configuration['content_type'];

    $access = $this->entityTypeManager->getAccessControlHandler('node')->createAccess($node_type, $user, [], TRUE);
    if (!$access->isAllowed()) {
      throw new \Exception('Failed to process the message. User is not authorized to create a node of type "' . $node_type . '".');
    }

    return $user;
  }

  /**
   * Creates a new node from given mail message.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzer result.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node.
   */
  protected function createNode(MessageInterface $message, MailhandlerAnalyzerResultInterface $result) {
    $node = Node::create([
      'type' => $this->configuration['content_type'],
      'body' => [
        'value' => $result->getBody(),
        'format' => 'full_html',
      ],
      'uid' => $result->getUser(),
      'title' => $message->getSubject(),
    ]);
    $node->save();

    return $node;
  }

  /**
   * Returns an array of content types.
   *
   * @return array
   *   An array of content types.
   */
  protected function getContentTypes() {
    // Find content types installed in the current system.
    $system_content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

    // Display a warning message if there are no content types available.
    if (empty($system_content_types)) {
      drupal_set_message($this->t('There are no content types available. <a href=":url">Create a new one</a>.', [':url' => Url::fromRoute('node.type_add')->toString()]), 'warning');
    }

    // Add default option to the content type list.
    $content_types['_mailhandler'] = $this->t('Detect (Mailhandler)');
    foreach ($system_content_types as $content_type) {
      $content_types[$content_type->id()] = $content_type->label();
    }

    return $content_types;
  }

  /**
   * Returns a flag whether a message is signed.
   *
   * @param \Drupal\inmail\ProcessorResultInterface $processor_result
   *   The processor result
   *
   * @return bool
   *   TRUE if message is signed. Otherwise, FALSE.
   */
  protected function isMessageSigned(ProcessorResultInterface $processor_result) {
    foreach ($processor_result->getAnalyzerResults() as $result) {
      if ($result instanceof MailhandlerAnalyzerResultSigned) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Verifies the PGP signature.
   *
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultSigned $result
   *   The analyzer result instance containing information about signed message.
   *
   * @throws \Exception
   *   Throws an exception in case verification fails.
   */
  protected function verifySignature(MailhandlerAnalyzerResultSigned $result) {
    if (!extension_loaded('gnupg')) {
      throw new \Exception('PHP extension "gnupg" has to enabled to verify the signature.');
    }

    // Initialize GnuPG resource.
    $gpg = gnupg_init();

    // Verify PGP signature.
    $verification = gnupg_verify($gpg, $result->getSignedText(), $result->getSignature());

    // Only support "full" and "ultimate" trust levels.
    if (!$verification || $verification[0]['validity'] < GNUPG_VALIDITY_FULL) {
      throw new \Exception('The process has been aborted. PGP signature cannot be verified.');
    }

    // Get a fingerprint for the GPG public key.
    $fingerprint = $verification[0]['fingerprint'];
    $key_info = gnupg_keyinfo($gpg, $fingerprint);
    $key_info = reset($key_info);

    // Compare the fingerprint with the identified user's fingerprint.
    if ($fingerprint != $result->getUser()->get('mailhandler_gpg_key')->fingerprint) {
      throw new \Exception('Failed to process the message. GPG key fingerprint mismatch.');
    }

    // Do not accept disabled, expired or revoked public keys.
    if ($key_info['disabled'] || $key_info['expired'] || $key_info['revoked']) {
      throw new \Exception('The process has been aborted. GPG public key was either disabled, expired or revoked.');
    }
  }

  /**
   * Checks if the user is authenticated.
   *
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzer result instance.
   *
   * @throws \Exception
   *   Throws an exception in case user is not authenticated.
   */
  protected function authenticateUser(MailhandlerAnalyzerResultInterface $result) {
    if (!$result->isUserAuthenticated()) {
      throw new \Exception('Failed to process the message. User is not authenticated.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content_type' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['content_type'] = [
      '#title' => $this->t('Content type'),
      '#type' => 'select',
      '#options' => $this->getContentTypes(),
      '#default_value' => $this->configuration['content_type'],
      '#description' => $this->t('Select a content type of a node.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['content_type'] = $form_state->getValue('content_type');
  }

}
