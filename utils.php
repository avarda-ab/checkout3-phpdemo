<?php
function send_post_request($request_url, $request_header, $request_payload) {
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
};
?>