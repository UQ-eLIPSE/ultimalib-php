<?php
namespace Elipse\Ultima;

class LTIValidator {
    private $remote_endpoint;
    private $app_key;

    function __construct($remote_endpoint, $app_key) {
        $this->remote_endpoint = $remote_endpoint;
        $this->app_key = $app_key;
    }

    private function get_last_http_status($header_array) {
        $h = "";
        foreach($header_array as $header) {
            if (strpos($header, "HTTP") === 0) {
                $h = $header;
            }
        }
        return $h;
    }

    public function validate($launch_uri, $http_method, $payload) {
        $send_payload = [
            "appKey" => $this->app_key,
            "uri" => $launch_uri,
            "method" => $http_method,
            "payload" => $payload
        ];

        // Use http even if https because PHP
        $options = array(
            "http" => array(
                "header"  => "Content-type: application/json\r\n",
                "method"  => 'POST',
                "content" => json_encode($send_payload),
                "ignore_errors" => TRUE
            )
        );

        $context  = stream_context_create($options);
        $stream = fopen($this->remote_endpoint, 'r', false, $context);
        if ($stream === false) {
            throw new LTIException(error_get_last()["message"]);
        }

        $response_headers = stream_get_meta_data($stream);
        if (empty($response_headers["wrapper_data"])) {
            throw new LTIException("Error: Could not find response headers");
        }

        $response_status = $this->get_last_http_status($response_headers["wrapper_data"]);

        $response_body = stream_get_contents($stream);
        fclose($stream);

        $response_payload = json_decode($response_body);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new LTIException("Error: [$response_status] $response_body");
        }

        if (!empty($response_payload->error)) {
            throw new LTIException($response_payload->error);
        }

        return $response_payload;
    }
}
