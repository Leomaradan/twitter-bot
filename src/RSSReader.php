<?php

function getFeed($feed_url) {

    $content = file_get_contents($feed_url);

    $x = new SimpleXmlElement($content);
    var_dump($x);
    echo "<ul>";

    foreach($x->entry as $entry) {
        echo "<li><a href='$entry->link' title='$entry->title'>" . $entry->title . "</a></li>";
    }
    echo "</ul>";
}
?>