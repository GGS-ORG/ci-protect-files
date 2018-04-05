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

function get_files($url)
{
    print "Getting Files ($url)\n";
    $json = file_get_contents($url);
    print "GOT \n$json\n\n\n";

    $files = json_decode($json, true);
    return $files;
}


$post_data = file_get_contents('php://input');
$data = json_decode($post_data, true);

if ( !isset($data['pull_request']) ) {
    print('No PR information is found.');
    print_r($data);
}

$pr = $data['pull_request'];

if ( !isset($pr['statuses_url']) ) {
    print('No statuses_url information is found.');
    print_r($data);
}

if ( !isset($pr['commits_url']) ) {
    print('No commits_url information is found.');
    print_r($data);
}

$files_url = $pr['commits_url'];
$files_url = preg_replace('/commits$', '/files', $files_url);
$files = get_files($files_url);
print_r($files);

$url = $pr['statuses_url'];


print "statuses_url ($url)\n";

github_status($url, 'pending', 'Examining the list of updated files...');
sleep(5);
github_status($url);
