<?php
namespace Devture\Component\SynologyChat;

use function GuzzleHttp\Psr7\build_query;
use function GuzzleHttp\Psr7\stream_for;

class Communicator {

	/**
	 * @var \GuzzleHttp\Client
	 */
	private $guzzleClient;

	public function __construct(\GuzzleHttp\Client $guzzleClient) {
		$this->guzzleClient = $guzzleClient;
	}

	/**
	 * @throws Exception
	 * @throws Exception\AuthFailure
	 * @throws Exception\BadResponse
	 */
	public function postFormUrlEncoded(string $url, array $bodyParams): RawApiResponse {
		$request = new \GuzzleHttp\Psr7\Request('POST', $url);
		$request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
		$request = $request->withBody(stream_for(build_query($bodyParams)));
		return $this->sendRequest($request);
	}

	/**
	 * @throws Exception
	 * @throws Exception\AuthFailure
	 * @throws Exception\BadResponse
	 */
	private function sendRequest(\GuzzleHttp\Psr7\Request $request): RawApiResponse {
		$request = $this->prepareRequestForSending($request);

		try {
			$response = $this->guzzleClient->send($request, ['timeout' => 25]);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$response = $e->getResponse();

			if ($response !== null) {
				if ($response->getStatusCode() === 401) {
					throw new Exception\AuthFailure('Not authenticated', 0, $e);
				}

				if ($response->getStatusCode() === 404) {
					throw new Exception\NotFound('Not found', 0, $e);
				}
			}

			throw new Exception($e->getMessage(), 0, $e);
		} catch (\GuzzleHttp\Exception\TransferException $e) {
			throw new Exception($e->getMessage(), 0, $e);
		}

		return $this->processHttpResponse($response);
	}

	private function prepareRequestForSending(\GuzzleHttp\Psr7\Request $request): \GuzzleHttp\Psr7\Request {
		$request = $request->withHeader('Accept', 'application/json');

		return $request;
	}

	/**
	 * Processes the HTTP response and potentially throws exceptions for common failure scenarios.
	 *
	 * The end result is not guaranteed to contain an error-free API response.
	 * It just guarantees that there's a valid response and it's not a common failure scenario.
	 * Callers are expected to check for success with `isSuccess()`.
	 *
	 * @throws Exception\BadResponse - when the response body doesn't contain JSON
	 * @throws Exception\AuthFailure - when the response body has been determined to contain an "invalid token" error
	 */
	private function processHttpResponse(\Psr\Http\Message\ResponseInterface $response): RawApiResponse {
		$data = [];

		if ($response->getStatusCode() !== 204) {
			$responseBody = [];
			$responseBody = (string) $response->getBody();

			$data = json_decode($responseBody, true);

			if (json_last_error()) {
				throw new Exception\BadResponse(sprintf(
					'Failed parsing response data as JSON (%s): %s',
					json_last_error_msg(),
					$responseBody
				));
			}
		}

		$apiResponse = new RawApiResponse($data);

		if ($apiResponse->isSuccess()) {
			return $apiResponse;
		}

		if ($apiResponse->getErrorCode() === 404) {
			// Signifies an invalid token error.
			throw new Exception\AuthFailure('Invalid token', 0);
		}

		return $apiResponse;
	}

}
