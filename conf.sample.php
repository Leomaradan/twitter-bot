<?php

// This configuration need to be validated with schema.json
return [
    [
        'account' => 'account1',
        'tokens' => [
            'api_key' => 'abcd',
            'api_secret' => 'efgh',
            'access_token' => 'ijkl',
            'access_secret' => 'mnop',
        ],
        'retweet' => [
            [
                'screen' => 'account_name1',
                'retweet' => true, // include retweet from account_name1
                'response' => true, // include response to tweet from account_name1
            ],
            'account_name2',
            [
                'screen' => 'account_name3',
                'retweet' => false, // ignore retweet
                'response' => false, // ignore response
            ],
        ],
    ],
    [
        'account' => 'account1',
        'tokens' => [
            'api_key' => 'dcba',
            'api_secret' => 'hgfe',
            'access_token' => 'lkji',
            'access_secret' => 'ponm',
        ],
        'retweet' => [
            'account_name4',
        ],
        'rss' => [
            [
                'url' => 'https://leomaradan.com/liens/?do=atom',
                'include_permalink' => true, // include the permalink
                'include_hashtags' => true, // include the hashtag
                'parser' => 'shaarli', // the rss is a "shaarli" https://www.shaarli.fr/
                'filter_hashtag_input' => false, // don't filter any hashtag | if set, will ignore article with these hashtags
                'filter_hashtag_output' => ['pro'], // filter hashtag output
            ] .
            'https://other.exemple.com/rss', // another rss, in automatic mode
        ],
    ],
];
