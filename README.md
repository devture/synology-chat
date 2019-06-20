# Synology Chat API

A library to communicate with the [Synology Chat API](https://www.synology.com/en-global/knowledgebase/DSM/help/Chat/chat_integration) (just Incoming Webhooks for now).


## Installation

Install through composer: `composer require --dev devture/synology-chat`


## Usage


### Preparation
```php
$communicator = new \Devture\Component\SynologyChat\Communicator(new \GuzzleHttp\Client());
$client = new \Devture\Component\SynologyChat\Client($communicator);
```


### Actual usage
```php
$incomingWebhookUrl = 'https://chat.DOMAIN/chat/webapi/entry.cgi?api=SYNO.Chat.External&method=incoming&version=2&token=some-token';

try {
	$client->sendWebhookMessage($incomingWebhookUrl, 'Hello!');
} catch (\Devture\Component\SynologyChat\Exception\AuthFailure $e) {
	// Bad token. No such incoming webhook?
} catch (\Devture\Component\SynologyChat\Exception $e) {
	// Another error. Likely transient and can be retried.
}
```
