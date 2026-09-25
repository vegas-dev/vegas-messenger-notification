<?php

namespace Vegas\MessengerNotification\Messages;

use RuntimeException;

class MessengerMessage
{
	public $content;
	public $format = 'markdown';
	public $notify = true;
	public $idempotencyKey;
	public $buttons = [];
	public $documentPath;
	public $documentFilename;
	public $documentMime;

	public function __construct($content = '')
	{
		$this->content = (string) $content;
	}

	public static function create($content = '')
	{
		return new static($content);
	}

	public function content($content)
	{
		$this->content = (string) $content;

		return $this;
	}

	public function format($format)
	{
		$this->format = (string) $format;

		return $this;
	}

	public function notify($notify = true)
	{
		$this->notify = (bool) $notify;

		return $this;
	}

	public function idempotencyKey($key)
	{
		$this->idempotencyKey = (string) $key;

		return $this;
	}

	public function button($text, $url, $row = 0)
	{
		$this->buttons[] = [
			'text' => (string) $text,
			'url' => (string) $url,
			'row' => (int) $row,
		];

		return $this;
	}

	public function document($path, $filename = null, $mime = null)
	{
		$this->documentPath = (string) $path;
		$this->documentFilename = $filename ?: basename($this->documentPath);
		$this->documentMime = $mime ?: $this->detectMime($this->documentPath);

		return $this;
	}

	public function hasDocument()
	{
		return $this->documentPath !== null;
	}

	public function toArray()
	{
		$data = [
			'text' => $this->content,
			'format' => $this->format,
			'notify' => $this->notify,
			'buttons' => $this->buttons,
		];

		if ($this->hasDocument()) {
			$this->assertReadableDocument();
			$contents = file_get_contents($this->documentPath);

			if ($contents === false) {
				throw new RuntimeException('Unable to read messenger document.');
			}

			$data['document'] = [
				'filename' => $this->documentFilename,
				'mime' => $this->documentMime,
				'content_base64' => base64_encode($contents),
			];
		}

		return $data;
	}

	public function assertReadableDocument()
	{
		if (!$this->hasDocument() || !is_file($this->documentPath) || !is_readable($this->documentPath)) {
			throw new RuntimeException('Messenger document does not exist or is not readable: ' . $this->documentPath);
		}
	}

	private function detectMime($path)
	{
		if (function_exists('mime_content_type') && is_file($path)) {
			$mime = mime_content_type($path);

			if ($mime) {
				return $mime;
			}
		}

		$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		$known = [
			'csv' => 'text/csv',
			'pdf' => 'application/pdf',
			'xls' => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'zip' => 'application/zip',
		];

		return isset($known[$extension]) ? $known[$extension] : 'application/octet-stream';
	}
}
