<?php
namespace Devture\Component\SynologyChat;

use Symfony\Component\PropertyAccess\PropertyAccess;

class RawApiResponse {

	private $data;

	public function __construct(array $data) {
		$this->data = $data;
	}

	public function __toString() {
		$str = json_encode($this->data);
		if ($str === false) {
			return sprintf('[Failed to serialize: %s]', json_last_error_msg());
		}
		return $str;
	}

	public function getValue(string $propertyPath) {
		$accessor = PropertyAccess::createPropertyAccessor();
		return $accessor->getValue($this->data, $propertyPath);
	}

	public function getData(): array {
		return $this->data;
	}

	public function isSuccess(): bool {
		return ($this->getValue('[success]') === true);
	}

	public function getErrorCode(): ?int {
		return $this->getValue('[error][code]');
	}

	public function getErrorMessage(): ?string {
		// `error.errors` could contain 2 different things:
		// 1. a string with an error message (e.g. `{"error":{"code":404,"errors":"invalid token"},"success":false}`)
		// 2. an object (e.g. `{"error":{"code":120,"errors":{"name":"payload","reason":"required"}},"success":false}`)
		//
		// Below is an attempt to make sense of such craziness.
		$error = $this->getValue('[error][errors]');
		if ($error === null) {
			return null;
		}

		if (!is_array($error)) {
			$error = [
				'name' => 'generic',
				'reason' => $error,
			];
		}

		return sprintf(
			'name=%s, reason=%s',
			(array_key_exists('name', $error) ? $error['name'] : 'missing'),
			(array_key_exists('reason', $error) ? $error['reason'] : 'missing')
		);
	}

}
