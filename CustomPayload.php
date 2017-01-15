<?php

/**
 * CustomPayload provides a mechanism to create a structured json payload for Postback buttons and Quick Reply buttons.
 * It contains a type and an array of data for you to pass in various arbitrary parameters.
 */

class CustomPayload {
	public static function create($type, $data) {
		return json_encode(array(
			'type' => $type,
			'data' => $data
		));
	}

	private $payload;

	public function __construct($payload) {
		$this->payload = json_decode($payload, true);
	}

	public function isValid() {
		return (!empty($this->payload) && !empty($this->payload['type']));
	}

	public function getType() {
		$result = null;
		if ($this->isValid()) {
			$result = $this->payload['type'];
		}
		return $result;
	}

	public function getData($dataName) {
		$result = null;
		if (!empty($this->payload) && !empty($this->payload['data']) && !empty($this->payload['data'][$dataName])) {
			$result = $this->payload['data'][$dataName];
		}
		return $result;
	}
}
