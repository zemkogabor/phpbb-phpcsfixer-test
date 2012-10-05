<?php
/**
*
* @package notifications
* @copyright (c) 2012 phpBB Group
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class phpbb_ext_test_notification_type_test extends phpbb_notification_type_base
{
	public $email_template = 'topic_notify';

	public static function get_item_type()
	{
		return 'ext_test-test';
	}

	public static function get_item_id($post)
	{
		return (int) $post['post_id'];
	}

	public static function get_item_parent_id($post)
	{
		return (int) $post['topic_id'];
	}

	public function find_users_for_notification($post, $options = array())
	{
		return array(
			0 => array(''),
			//2 => array('', 'email'),
			//3 => array('', 'email', 'jabber'),
		);
	}

	public function create_insert_array($post)
	{
		$this->time = $post['post_time'];

		return parent::create_insert_array($post);
	}

	public function get_title()
	{
		return 'test title';
	}

	public function users_to_query()
	{
		return array();
	}

	public function get_url()
	{
		return '';
	}

	public function get_email_template_variables()
	{
		return array();
	}
}
