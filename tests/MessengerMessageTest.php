<?php

namespace Vegas\MessengerNotification\Tests;

use Vegas\MessengerNotification\Messages\MessengerMessage;

class MessengerMessageTest extends TestCase
{
	private $temporaryFile;

	protected function tearDown(): void
	{
		if ($this->temporaryFile && is_file($this->temporaryFile)) {
			unlink($this->temporaryFile);
		}

		parent::tearDown();
	}

	public function test_it_serializes_text_buttons_and_a_document()
	{
		$this->temporaryFile = tempnam(sys_get_temp_dir(), 'messenger-');
		file_put_contents($this->temporaryFile, 'document-body');

		$data = MessengerMessage::create('*Report*')
			->button('Open', 'https://example.com', 1)
			->document($this->temporaryFile, 'report.xls', 'application/vnd.ms-excel')
			->toArray();

		$this->assertSame('*Report*', $data['text']);
		$this->assertSame('Open', $data['buttons'][0]['text']);
		$this->assertSame('report.xls', $data['document']['filename']);
		$this->assertSame(base64_encode('document-body'), $data['document']['content_base64']);
	}

	public function test_it_serializes_only_explicit_preview_choices(): void
	{
		config(['messenger.channels.max.link_preview' => false]);
		$message = MessengerMessage::create('https://example.com/page');

		$this->assertArrayNotHasKey('disable_link_preview', $message->toArray());
		$this->assertFalse($message->linkPreview(true)->toArray()['disable_link_preview']);
		$this->assertTrue($message->linkPreview(false)->toArray()['disable_link_preview']);
		$this->assertArrayNotHasKey('disable_link_preview', $message->linkPreview(null)->toArray());
		$this->assertTrue($message->toArrayForChannel('max')['disable_link_preview']);
	}
}
