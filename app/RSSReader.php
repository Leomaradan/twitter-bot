<?php

function getAttribute($object, $attribute)
{
    if (isset($object[$attribute])) {
        return (string) $object[$attribute];
    }
}

function getFeed($feed_url, DateTime $last, $parser = null)
{
    $content = file_get_contents($feed_url);

    $x = new SimpleXmlElement($content);

    if ($parser === null || $parser === 'auto') {
        if ($x->channel) {
            $parser = 'rss';
        } else {
            $parser = 'atom';
        }
    }

    logDebug('Parser: ' . $parser);

    switch ($parser) {
        case 'atom':
            $result = parser_atom($x, $last);
            break;
        case 'rss':
            $result = parser_rss($x, $last);
            break;
        case 'shaarli':
            $result = parser_shaarli($x, $last);
            break;
        default:
            throw new Exception("Invalid RSS parser");
    }

    return $result;
}

function parser_atom($x, $last)
{
    $data = [];
    $newLast = $last;

    foreach ($x->entry as $entry) {
        $date = new DateTime($entry->published);
        $recent = ($date > $last);
        $newLast = ($date > $newLast) ? $date : $newLast;

        $description = trim((string) $entry->content);

        $hashtags = [];

        foreach ($entry->category as $category) {
            $hashtags[] = (string) $category['term'];
        }

        if ($recent) {
            $data[] = (object) [
                'url' => (string) $entry->link[0]['href'],
                'text' => $description,
                'hashtag' => $hashtags,
            ];
        }
    }

    return (object) [
        'data' => (object) $data,
        'last' => $newLast,
    ];
}

function parser_rss($x, $last)
{
    $data = [];
    $newLast = $last;

    foreach ($x->channel->item as $entry) {
        $date = new DateTime($entry->pubDate);
        $recent = ($date > $last);
        $newLast = ($date > $newLast) ? $date : $newLast;

        $description = trim((string) $entry->description);

        $hashtags = [];

        foreach ($entry->category as $category) {
            $hashtags[] = (string) $category;
        }

        if ($recent) {
            $data[] = (object) [
                'url' => (string) $entry->link,
                'text' => $description,
                'hashtag' => $hashtags,
            ];
        }
    }

    return (object) [
        'data' => (object) $data,
        'last' => $newLast,
    ];
}

function parser_shaarli($x, $last)
{
    $data = [];
    $newLast = $last;

    foreach ($x->entry as $entry) {
        $date = new DateTime($entry->published);
        $recent = ($date > $last);
        $newLast = ($date > $newLast) ? $date : $newLast;

        $html = new SimpleXmlElement((string) $entry->content);
        $output = '';

        foreach ($html->p as $p) {
            $output .= ' ' . (string) $p;
            $output = trim($output);
        }

        $description = preg_replace('/\n—/mi', '', $output);
        $description = preg_replace('/<a href=".*">Permalien<\/a>/mi', '', $description);

        $hashtags = [];

        foreach ($entry->category as $category) {
            $hashtags[] = (string) $category['term'];
        }

        if ($recent) {
            $data[] = (object) [
                'url' => (string) $entry->id,
                'text' => $description,
                'hashtag' => $hashtags,
            ];
        }
    }

    return (object) [
        'data' => (object) $data,
        'last' => $newLast,
    ];
}
