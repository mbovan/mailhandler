<?php

namespace Drupal\Tests\mailhandler_comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Tests the Comment handler plugin.
 *
 * @group mailhandler
 */
class MailhandlerCommentTest extends KernelTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'mailhandler',
    'mailhandler_comment',
    'inmail',
    'comment',
    'system',
    'node',
    'user',
    'field',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('inmail_handler');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['inmail', 'mailhandler', 'mailhandler_comment', 'node', 'user', 'comment']);

    // Create a sample node type.
    $this->blog = NodeType::create([
      'type' => 'blog',
      'name' => 'Blog',
    ]);
    $this->blog->save();

    // Create a new role.
    $role = Role::create([
      'id' => 'mailhandler',
      'label' => 'Mailhandler',
    ]);
    $role->grantPermission('post comments');
    $role->save();

    // Create a new user with "Mailhandler" role.
    /** @var \Drupal\user\Entity\User $user */
    $user = User::create([
      'mail' => 'milos@example.com',
      'name' => 'Milos',
    ]);
    $user->addRole($role->id());
    $user->save();
    $this->user = $user;

    // Create a sample node.
    $this->node = Node::create([
      'type' => $this->blog->id(),
      'title' => 'Sample blog post',
    ]);
    $this->node->save();

    // Add a comment field to "blog".
    $this->addDefaultCommentField('node', 'blog');

    $this->processor = \Drupal::service('inmail.processor');
    $this->parser = \Drupal::service('inmail.mime_parser');
    $this->deliverer = DelivererConfig::create(['id' => 'test']);
  }

  /**
   * Tests features of Mailhandler Comment plugin for anonymous users.
   */
  public function testMailhandlerCommentPluginAnonymous() {
    // Get a sample comment mail message.
    $raw_comment_mail = $this->getFileContent('eml/Comment.eml');
    // Replace a node ID with an actual node ID.
    $raw_comment_mail = str_replace('[comment][#1]', '[comment][#' . $this->node->id() . ']', $raw_comment_mail);

    // Assert default handler configuration.
    /** @var \Drupal\inmail\Entity\HandlerConfig $handler_config */
    $handler_config = HandlerConfig::load('mailhandler_comment');
    $this->assertEquals('node', $handler_config->getConfiguration()['entity_type']);

    // Process the mail.
    $this->processor->process('test_key', $raw_comment_mail, $this->deliverer);

    // Since SenderAnalyzer is disabled by default, authenticated user will
    // fallback to an anonymous user.
    $comments = Comment::loadMultiple();
    $this->assertEmpty($comments, 'Anonymous user has no permission to post comments.');

    // Clear the cache before updating permissions for anonymous users.
    drupal_flush_all_caches();
    // Grant "post comments" permission to anonymous users.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['post comments']);

    // Trigger the processing again.
    $this->processor->process('test_key', $raw_comment_mail, $this->deliverer);
    $comments = Comment::loadMultiple();
    $this->assertEquals(count($comments), 1, 'There is a new comment created.');

    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = reset($comments);
    $this->assertEquals('Anonymous', $comment->getAuthorName());
    $this->assertEquals(0, $comment->getOwnerId());
    $this->assertEquals('Great article!', $comment->getSubject());
  }

  /**
   * Tests features of Mailhandler Comment plugin for authenticated users.
   */
  public function testMailhandlerCommentPluginAuthenticated() {
    // Get a sample comment mail message.
    $raw_comment_mail = $this->getFileContent('eml/Comment.eml');
    // Replace a node ID with an actual node ID.
    $raw_comment_mail = str_replace('[comment][#1]', '[comment][#' . $this->node->id() . ']', $raw_comment_mail);

    // Enable "From" authentication since it is disabled by default.
    $sender_analyzer = AnalyzerConfig::load('sender');
    $sender_analyzer->enable()->save();

    // Trigger the processing.
    $this->processor->process('test_key', $raw_comment_mail, $this->deliverer);
    $comments = Comment::loadMultiple();
    $this->assertEquals(count($comments), 1, 'There is a new comment created.');

    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = end($comments);
    $this->assertEquals($this->user->getDisplayName(), $comment->getAuthorName());
    $this->assertEquals($this->user->id(), $comment->getOwnerId());
    $this->assertEquals(CommentInterface::PUBLISHED, $comment->getStatus());
    $this->assertEquals('Great article!', $comment->getSubject());
  }

  /**
   * Returns the content of a requested file.
   *
   * See \Drupal\Tests\inmail\Kernel\ModeratorForwardTest.
   *
   * @param string $filename
   *   The name of the file.
   *
   * @return string
   *   The content of the file.
   */
  public function getFileContent($filename) {
    $path = drupal_get_path('module', 'mailhandler_comment') . '/tests/' . $filename;
    return file_get_contents(DRUPAL_ROOT . '/' . $path);
  }

}
