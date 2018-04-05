<?php

define('CONTEXT', 'continuous-integration/protect-files');
define('USER_AGENT', 'GGS_WIKI');

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
    curl_setopt($ch, CURLOPT_USERAGENT, USER_AGENT);
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
    print "Getting files updated in PR ($url)\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, USER_AGENT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");

    if (false === ($json = curl_exec($ch))) {
        print "ERROR\n";
        print curl_error($ch) . "\n\n";
        return [];
    }

    $files = json_decode($json, true);
    $return = [];

    foreach ($files as $file) {
        $return[] = $file['filename'];
    }

    return $return;
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
$files_url = preg_replace('~/commits$~', '/files', $files_url);
$pr_status_url = $pr['statuses_url'];

// Now we can init PR status and start doing validation
github_status($pr_status_url, 'pending', 'Examining the list of updated files...');

$files = get_files($files_url);

foreach ($files as $file_name) {
    if (!preg_match('~\.md$~', $file_name)) {
        github_status($pr_status_url, 'failure', 'Please only update .md files.');
        exit(0);
    }
}

github_status($pr_status_url);
