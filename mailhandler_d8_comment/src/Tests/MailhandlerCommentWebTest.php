<?php

namespace Drupal\mailhandler_d8_comment\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI of Mailhandler Comment.
 *
 * @group mailhandler_d8
 */
class MailhandlerCommentWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'mailhandler_d8',
    'comment',
    'block',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create a test user and log in.
    $this->user = $this->drupalCreateUser([
      'access administration pages',
      'administer inmail',
      'administer user form display',
      'administer content types',
      'administer user display',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests user interface of Mailhandler Comment plugin.
   */
  protected function testMailhandlerCommentUi() {
    $this->drupalGet('admin/config/system/inmail/handlers');
    $this->assertText(t('Post comments via email'));
    $this->assertText(t('Comment'));

    $mailhandler_comment_path = 'admin/config/system/inmail/handlers/mailhandler_comment';
    $this->drupalGet($mailhandler_comment_path);
    $this->assertText(t('Post comments via email'));
    $this->assertText('mailhandler_comment');
    $this->assertText(t('Select a referenced entity type.'));
  }

}
