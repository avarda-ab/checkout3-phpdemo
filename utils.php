<?php

/**
 * Send a POST Request
 *
 * Uses `file_get_contents()` to send a POST request, takes request url, header and payload as arguments
 *
 * @see https://www.php.net/manual/en/function.file-get-contents.php
 *
 * @param string $request_url
 * @param string $request_header
 * @param array $request_payload
 * @return string
 */
function send_post_request($request_url, $request_header, $request_payload)
{
    $options = array(
        'http' => array(
            'header'  => $request_header,
            'method'  => 'POST',
            'content' => json_encode($request_payload)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($request_url, false, $context);
    return $result;
}

/**
 * Send a PUT Request
 *
 * Uses `file_get_contents()` to send a PUT request, takes request url, header and payload as arguments
 *
 * @see https://www.php.net/manual/en/function.file-get-contents.php
 *
 * @param string $request_url
 * @param string $request_header
 * @param array $request_payload
 * @return string
 */
function send_put_request($request_url, $request_header, $request_payload)
{
    $options = array(
        'http' => array(
            'header'  => $request_header,
            'method'  => 'PUT',
            'content' => json_encode($request_payload)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($request_url, false, $context);
    return $result;
}

/**
 * Send a GET Request
 *
 * Uses `file_get_contents()` to send a GET request, takes request url, header as arguments
 *
 * @see https://www.php.net/manual/en/function.file-get-contents.php
 *
 * @param string $request_url
 * @param string $request_header
 * @return string
 */
function send_get_request($request_url, $request_header = array())
{
    $options = array(
        'http' => array(
            'header'  => $request_header,
            'method'  => 'GET'
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($request_url, false, $context);
    return $result;
}
