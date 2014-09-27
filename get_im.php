<?php

require 'Bart/Curl.php';

use Bart\Curl;

class VkIM
{
	public $curl;
	public $session;

	public function __construct($session)
	{
		$this->curl = new Curl('http://vk.com');
		$this->session = $session;
	}

	public function cachedPost($path, array $getParams, $postData)
	{
		$headers = [
			'User Agent' => $this->session['User Agent'],
			'Referer' => $this->session['Referer'],
		];

		$cookies = $this->session['Cookies'];

		$cacheFile = 'cache/post.'.md5(serialize(func_get_args()).serialize($headers).serialize($cookies));

		if (file_exists($cacheFile)) {
			echo "read $cacheFile\n";
			$content = file_get_contents($cacheFile);
		} else {
			echo "fetch $cacheFile\n";
			$resp = $this->curl->post($path, [], $postData, $headers, $cookies);
			$content = $resp['content'];
			file_put_contents($cacheFile, $content);
		}

		return $content;
	}

	public function getDialogsPage($offset = 0)
	{
		$postData = [
			'act' => 'a_get_dialogs',
			'al' => 1,
			'offset' => $offset,
			'unread' => '',
		];

		$content = $this->cachedPost('al_im.php', [], $postData);
		$content = mb_convert_encoding($content, 'utf8', 'cp1251');

		preg_match('/<\!json>(.*)<\!>/', $content, $contentMatch);
		$contentJson = json_decode($contentMatch[1], true);

		return json_decode($contentJson['dialogs_members'], true);
	}

	public function getDialogPage($uid, $offset = 0)
	{
		$postData = [
			'act' => 'a_history',
			'al' => 1,
			'offset' => $offset,
			'peer' => $uid,
			'rev' => 0,
			'whole' => 0,
		];

		$content = $this->cachedPost('al_im.php', [], $postData);
		$content = mb_convert_encoding($content, 'utf8', 'cp1251');

		$contentParts = explode('<!>', $content);
		$contentHtml = $contentParts[5];

		return $this->parseDialogHtml($contentHtml);
	}

	public function parseDialogHtml($html)
	{
		libxml_use_internal_errors(true);

		$doc = new DOMDocument();
		$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
		$xpath = new DOMXpath($doc);

		$messages = [];

		foreach ($xpath->query('/html/body/table/tbody/tr') as $node) {
			$date = $node->getAttribute('data-date');

			$images = [];
			$imageLinks = $xpath->query('*//div[@class="page_post_sized_thumbs  clear_fix"]/a', $node);
			foreach ($imageLinks as $imageLink) {
				if ($imageNode = $imageLink->childNodes->item(0)) {
					$images[] = [
						'onclick' => $imageLink->getAttribute('onclick'),
						'thumb_src' => $imageNode->getAttribute('src'),
						'thumb_width' => $imageNode->getAttribute('width'),
						'thumb_height' => $imageNode->getAttribute('height'),
					];
				}
			}

			if ($wall_module = $xpath->query('*//div[@class="wall_module"]', $node)->item(0))
				$wall_html = $doc->saveHTML($wall_module);
			else
				$wall_html = null;

			$message = [
				'id' => $node->getAttribute('id'),
				'from' => $node->getAttribute('data-from'),
				'date' => $date,
				'date_format' => date('Y-m-d H:i:s', $date),
				'from_name' => $xpath->query('*//div[@class="im_log_author_chat_name"]', $node)->item(0)->textContent,
				'from_thumb' => $xpath->query('*//img[@class="im_log_author_chat_thumb"]', $node)->item(0)->getAttribute('src'),
				//'text' => $xpath->query('*//div[@class="im_msg_text"]', $node)->item(0)->textContent,
				'text' => $doc->saveHTML($xpath->query('*//div[@class="im_msg_text"]', $node)->item(0)),
				'wall_html' => $wall_html,
				'images' => $images,
				//'content' => trim($node->textContent),
				//'html' => $doc->saveHTML($node),
			];

			$messages[$message['id']] = $message;
		}

		return $messages;
	}
}

$sessions = json_decode(file_get_contents('sessions.json'), true);

if ($argc < 2) {
	die("Usage: php get_im.php <uid>\n");
}

$sessionName = $argv[1];
$session = $sessions[$sessionName];

$vkIM = new VkIM($session);

$dialogdir = 'ims/'.$sessionName.'/';
if (!is_dir($dialogdir))
	mkdir($dialogdir, 0777, true);

$dialogsfile = 'ims/'.$sessionName.'.json';

echo "write: ".$dialogfiles."\n";

$offset = 0;
$dialogs = [];
while ($dialogsPage = $vkIM->getDialogsPage($offset)) {
	$dialogs += $dialogsPage;
	$offset += count($dialogsPage);
}

file_put_contents($dialogsfile, json_encode($dialogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

foreach ($dialogs as $uid => $user) {
	$dialogname = $user['first_name']." ".$user['last_name'];
	$dialogfile = $dialogdir.$dialogname.' - '.$uid.'.json';

	echo "write: ".$dialogfile."\n";

	$offset = 0;
	$messages = [];
	while ($dialogPage = $vkIM->getDialogPage($uid, $offset)) {
		$messages = $dialogPage + $messages;
		$offset += count($dialogPage);
	}

	//print_r($messages);

	file_put_contents($dialogfile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}