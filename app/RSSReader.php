<?php

function getAttribute($object, $attribute) {
    if(isset($object[$attribute]))
    return (string) $object[$attribute];
}

function getFeed($feed_url, DateTime $last) {

    $newLast = $last;

    $content = file_get_contents($feed_url);

    $x = new SimpleXmlElement($content);

    $data = [];

    //echo "<ul>";

    foreach($x->entry as $entry) {
        $date = new DateTime($entry->published);
        $recent = ($date > $last);
        $newLast = ($date > $newLast) ? $date : $newLast;

        $html = new SimpleXmlElement((string)$entry->content);
        $output = '';

        foreach($html->p as $p) {
            $output .= ' ' . (string)$p;
            $output = trim($output);
        }

        $description = preg_replace('/\n—/mi', '', $output);
        $description = preg_replace('/<a href=".*">Permalien<\/a>/mi', '', $description);



        if($recent) {
            $data[] = [
                'url' => getAttribute($entry->link, 'href'),
                'text' => $description
            ];
        }

    }

    return [
        'data' => $data,
        'last' => $newLast
    ];

}
?>