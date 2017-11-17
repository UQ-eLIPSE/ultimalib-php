<?php namespace Elipse\Ultima;

class LTIException extends \Exception {}

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

class UltimaSingleton {
    static private $validator = null;

    static function getValidator($remote_endpoint, $app_key) {
        $validator = self::$validator;
        if (empty(self::$validator)) {
            if (empty($remote_endpoint)) {
                throw new LTIException("LTIValidator has not been initialized and is missing remote_endpoint");
            } else if (empty($app_key)) {
                throw new LTIException("LTIValidator has not been initialized and is missing app_key");
            }

            self::$validator = new LTIValidator($remote_endpoint, $app_key);
        }

        return self::$validator;
    }
}
?>

<?php
$validator = UltimaSingleton::getValidator("https://ultima.uqcloud.net/lti/validate/", "HGJKHGJTY3865ripple");
?>
<pre>
<?= var_dump($validator->validate("http://localhost:80/Ultima.php", "POST", $_POST)); ?>
</pre>
