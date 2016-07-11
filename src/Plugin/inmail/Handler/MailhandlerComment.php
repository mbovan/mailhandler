<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Handler;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Handler\HandlerBase;
use Drupal\inmail\ProcessorResultInterface;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResult;
use Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Message handler that supports posting comments via email.
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
  public function invoke(MessageInterface $message, ProcessorResultInterface $processor_result) {
    try {
      $result = $this->getMailhandlerResult($processor_result);

      if ($result->getEntityType() != 'comment') {
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
   * Returns a Mailhandler analyzer result instance.
   *
   * @param \Drupal\inmail\ProcessorResultInterface $processor_result
   *   The result and log container for the message, containing the message
   *   deliverer and possibly analyzer results.
   * @return \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface
   *   The Mailhandler analyzer result instance.
   *
   * @throws \Exception
   *   Throws an exception in case there is no Mailhandler analyzer result
   *   object created. It happens in case all Mailhandler analyzers are
   *   disabled.
   */
  public function getMailhandlerResult(ProcessorResultInterface $processor_result) {
    /** @var \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result */
    $result = $processor_result->getAnalyzerResult(MailhandlerAnalyzerResult::TOPIC);

    // @todo: Remove when support for core Inmail result objects is implemented.
    if (!$result) {
      throw new \Exception('Mailhandler Analyzer result object cannot be ensured.');
    }

    return $result;
  }

  /**
   * Creates a new comment from given mail message.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzer result.
   *
   * @return \Drupal\comment\Entity\Comment
   *   The created comment.
   *
   * @throws \Exception
   *   Throws an exception in case user is not authorized to create a comment.
   */
  protected function createComment(MessageInterface $message, MailhandlerAnalyzerResultInterface $result) {
    $subject = $result->getSubject();
    if (!preg_match('/^\[#(\d+)\]\s+/', $subject, $matches)) {
      throw new \Exception('Referenced entity ID of the comment could not be identified.');
    }

    // Get a node ID and update the subject.
    $node_id = $matches[1];
    $subject = str_replace(reset($matches), '', $subject);

    // Validate whether user is allowed to post comments.
    $user = $this->validateUser($result);

    // Create a comment entity.
    $comment = Comment::create([
      'entity_type' => 'node',
      'entity_id' => $node_id,
      'uid' => $user,
      'subject' => $subject,
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
   * @param \Drupal\mailhandler_d8\MailhandlerAnalyzerResultInterface $result
   *   The analyzer result instance.
   *
   * @return \Drupal\user\UserInterface
   *   The identified user.
   *
   * @throws \Exception
   *   Throws an exception in case user is not validated.
   */
  protected function validateUser(MailhandlerAnalyzerResultInterface $result) {
    // Do not allow unverified PGP-signed messages.
    if ($result->isSigned() && !$result->isVerified()) {
      throw new \Exception('Failed to process the message. PGP-signed message is not verified.');
    }

    // Get the user or fallback to anonymous user.
    // @todo: Replace with current user after https://www.drupal.org/node/2754261.
    $user = $result->isUserAuthenticated() ? $result->getUser() : User::getAnonymousUser();

    // Authorize a user.
    $access = $this->entityTypeManager->getAccessControlHandler('comment')->createAccess('comment', $user, [], TRUE);
    if (!$access->isAllowed()) {
      throw new \Exception('Failed to process the message. User is not authorized to post comments.');
    }

    return $user;
  }

}
