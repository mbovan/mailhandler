<?php

namespace Drupal\mailhandler_demo\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI of Mailhandler Demo.
 *
 * @group mailhandler
 */
class MailhandlerDemoWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'mailhandler_demo',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a test user and log in.
    $this->user = $this->drupalCreateUser([
      'access administration pages',
      'administer inmail',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests user interface of Mailhandler Demo.
   */
  protected function testMailhandlerDemoUi() {
    $this->drupalGet('admin/config/system/inmail/paste');
    $sample_messages = (array) $this->xpath('//*[@id="edit-example"]')[0]->option;

    // Assert sample messages are available in the list of sample messages.
    $this->assertTrue(in_array('Mailhandler_PGP_Signed_MIME.eml', $sample_messages), 'PGP Signed MIME message is in the list of sample mail messages.');
    $this->assertTrue(in_array('Mailhandler_PGP_Signed_MIME_HTML.eml', $sample_messages), 'PGP Signed MIME HTML message is in the list of sample mail messages.');
    $this->assertTrue(in_array('Mailhandler_PGP_Signed_Inline.eml', $sample_messages), 'PGP Signed Inline message is in the list of sample mail messages.');
    $this->assertTrue(in_array('Mailhandler_Comment.eml', $sample_messages), 'Sample comment message is in the list of sample mail messages.');
  }

}
