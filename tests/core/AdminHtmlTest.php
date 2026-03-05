<?php
use PHPUnit\Framework\TestCase;

// Load admin module classes
require_once GYRO_ROOT_DIR . 'modules/admin/lib/helpers/adminhtml.cls.php';

class AdminHtmlTest extends TestCase {
	public function test_esc_html_entities() {
		$this->assertSame('&lt;script&gt;', AdminHtml::esc('<script>'));
		$this->assertSame('a &amp; b', AdminHtml::esc('a & b'));
		$this->assertSame('&quot;hello&quot;', AdminHtml::esc('"hello"'));
	}

	public function test_truncate_short_string() {
		$this->assertSame('hello', AdminHtml::truncate('hello', 10));
	}

	public function test_truncate_long_string() {
		$result = AdminHtml::truncate('This is a very long string that needs truncation', 20);
		$this->assertSame('This is a very lo...', $result);
		$this->assertSame(20, mb_strlen($result));
	}

	public function test_page_returns_html() {
		$html = AdminHtml::page('Test Title', '<p>Content</p>');
		$this->assertStringContainsString('<!DOCTYPE html>', $html);
		$this->assertStringContainsString('Test Title', $html);
		$this->assertStringContainsString('<p>Content</p>', $html);
		$this->assertStringContainsString('Gyro Admin', $html);
	}

	public function test_page_with_nav() {
		$html = AdminHtml::page('Test', '<p>X</p>', '<nav>My Nav</nav>');
		$this->assertStringContainsString('<nav>My Nav</nav>', $html);
	}

	public function test_nav_renders_links() {
		$models = array('users' => 'DAOUsers', 'posts' => 'DAOPosts');
		$nav = AdminHtml::nav($models, 'users');

		$this->assertStringContainsString('admin/users/', $nav);
		$this->assertStringContainsString('admin/posts/', $nav);
		$this->assertStringContainsString('class="active"', $nav);
		$this->assertStringContainsString('Users', $nav);
		$this->assertStringContainsString('Posts', $nav);
	}

	public function test_nav_no_active() {
		$models = array('users' => 'DAOUsers');
		$nav = AdminHtml::nav($models);
		$this->assertStringNotContainsString('class="active"', $nav);
	}

	public function test_table_with_rows() {
		$headers = array('id' => 'ID', 'name' => 'Name');
		$rows = array(
			array('id' => 1, 'name' => 'Alice'),
			array('id' => 2, 'name' => 'Bob'),
		);
		$html = AdminHtml::table($headers, $rows, 'users', array('id'));

		$this->assertStringContainsString('<table', $html);
		$this->assertStringContainsString('Alice', $html);
		$this->assertStringContainsString('Bob', $html);
		$this->assertStringContainsString('admin/users/1/', $html);
		$this->assertStringContainsString('admin/users/2/', $html);
	}

	public function test_table_empty() {
		$headers = array('id' => 'ID');
		$html = AdminHtml::table($headers, array(), 'users', array('id'));
		$this->assertStringContainsString('No records found', $html);
	}

	public function test_detail_renders_fields() {
		$fields = array(
			'name' => array('label' => 'Name', 'value' => 'Alice'),
			'email' => array('label' => 'Email', 'value' => 'alice@example.com'),
		);
		$html = AdminHtml::detail($fields);

		$this->assertStringContainsString('<dl', $html);
		$this->assertStringContainsString('Name', $html);
		$this->assertStringContainsString('Alice', $html);
		$this->assertStringContainsString('alice@example.com', $html);
	}

	public function test_form_text_input() {
		$fields = array(
			'name' => array('label' => 'Name', 'type' => 'text', 'value' => 'Alice', 'required' => true),
		);
		$html = AdminHtml::form($fields, '/save');

		$this->assertStringContainsString('<form', $html);
		$this->assertStringContainsString('type="text"', $html);
		$this->assertStringContainsString('value="Alice"', $html);
		$this->assertStringContainsString('required', $html);
		$this->assertStringContainsString('<span class="required">*</span>', $html);
	}

	public function test_form_select_input() {
		$fields = array(
			'status' => array(
				'label' => 'Status',
				'type' => 'select',
				'value' => 'active',
				'options' => array('active', 'inactive'),
			),
		);
		$html = AdminHtml::form($fields, '/save');

		$this->assertStringContainsString('<select', $html);
		$this->assertStringContainsString('active', $html);
		$this->assertStringContainsString('inactive', $html);
		$this->assertStringContainsString('selected', $html);
	}

	public function test_form_checkbox() {
		$fields = array(
			'active' => array('label' => 'Active', 'type' => 'checkbox', 'value' => true),
		);
		$html = AdminHtml::form($fields, '/save');

		$this->assertStringContainsString('type="checkbox"', $html);
		$this->assertStringContainsString('checked', $html);
	}

	public function test_form_textarea() {
		$fields = array(
			'bio' => array('label' => 'Bio', 'type' => 'textarea', 'value' => 'Hello world'),
		);
		$html = AdminHtml::form($fields, '/save');

		$this->assertStringContainsString('<textarea', $html);
		$this->assertStringContainsString('Hello world', $html);
	}

	public function test_pager_single_page() {
		$this->assertSame('', AdminHtml::pager(1, 1, 'admin/test/?page={page}'));
	}

	public function test_pager_multi_page() {
		$html = AdminHtml::pager(2, 5, 'admin/test/?page={page}');

		$this->assertStringContainsString('Page 2 of 5', $html);
		$this->assertStringContainsString('page=1', $html);
		$this->assertStringContainsString('page=3', $html);
		$this->assertStringContainsString('Prev', $html);
		$this->assertStringContainsString('Next', $html);
	}

	public function test_pager_first_page() {
		$html = AdminHtml::pager(1, 3, 'admin/test/?page={page}');
		$this->assertStringNotContainsString('Prev', $html);
		$this->assertStringContainsString('Next', $html);
	}

	public function test_pager_last_page() {
		$html = AdminHtml::pager(3, 3, 'admin/test/?page={page}');
		$this->assertStringContainsString('Prev', $html);
		$this->assertStringNotContainsString('Next', $html);
	}

	public function test_alert_types() {
		$html = AdminHtml::alert('Success!', 'success');
		$this->assertStringContainsString('admin-alert-success', $html);
		$this->assertStringContainsString('Success!', $html);

		$html = AdminHtml::alert('Error!', 'error');
		$this->assertStringContainsString('admin-alert-error', $html);
	}

	public function test_field_to_input_type() {
		$this->assertSame('integer', AdminHtml::field_to_input_type(new DBFieldInt('id')));
		$this->assertSame('text', AdminHtml::field_to_input_type(new DBFieldText('name', 100)));
		$this->assertSame('checkbox', AdminHtml::field_to_input_type(new DBFieldBool('active')));
		$this->assertSame('float', AdminHtml::field_to_input_type(new DBFieldFloat('score')));
		$this->assertSame('date', AdminHtml::field_to_input_type(new DBFieldDate('birthday')));
		$this->assertSame('datetime-local', AdminHtml::field_to_input_type(new DBFieldDateTime('created')));
		$this->assertSame('time', AdminHtml::field_to_input_type(new DBFieldTime('start')));
		$this->assertSame('select', AdminHtml::field_to_input_type(new DBFieldEnum('status', array('A'))));
		$this->assertSame('textarea', AdminHtml::field_to_input_type(new DBFieldBlob('data')));
	}

	public function test_field_to_input_type_long_text() {
		$this->assertSame('textarea', AdminHtml::field_to_input_type(new DBFieldText('content', 1000)));
		$this->assertSame('text', AdminHtml::field_to_input_type(new DBFieldText('name', 100)));
	}

	public function test_form_maxlength() {
		$fields = array(
			'name' => array('label' => 'Name', 'type' => 'text', 'value' => '', 'maxlength' => 100),
		);
		$html = AdminHtml::form($fields, '/save');
		$this->assertStringContainsString('maxlength="100"', $html);
	}

	public function test_esc_prevents_xss() {
		$malicious = '"><script>alert(1)</script>';
		$escaped = AdminHtml::esc($malicious);
		$this->assertStringNotContainsString('<script>', $escaped);
	}
}
