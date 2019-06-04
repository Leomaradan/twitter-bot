# Leomaradan's Twitter Bot
## Auto-tweeting from RSS and auto-retweet
----
### Installation

__With git + composer__
````bash
git clone https://github.com/Leomaradan/twitter-bot
cd twitter-bot
composer install
````

create the __conf.php__ file, or rename __conf.sample.php__ to __conf.php__

Check the conf.sample.php or schema.json for the availlable option

__Direct download__

go to https://github.com/Leomaradan/twitter-bot/releases and take to latest release.

unzip to file to your server.

create the __conf.php__ file, or rename __conf.sample.php__ to __conf.php__

Check the conf.sample.php or schema.json for the availlable option

### Usage
You have 3 options to run the bot
* command line
* in browser
* included in another script

#### Command line:
you can execute the bot with the following line :

````bash
php daemon.php [--verbose] [--simulation] [--html]
````

* the _--verbose_ option will display debug messages
* the _--simulation_ option will do everything except sending the tweet and register the lock-file
* the _--html_ will add &lt;pre&gt;. It can be usefull if used with verbose

#### Browser
you can call the script directly in a browser, and pass the options as query string
````url
daemon.php?html=true&verbose=true
````

See the command line section for the availlable options

#### Include / Require
If you want to include the daemon in another script (for example a web-cron), you can declare a variable $daemonConfig with the needed option.

````php
    ob_start();
    $daemonConfig = ['simulation' => false];
    require('daemon.php');
    $buffer = ob_get_flush();
    file_put_contents('twitter-bot.log', $buffer, FILE_APPEND | LOCK_EX);
````