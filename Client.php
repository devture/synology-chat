<?php
namespace Devture\Component\SynologyChat;

class Client {

	private $communicator;

	public function __construct(Communicator $communicator) {
		$this->communicator = $communicator;
	}

	public function sendWebhookMessage(string $webhookUrl, string $message): void {
		$response = $this->communicator->postFormUrlEncoded($webhookUrl, [
			'payload' => json_encode([
				'text' => $message
			]),
		]);

		if (!$response->isSuccess()) {
			throw new Exception('Request failure');
		}
	}

}
