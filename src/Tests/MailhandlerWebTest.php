<?php

namespace Drupal\mailhandler\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI of Mailhandler.
 *
 * @group mailhandler
 */
class MailhandlerWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'mailhandler',
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
   * Tests user interface of Mailhandler Node plugin.
   */
  protected function testMailhandlerNodeUi() {
    $mailhandler_node_path = 'admin/config/system/inmail/handlers/mailhandler_node';
    // Configure a handler.
    $this->drupalGet($mailhandler_node_path);
    $this->assertText(t('Create content'));
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

    // Assert Mailhandler analyzers are displayed.
    $this->drupalGet('admin/config/system/inmail/analyzers');
    $this->assertText(t('PGP signed messages verification'));
    $this->assertText(t('Entity type and bundle identification from the mail subject'));
    $this->assertText(t('Message sender'));
    $this->assertText(t('Message footer'));
    $this->drupalGet('admin/config/system/inmail/analyzers/sender');
    $this->assertText('sender');
    $this->assertNoFieldChecked('edit-status');

    // Assert GPG key field.
    $edit = [
      'fields[mailhandler_gpg_key][type]' => 'mailhandler_gpg',
      'fields[mailhandler_gpg_key][region]' => 'content',
    ];
    $this->drupalPostForm('admin/config/people/accounts/form-display', $edit, t('Save'));
    $this->assertText(t('Number of rows: 20'));
    $this->assertText(t('Placeholder'));
    $this->assertText(t('-----BEGIN PGP PUBLIC KEY BLOCK-----'));
    $this->drupalGet('user/' . $this->user->id() . '/edit');
    $this->assertText(t('GPG Public key.'));
    $this->assertText(t('Fingerprint of the corresponding public key. This property will be automatically populated.'));
    $this->assertText(t('GPG Key field is used by Mailhandler to authenticate a user.'));

    // Add GPG public key to the user.
    $path = drupal_get_path('module', 'mailhandler') . '/tests/keys/public.key';
    $key = file_get_contents(DRUPAL_ROOT . '/' . $path);
    $edit = [
      'mailhandler_gpg_key[0][public_key]' => $key,
    ];
    $this->drupalPostForm('user/' . $this->user->id() . '/edit', $edit, t('Save'));

    // Assert "Manage display" of GPG key field.
    $edit = [
      'fields[mailhandler_gpg_key][type]' => 'mailhandler_gpg',
      'fields[mailhandler_gpg_key][region]' => 'content',
    ];
    $this->drupalPostForm('admin/config/people/accounts/display', $edit, t('Save'));
    $this->drupalPostAjaxForm(NULL, [], 'mailhandler_gpg_key_settings_edit');
    $edit = [
      'fields[mailhandler_gpg_key][settings_edit_form][settings][display]' => 'all',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'mailhandler_gpg_key_plugin_settings_update');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->drupalGet('user/' . $this->user->id());
    $this->assertText(t('GPG Key'));
    $this->assertText(t('Public key'));
    $this->assertText(t('Fingerprint'));
    if (extension_loaded('gnupg')) {
      $this->assertText('266B764825A210EE327CE70F7396A4ED5F5EED56');
    }
  }

}
