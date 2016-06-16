<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Handler;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Handler\HandlerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
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
    return array(
      '#type' => 'item',
      '#markup' => $this->t('Creates a node from a mail message.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function invoke(MessageInterface $message, ProcessorResultInterface $processor_result) {
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResult $result */
    $result = $processor_result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);

    if (!$result) {
      // @todo: Log. MailhandlerAnalyzer must be enabled in order to use this handler.
    }

    $this->createNode($message, $result);
  }

  /**
   * Creates a new node from given mail message.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResult $result
   *   The analyzer result.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node.
   */
  protected function createNode(MessageInterface $message, MailhandlerAnalyzerResult $result) {
    if (!$result->isUserAuthenticated()) {
      // @todo: Log. User has no permission to create a node.
      return NULL;
    }

    $node = Node::create([
      'type' => $this->configuration['content_type'],
      'body' => [
        'value' => $message->getBody(),
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
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'content_type' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['content_type'] = array(
      '#title' => $this->t('Content type'),
      '#type' => 'select',
      '#options' => $this->getContentTypes(),
      '#default_value' => $this->configuration['content_type'],
      '#description' => $this->t('Select a content type of a node.'),
    );

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
