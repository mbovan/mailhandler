<?php

namespace Drupal\mailhandler_d8\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI of Mailhandler.
 *
 * @group mailhandler_d8
 */
class MailhandlerWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'mailhandler_d8',
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
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests user interface of Mailhandler Node plugin.
   */
  protected function testMailhandlerNodeUi() {
    $mailhandler_node_path = 'admin/config/system/inmail/handlers/mailhandler_node';
    // Configure a handler.
    $this->drupalGet($mailhandler_node_path);
    $this->assertText(t('Node'));
    $this->assertText('mailhandler_node');
    $this->assertFieldByName('content_type', '_mailhandler');
    $this->assertText(t('Detect (Mailhandler)'));
    $this->assertText(t('There are no content types available. Create a new one'));

    // Create a new content type.
    $this->clickLink(t('Create a new one'));
    $edit = [
      'name' => 'Blog',
      'type' => 'blog',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and manage fields'));

    // Configure handler to use newly created content type.
    $edit = [
      'content_type' => 'blog',
    ];
    $this->drupalPostForm($mailhandler_node_path, $edit, t('Save'));
    $this->assertEqual('blog', \Drupal::config('inmail.handler.mailhandler_node')->get('configuration.content_type'));
    $this->drupalGet($mailhandler_node_path);
    $this->assertNoText(t('There are no content types available. Create a new one'));
    $this->assertFieldByName('content_type', 'blog');

    // Test Mailhandler analyzer.
    $this->drupalGet('admin/config/system/inmail/analyzers');
    $this->assertText(t('Mailhandler Analyzer'));
    $this->drupalGet('admin/config/system/inmail/analyzers/mailhandler');
    $this->assertText(t('Mailhandler analyzer'));
    $this->assertText('mailhandler');
    $this->assertFieldChecked('edit-status');

    // Assert GPG key field.
    $edit = [
      'fields[mailhandler_gpg_key][type]' => 'mailhandler_gpg',
    ];
    $this->drupalPostForm('admin/config/people/accounts/form-display', $edit, t('Save'));
    $this->assertText(t('Number of rows: 20'));
    $this->assertText(t('Placeholder'));
    $this->assertText(t('-----BEGIN PGP PUBLIC KEY BLOCK-----'));
    $this->drupalGet('user/' . $this->user->id() . '/edit');
    $this->assertText(t('GPG Public key.'));
    $this->assertText(t('Fingerprint of the corresponding public key. This property will be automatically populated.'));
    $this->assertText(t('GPG Key field is used by Mailhandler to authenticate a user.'));
    // @todo: Asssert "Manage display" of GPG key field.
  }

}
