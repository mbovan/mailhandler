<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Entity\HandlerConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

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
    $this->installConfig(['inmail', 'mailhandler_d8', 'node']);

    // Create a sample node type.
    $this->contentType = NodeType::create([
      'type' => $this->randomMachineName(),
      'name' => $this->randomString(),
    ]);
    $this->contentType->save();
    node_add_body_field($this->contentType);
  }

  /**
   * Tests features of Mailhandler Node plugin.
   */
  public function testMailhandlerNodePlugin() {
    /** @var \Drupal\inmail\MessageProcessor $processor */
    $processor = \Drupal::service('inmail.processor');
    $mail_path = drupal_get_path('module', 'mailhandler_d8') . '/tests/eml/node.eml';
    $node_mail = file_get_contents(DRUPAL_ROOT . '/' . $mail_path);
    /** @var \Drupal\inmail\MIME\ParserInterface $parser */
    $parser = \Drupal::service('inmail.mime_parser');
    $node_mail_parsed = $parser->parseMessage($node_mail);

    // Assert default handler configuration.
    /** @var \Drupal\inmail\Entity\HandlerConfig $handler_config */
    $handler_config = HandlerConfig::load('mailhandler_node');
    $this->assertEquals('_mailhandler', $handler_config->getConfiguration()['content_type']);

    // Update the handler configuration.
    $handler_config->setConfiguration(['content_type' => $this->contentType->id()])->save();

    // Process the mail.
    $processor->process($node_mail, DelivererConfig::create(['id' => 'test']));

    // Assert there is a new node created.
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
    /** @var \Drupal\node\NodeInterface $node */
    $node = reset($nodes);

    // Assert the node field values.
    $this->assertEquals($node_mail_parsed->getSubject(), $node->getTitle());
    $this->assertEquals($node_mail_parsed->getDecodedBody(), $node->get('body')->value);
    $this->assertEquals('full_html', $node->get('body')->format);
    $this->assertEquals(0, $node->getOwnerId());
    $this->assertEquals($this->contentType->id(), $node->getType());
    $this->assertEquals(NODE_PUBLISHED, $node->get('status')->value);
  }
}
