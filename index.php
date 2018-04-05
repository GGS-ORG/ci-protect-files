<?php

define('CONTEXT', 'continuous-integration/protect-files');

function github_request($url, $post = [])
{
    $ch = curl_init();

    // Basic Authentication with token
    // https://developer.github.com/v3/auth/
    // https://github.com/blog/1509-personal-api-tokens
    // https://github.com/settings/tokens
    // in bash set export GITHUB_TOKEN=<TOKEN>
    $access = getenv('GITHUB_TOKEN');

    curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
    curl_setopt($ch, CURLOPT_USERAGENT, 'Agent smith');
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERPWD, $access);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");

    if ( count($post) > 0 ) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }


    $output = curl_exec($ch);
    curl_close($ch);
    $result = json_decode(trim($output), true);
    return $result;
}

function github_status($url, $state = 'success', $descr = 'Only the .md files were updated.')
{
    $response = github_request(
        $url,
        json_encode([
//            'state' => 'pending',
//            'state' => 'success',
//            'state' => 'failure',
//            'target_url' => 'http://google.com', // We are npt using any "Details" URI
            'state' => $state,
            'description' => $descr,
            'context' => CONTEXT,
        ])
    );

    print_r($response);
}

$post_data = file_get_contents('php://input');
$data = json_decode($post_data, true);
print "<pre>";
print_r($data);


$url = 'https://api.github.com/repos/GGS-ORG/artifact/statuses/4757d5d99cf05605f2232d4246bc47ac738e1081';

github_status($url, 'pending', 'Examining the list of updated files...');
sleep(5);
github_status($url);
