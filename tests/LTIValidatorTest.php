<?php
use Elipse\Ultima\LTIValidator;
use Elipse\Ultima\LTIException;

class LTIValidatorTest extends \PHPUnit_Framework_TestCase {
    use \phpmock\phpunit\PHPMock;

    public function testFailedNetworkOpen() {
        $this->expectException(LTIException::class);
        $this->expectExceptionMessage("Did not open");

        $fopen_mock = $this->getFunctionMock("Elipse\Ultima", "fopen");
        $fopen_mock->expects($this->once())->willReturn(false);

        $last_error_mock = $this->getFunctionMock("Elipse\Ultima", "error_get_last");
        $last_error_mock->expects($this->once())->willReturn([
            "message" => "Did not open"
        ]);

        $validator = new LTIValidator("php://temp", "app_key");
        $validator->validate("", "", "");
    }

    public function testMissingResponseHeaders() {
        $this->expectException(LTIException::class);
        $this->expectExceptionMessage("Error: Could not find response headers");

        $stream_meta_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_meta_data");
        $stream_meta_mock->expects($this->once())->willReturn([]);
        $validator = new LTIValidator("php://temp", "app_key");
        $validator->validate("", "", "");
    }

    public function testGetHttpStatus() {
        $this->expectException(LTIException::class);
        $this->expectExceptionMessage("Error: [HTTP/1.1 404 Not Found] <body>");

        $stream_meta_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_meta_data");
        $stream_meta_mock->expects($this->once())->willReturn([
            "wrapper_data" => [
                "Garbage",
                "HTTP/1.1 404 Not Found",
                "Other"
            ]
        ]);
        $stream_contents_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_contents");
        $stream_contents_mock->expects($this->once())->willReturn("<body>");

        $validator = new LTIValidator("php://temp", "app_key");
        $validator->validate("", "", "");
    }

    public function testGetHttpStatusWithRedirect() {
        $this->expectException(LTIException::class);
        $this->expectExceptionMessage("Error: [HTTP/1.1 404 Not Found] <body>");

        $stream_meta_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_meta_data");
        $stream_meta_mock->expects($this->once())->willReturn([
            "wrapper_data" => [
                "Garbage",
                "HTTP/1.1 302 Redirect",
                "Other",
                "HTTP/1.1 404 Not Found"
            ]
        ]);
        $stream_contents_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_contents");
        $stream_contents_mock->expects($this->once())->willReturn("<body>");

        $validator = new LTIValidator("php://temp", "app_key");
        $validator->validate("", "", "");
    }

    public function testJsonDecode() {
        $this->expectException(LTIException::class);
        $this->expectExceptionMessage("Error: [HTTP/1.1 404 Not Found] <body>");

        $stream_meta_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_meta_data");
        $stream_meta_mock->expects($this->once())->willReturn([
            "wrapper_data" => [
                "Garbage",
                "Other",
                "HTTP/1.1 404 Not Found"
            ]
        ]);
        $stream_contents_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_contents");
        $stream_contents_mock->expects($this->once())->willReturn("<body>");

        $validator = new LTIValidator("php://temp", "app_key");
        $validator->validate("", "", "");
    }

    public function testJsonResponseWithError() {
        $this->expectException(LTIException::class);
        $this->expectExceptionMessage("Invalid nonce/timestamp");

        $stream_meta_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_meta_data");
        $stream_meta_mock->expects($this->once())->willReturn([
            "wrapper_data" => [
                "Garbage",
                "Other",
                "HTTP/1.1 200 OK"
            ]
        ]);

        $stream_contents_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_contents");
        $stream_contents_mock->expects($this->once())->willReturn('{"error": "Invalid nonce/timestamp", "valid": false}');

        $validator = new LTIValidator("php://temp", "app_key");
        $validator->validate("", "", "");
    }

    public function testValidJsonResponse() {
        $stream_meta_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_meta_data");
        $stream_meta_mock->expects($this->once())->willReturn([
            "wrapper_data" => [
                "Garbage",
                "Other",
                "HTTP/1.1 200 OK"
            ]
        ]);

        $stream_contents_mock = $this->getFunctionMock("Elipse\Ultima", "stream_get_contents");
        $stream_contents_mock->expects($this->once())->willReturn('{"valid": true}');

        $validator = new LTIValidator("php://temp", "app_key");
        $output = $validator->validate("", "", "");
        $expected = new StdClass;
        $expected->valid = true;
        $this->assertEquals($expected, $output);
    }
}
