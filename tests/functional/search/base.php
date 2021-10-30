<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

/**
* @group functional
*/
abstract class phpbb_functional_search_base extends phpbb_functional_test_case
{
	protected $search_backend;

	protected function assert_search_found($keywords, $posts_found, $words_highlighted)
	{
		$this->purge_cache();
		$crawler = self::request('GET', 'search.php?keywords=' . $keywords);
		$this->assertEquals($posts_found, $crawler->filter('.postbody')->count(), $this->search_backend);
		$this->assertEquals($words_highlighted, $crawler->filter('.posthilit')->count(), $this->search_backend);
		$this->assertStringContainsString("Search found $posts_found match", $crawler->filter('.searchresults-title')->text(), $this->search_backend);
	}

	protected function assert_search_found_topics($keywords, $topics_found)
	{
		$this->purge_cache();
		$crawler = self::request('GET', 'search.php?sr=topics&keywords=' . $keywords);
		$html = '';
		foreach ($crawler as $domElement) {
			$html .= $domElement->ownerDocument->saveHTML($domElement);
		}
		$this->assertEquals($topics_found, $crawler->filter('.row')->count(), $html);
		$this->assertStringContainsString("Search found $topics_found match", $crawler->filter('.searchresults-title')->text(), $html);
	}

	protected function assert_search_not_found($keywords)
	{
		$crawler = self::request('GET', 'search.php?keywords=' . $keywords);
		$this->assertEquals(0, $crawler->filter('.postbody')->count(),$this->search_backend);
		$split_keywords_string = str_replace('+', ' ', $keywords);
		$this->assertEquals($split_keywords_string, $crawler->filter('#keywords')->attr('value'), $this->search_backend);
	}

	public function test_search_backend()
	{
		$this->login();
		$this->admin_login();

		$this->create_search_index('phpbb\\search\\backend\\fulltext_native');

		$post = $this->create_topic(2, 'Test Topic 1 foosubject', 'This is a test topic posted by the barsearch testing framework.');

		$crawler = self::request('GET', 'adm/index.php?i=acp_search&mode=settings&sid=' . $this->sid);
		$form = $crawler->selectButton('Submit')->form();
		$values = $form->getValues();

		if ($values["config[search_type]"] != $this->search_backend)
		{
			$values["config[search_type]"] = $this->search_backend;

			try
			{
				$form->setValues($values);
			}
			catch(\InvalidArgumentException $e)
			{
				// Search backed is not supported because don't appear in the select
				$this->delete_topic($post['topic_id']);
				$this->markTestSkipped("Search backend is not supported/running");
			}

			$crawler = self::submit($form);

			$form = $crawler->selectButton('Yes')->form();
			$values = $form->getValues();
			$crawler = self::submit($form);

			// Unknown error selecting search backend
			if ($crawler->filter('.errorbox')->count() > 0)
			{
				$this->fail('Error when trying to select available search backend');
			}

			$this->create_search_index();
		}

		$this->logout();
		$this->assert_search_found('phpbb3+installation', 1, 3);
		$this->assert_search_found('foosubject+barsearch', 1, 2);
		$this->assert_search_found_topics('phpbb3+installation', 1);
		$this->assert_search_found_topics('foosubject+barsearch', 1);

		$this->assert_search_not_found('loremipsumdedo');
		$this->assert_search_found('barsearch-testing', 1, 2); // test hyphen ignored
		$this->assert_search_found('barsearch+-+testing', 1, 2); // test hyphen wrapped with space ignored
		$this->assert_search_not_found('barsearch+-testing'); // test excluding keyword

		$this->login();
		$this->admin_login();
		$this->delete_search_index();
		$this->delete_topic($post['topic_id']);
	}

	protected function create_search_index($backend = null)
	{
		$this->add_lang('acp/search');
		$crawler = self::request('GET', 'adm/index.php?i=acp_search&mode=index&sid=' . $this->sid);
		$form = $crawler->selectButton('Create index')->form();
		$form_values = $form->getValues();
		$form_values = array_merge($form_values,
			array(
				'search_type'	=> ( ($backend === null) ? $this->search_backend : $backend ),
				'action'		=> 'create',
			)
		);
		$form->setValues($form_values);
		$crawler = self::submit($form);
		$this->assertContainsLang('SEARCH_INDEX_CREATED', $crawler->text());
	}

	protected function delete_search_index()
	{
		$this->add_lang('acp/search');
		$crawler = self::request('GET', 'adm/index.php?i=acp_search&mode=index&sid=' . $this->sid);
		$form = $crawler->selectButton('Delete index')->form();
		$form_values = $form->getValues();
		$form_values = array_merge($form_values,
			array(
				'search_type'	=> $this->search_backend,
				'action'		=> 'delete',
			)
		);
		$form->setValues($form_values);
		$crawler = self::submit($form);
		$this->assertContainsLang('SEARCH_INDEX_REMOVED', $crawler->text());
	}
}
