<?php

namespace Drupal\mailhandler\Plugin\inmail\Handler;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Handler\HandlerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Message handler that creates a node from a mail message.
 *
 * To trigger this handler, the email subject needs to begin with
 * "[node][{content_type}]" pattern. It will be parsed by Entity type analyzer
 * and only if "node" entity type is identified this handler will execute.
 * The content type (bundle) can be pre-configured in the handler configuration
 * too.
 * Later on, this handler will authenticat and authorize a user based on the
 * analyzed result.
 * In case all the conditions above are met, a new node is created.
 *
 * @Handler(
 *   id = "mailhandler_node",
 *   label = @Translation("Content"),
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
  public function invoke(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    try {
      $result = $processor_result->getAnalyzerResult();

      if (!$result->hasContext('entity_type') || $result->getContext('entity_type')->getContextValue()['entity_type'] != 'node') {
        // Do not run this handler in case
        // the identified entity type is not node.
        return;
      }

      // Authenticate a user.
      $this->authenticateUser($result);

      // Create a node.
      $node = $this->createNode($message, $result);

      \Drupal::logger('mailhandler')->log(RfcLogLevel::NOTICE, "\"{$node->label()}\" has been created by \"{$result->getAccount()->getDisplayName()}\".");
    }
    catch (\Exception $e) {
      // Log error in case verification, authentication or authorization fails.
      \Drupal::logger('mailhandler')->log(RfcLogLevel::WARNING, $e->getMessage());
    }
  }

  /**
   * Creates a new node from given mail message.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   The mail message.
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node.
   *
   * @throws \Exception
   *   Throws an exception in case user is not authorized to create a node.
   */
  protected function createNode(MimeMessageInterface $message, DefaultAnalyzerResult $result) {
    $node = Node::create([
      'type' => $this->getContentType($result),
      'body' => [
        'value' => $result->getBody(),
        'format' => 'full_html',
      ],
      'uid' => \Drupal::currentUser()->getAccount(),
      'title' => $result->getSubject(),
    ]);
    $node->save();

    return $node;
  }

  /**
   * Checks if the user is authenticated.
   *
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result instance.
   *
   * @throws \Exception
   *   Throws an exception in case user is not authenticated.
   */
  protected function authenticateUser(DefaultAnalyzerResult $result) {
    // Do not allow "From" mail header authorization for PGP-signed messages.
    if (!$result->isUserAuthenticated() || ($result->hasContext('verified') && !$result->getContext('verified')->getContextValue())) {
      throw new \Exception('Failed to process the message. User is not authenticated.');
    }
  }

  /**
   * Returns the content type.
   *
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   *
   * @return string
   *   The content type.
   *
   * @throws \Exception
   *   Throws an exception in case user is not authorized to create a node.
   */
  protected function getContentType(DefaultAnalyzerResult $result) {
    $content_type = $this->configuration['content_type'];
    $node = TRUE;
    if ($content_type == '_mailhandler' && $result->hasContext('entity_type')) {
      $node = $result->getContext('entity_type')->getContextValue()['entity_type'] == 'node';
      $content_type = $result->getContext('entity_type')->getContextValue()['bundle'];
    }

    if (!$content_type || !$node) {
      throw new \Exception('Failed to process the message. The content type does not exist or node entity type is not specified.');
    }

    // Authorize a user.
    $access = $this->entityTypeManager->getAccessControlHandler('node')->createAccess($content_type, $result->getAccount(), [], TRUE);
    if (!$access->isAllowed()) {
      throw new \Exception('Failed to process the message. User is not authorized to create a node of type "' . $content_type . '".');
    }

    return $content_type;
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
