<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the Node handler plugin.
 *
 * @group mailhandler_d8
 */
class MailhandlerNodeTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'mailhandler_d8',
    'inmail',
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
    $this->installEntitySchema('inmail_handler');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['inmail', 'mailhandler_d8', 'node', 'user']);

    // Create a sample node type.
    $this->contentType = NodeType::create([
      'type' => $this->randomMachineName(),
      'name' => $this->randomString(),
    ]);
    $this->contentType->save();
    node_add_body_field($this->contentType);

    // Create a new role.
    $role = Role::create([
      'id' => 'mailhandler',
      'label' => 'Mailhandler',
    ]);
    $role->grantPermission('create nodes via email');
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

    $this->processor = \Drupal::service('inmail.processor');
    $this->parser = \Drupal::service('inmail.mime_parser');
    $this->deliverer = DelivererConfig::create(['id' => 'test']);
  }

  /**
   * Tests features of Mailhandler Node plugin.
   */
  public function testMailhandlerNodePlugin() {
    $raw_node_mail = $this->getFileContent('node.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $node_mail */
    $node_mail = $this->parser->parseMessage($raw_node_mail);

    // Assert default handler configuration.
    /** @var \Drupal\inmail\Entity\HandlerConfig $handler_config */
    $handler_config = HandlerConfig::load('mailhandler_node');
    $this->assertEquals('_mailhandler', $handler_config->getConfiguration()['content_type']);

    // Update the handler configuration.
    $handler_config->setConfiguration(['content_type' => $this->contentType->id()])->save();

    // Process the mail.
    $this->processor->process($raw_node_mail, $this->deliverer);

    // Assert there is a new node created.
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
    /** @var \Drupal\node\NodeInterface $node */
    $node = reset($nodes);

    // Assert the node field values.
    $this->assertEquals($node_mail->getSubject(), $node->getTitle());
    $this->assertEquals($node_mail->getBody(), $node->get('body')->value);
    $this->assertEquals('full_html', $node->get('body')->format);
    $this->assertEquals($this->user->id(), $node->getOwnerId());
    $this->assertEquals($this->contentType->id(), $node->getType());
    $this->assertEquals(NODE_PUBLISHED, $node->get('status')->value);
  }

  /**
   * Tests signed mail messages.
   */
  public function testSignedMails() {
    $raw_signed_mail = $this->getFileContent('signed/inline.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $signed_mail */
    $signed_mail = $this->parser->parseMessage($raw_signed_mail);

    // Add a public key to the user.
    $this->user->set('mailhandler_gpg_key', ['public_key' => $this->getFileContent('keys/example.key')]);
    $this->user->save();

    // Update the handler configuration.
    /** @var \Drupal\inmail\Entity\HandlerConfig $handler_config */
    $handler_config = HandlerConfig::load('mailhandler_node');
    $handler_config->setConfiguration(['content_type' => $this->contentType->id()])->save();

    // Process the mail.
    $this->processor->process($raw_signed_mail, $this->deliverer);

    // Assert there is a new node created.
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
    /** @var \Drupal\node\NodeInterface $node */
    $node = reset($nodes);

    // @todo: Uncomment after enabling GnuGP extension in tests?
    // Assert the node field values.
    /*$this->assertEquals($signed_mail->getSubject(), $node->getTitle());
    $this->assertEquals('Hello world!', $node->get('body')->value);
    $this->assertEquals('full_html', $node->get('body')->format);
    $this->assertEquals($this->user->id(), $node->getOwnerId());
    $this->assertEquals($this->contentType->id(), $node->getType());
    $this->assertEquals(NODE_PUBLISHED, $node->get('status')->value);*/
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
    $path = drupal_get_path('module', 'mailhandler_d8') . '/tests/eml/' . $filename;
    return file_get_contents(DRUPAL_ROOT . '/' . $path);
  }

}
