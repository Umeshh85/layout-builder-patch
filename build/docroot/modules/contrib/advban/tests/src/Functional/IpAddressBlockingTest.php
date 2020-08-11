<?php

namespace Drupal\Tests\advban\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests IP address banning.
 *
 * @group Advban
 */
class IpAddressBlockingTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['advban'];

  /**
   * Tests various user input to confirm correct validation and saving of data.
   */
  public function testIpAddressValidation() {
    // Create user.
    $admin_user = $this->drupalCreateUser(['advanced ban IP addresses']);
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/people/advban');

    /** @var $storage \Drupal\advban\AdvbanIpManager */
    $ipManagerService = $this->container->get('advban.ip_manager');
    /** @var $connection \Drupal\Core\Database\Connection */
    $connection = $this->container->get('database');

    // Ban a valid IP address.
    $edit = [];
    $edit['ip'] = '1.2.3.3';
    $post = $this->drupalPostForm('admin/config/people/advban', $edit, $this->t('Add'));
    $this->verbose($post);
    $ip = $connection->query("SELECT iid from {advban_ip} WHERE ip = :ip", [':ip' => $edit['ip']])->fetchField();
    $this->assertNotEmpty($ip, 'IP address found in database.');
    $ban_result = $ipManagerService->isBanned($edit['ip'], [
      'expiry_check' => TRUE,
      'info_output' => TRUE,
    ]);
    $this->assertNotEmpty($ban_result['is_banned'], 'IP address found in database.');

    // Ban range IP addresses.
    $edit = [];
    $edit['ip'] = '1.2.4.1';
    $edit['ip_end'] = '1.2.4.9';
    $this->drupalPostForm('admin/config/people/advban', $edit, $this->t('Add'));
    $ip_test = '1.2.4.3';
    $ip_long = ip2long($ip_test);
    $ip = $connection->query("SELECT iid FROM {advban_ip} WHERE ip_end <> '' AND ip <= :ip AND ip_end >= :ip LIMIT 1", [':ip' => $ip_long])->fetchField();
    $this->assertNotEmpty($ip, 'IP address found in database.');
    $ban_result = $ipManagerService->isBanned($ip_test, [
      'expiry_check' => TRUE,
      'info_output' => TRUE,
    ]);
    $this->assertNotEmpty($ban_result['is_banned'], 'IP address is banned.');

    $edit = [];
    $edit['ip'] = '1.1.1.1';
    $edit['ip_end'] = '2.2.2.2';
    $this->drupalPostForm('admin/config/people/advban', $edit, $this->t('Add'));
    $ip_test = '127.0.0.1';
    $ip_long = ip2long($ip_test);
    $ip = $connection->query("SELECT iid FROM {advban_ip} WHERE ip_end <> '' AND ip <= $ip_long AND ip_end >= $ip_long LIMIT 1")->fetchField();
    $this->assertEmpty($ip, 'IP address not found in database.');
    $ban_result = $ipManagerService->isBanned($ip_test, [
      'expiry_check' => TRUE,
      'info_output' => TRUE,
    ]);
    $this->assertEmpty($ban_result['is_banned'], 'IP address is not banned.');

    // Try to block an IP address that's already blocked.
    $edit = [];
    $edit['ip'] = '1.2.3.3';
    $this->drupalPostForm('admin/config/people/advban', $edit, $this->t('Add'));
    $this->assertText($this->t('This IP address is already banned.'));

    // Try to block an IP address that's already blocked (by range).
    $edit = [];
    $edit['ip'] = '1.2.4.3';
    $this->drupalPostForm('admin/config/people/advban', $edit, $this->t('Add'));
    $this->assertText($this->t('This IP address is already banned.'));

    // Try to block a reserved IP address.
    $edit = [];
    $edit['ip'] = '255.255.255.255';
    $this->drupalPostForm('admin/config/people/advban', $edit, $this->t('Add'));
    $this->assertText($this->t('Enter a valid IP address.'));

    // Try to block a reserved IP address.
    $edit = [];
    $edit['ip'] = 'test.example.com';
    $this->drupalPostForm('admin/config/people/advban', $edit, $this->t('Add'));
    $this->assertText($this->t('Enter a valid IP address.'));

    // Submit an empty form.
    $edit = [];
    $edit['ip'] = '';
    $edit['ip_end'] = '';
    $this->drupalPostForm('admin/config/people/advban', $edit, $this->t('Add'));
    $this->assertText($this->t('Enter a valid IP address.'));

    // Submit your own IP address. This fails, although it works when testing
    // manually.
    // TODO: On some systems this test fails due to a bug/inconsistency in cURL.
    // $edit = array();
    // $edit['ip'] = \Drupal::request()->getClientIP();
    // $this->drupalPostForm('admin/config/people/advban', $edit, t('Save'));
    // $this->assertText(t('You may not ban your own IP address.'));
    // Test duplicate ip address are not present in the 'blocked_ips' table.
    // when they are entered programmatically.
    $ip = '1.0.0.0';
    $ipManagerService->banIp($ip);
    $ipManagerService->banIp($ip);
    $ipManagerService->banIp($ip);
    $query = $connection->select('advban_ip', 'bip');
    $query->fields('bip', ['iid']);
    $query->condition('bip.ip', $ip);
    $ip_count = $query->execute()->fetchAll();
    $this->assertEquals(1, count($ip_count));

    $ip = '';
    $ipManagerService->banIp($ip);
    $ipManagerService->banIp($ip);
    $query = $connection->select('advban_ip', 'bip');
    $query->fields('bip', ['iid']);
    $query->condition('bip.ip', $ip);
    $ip_count = $query->execute()->fetchAll();
    $this->assertEquals(1, count($ip_count));
  }

}
