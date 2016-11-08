<?php

namespace Drupal\mailhandler_comment\Plugin\inmail\Handler;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Handler\HandlerBase;
use Drupal\inmail\ProcessorResultInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Message handler that supports posting comments via email.
 *
 * This handler creates a new comment entity on the configured entity type if
 * user (anonymous or authenticated user) has required permissions to create
 * one.
 * It is triggered in case the mail subject begins with "[comment][#entity_ID]"
 * pattern.
 *
 * @Handler(
 *   id = "mailhandler_comment",
 *   label = @Translation("Comment"),
 *   description = @Translation("Post comments via email.")
 * )
 */
class MailhandlerComment extends HandlerBase implements ContainerFactoryPluginInterface {

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
      '#markup' => $this->t('Post comments via email.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function invoke(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    try {
      $result = $processor_result->getAnalyzerResult();

      if (!$result->hasContext('entity_type') || $result->getContext('entity_type')->getContextValue()['entity_type'] != 'comment') {
        // Do not run this handler in case we are not dealing with comments.
        return;
      }

      // Create a comment.
      $comment = $this->createComment($message, $result);

      $processor_result->log('CommentHandler', '@comment has been created by @user.', ['@comment' => $comment->label(), '@user' => $comment->getAuthorName()]);
    }
    catch (\Exception $e) {
      // Log error in case verification, authentication or authorization fails.
      $processor_result->log('CommentHandler', $e->getMessage());
    }
  }

  /**
   * Creates a new comment from given mail message.
   *
   * @param \Drupal\inmail\MIME\MimeMessageInterface $message
   *   The mail message.
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   *
   * @return \Drupal\comment\Entity\Comment
   *   The created comment.
   *
   * @throws \Exception
   *   Throws an exception in case user is not authorized to create a comment.
   */
  protected function createComment(MimeMessageInterface $message, DefaultAnalyzerResult $result) {
    $entity_id = $this->getEntityId($result);

    // Validate whether user is allowed to post comments.
    $user = $this->validateUser($result);

    // Create a comment entity.
    $comment = Comment::create([
      'entity_type' => $this->configuration['entity_type'],
      'entity_id' => $entity_id,
      'uid' => $user->id(),
      'subject' => $result->getSubject(),
      'comment_body' => [
        'value' => $result->getBody(),
        'format' => 'basic_html',
      ],
      'field_name' => 'comment',
      'comment_type' => 'comment',
      'status' => CommentInterface::PUBLISHED,
    ]);
    $comment->save();

    return $comment;
  }

  /**
   * Checks if the user is authenticated and authorized to post comments.
   *
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The identified account.
   *
   * @throws \Exception
   *   Throws an exception in case user is not validated.
   */
  protected function validateUser(DefaultAnalyzerResult $result) {
    // Do not allow unverified PGP-signed messages.
    if ($result->hasContext('verified') && !$result->getContext('verified')->getContextValue()) {
      throw new \Exception('Failed to process the message. PGP-signed message is not verified.');
    }

    // Get the current user.
    $account = \Drupal::currentUser()->getAccount();

    // Authorize a user.
    $access = $this->entityTypeManager->getAccessControlHandler('comment')->createAccess('comment', $account, [], TRUE);
    if (!$access->isAllowed()) {
      throw new \Exception('Failed to process the message. User is not authorized to post comments.');
    }

    return $account;
  }

  /**
   * Returns an array of entity types.
   *
   * @return array
   *   An array of entity types.
   */
  protected function getEntityTypes() {
    // Get a mapping of entity types (bundles) with comment fields.
    $comment_entity_types = \Drupal::entityManager()->getFieldMapByFieldType('comment');
    $entity_types = [];

    foreach ($comment_entity_types as $entity_type => $bundles) {
      $entity_types[$entity_type] = $this->entityTypeManager->getDefinition($entity_type)->getLabel();
    }

    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_type' => 'node',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['entity_type'] = [
      '#title' => $this->t('Entity type'),
      '#type' => 'select',
      '#options' => $this->getEntityTypes(),
      '#default_value' => $this->configuration['entity_type'],
      '#description' => $this->t('Select a referenced entity type.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['entity_type'] = $form_state->getValue('entity_type');
  }

  /**
   * Returns a referenced entity ID.
   *
   * @param \Drupal\inmail\DefaultAnalyzerResult $result
   *   The analyzer result.
   *
   * @return string
   *   The entity ID.
   *
   * @throws \Exception.
   *   Throws an exception in case entity ID is not valid.
   */
  protected function getEntityId(DefaultAnalyzerResult $result) {
    $subject = $result->getSubject();
    if (!preg_match('/^\[#(\d+)\]\s+/', $subject, $matches)) {
      throw new \Exception('Referenced entity ID of the comment could not be identified.');
    }

    // Get an entity ID and update the subject.
    $entity_id = $matches[1];
    $subject = str_replace(reset($matches), '', $subject);
    $result->setSubject($subject);

    $entity_type = $this->configuration['entity_type'];
    $commentable_entity_types = \Drupal::entityManager()->getFieldMapByFieldType('comment');;

    if (!isset($commentable_entity_types[$entity_type])) {
      throw new \Exception('The referenced entity type ' . $entity_type . ' does not support comments.');
    }

    if (!$entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id)) {
      throw new \Exception('The referenced entity ID (' . $entity_type . ':' . $entity_id . ') does not exists.');
    }

    $allowed_entity_bundles = $commentable_entity_types[$entity_type]['comment']['bundles'];
    $entity_bundle = $entity->bundle();
    if (!in_array($entity_bundle, $allowed_entity_bundles)) {
      throw new \Exception('The bundle ' . $entity_bundle . ' of entity (' . $entity_type . ':' . $entity_id . ') does not support comments.');
    }

    return $entity_id;
  }

}
