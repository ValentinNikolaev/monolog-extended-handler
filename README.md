# Extended Monolog Handler
Monolog Formatter based on NormalizerFormatter for storing logs to the JSON-files.

![visitors](https://visitor-badge.laobi.icu/badge?page_id=ValentinNikolaev.monolog-extended-handler)

## Example

```json
{
   "app":"php-web",
   "level":400,
   "level_name":"ERROR",
   "channel":"backend_local",
   "env":{
      "request_method":"GET",
      "request_uri":"/v1/search?adult=true&first=20&search=some_text",
      "request_host":"localhost:8000",
      "script_name":"/index.php",
      "argv":[],
      "ip":"127.0.0.1",
      "user_agent":"PostmanRuntime/7.26.10"
   },
   "context":{
      "exception":"{\"message\":\"Couldn't connect to host, Elasticsearch down?\",\"class\":\"Elastica\\\\Exception\\\\Connection\\\\HttpException\",\"code\":500,\"file\":\"\\/vendor\\/ruflin\\/elastica\\/src\\/Transport\\/Http.php:190\",\"line\":0,\"trace\":\"\\/home\\/valentin\\/Development\\/src\\/backend-social-network\\/vendor\\/ruflin\\/elastica\\/src\\/Request.php:181; \\/home\\/valentin\\/Development\\/src\\/backend-social-network\\/vendor\\/ruflin\\/elastica\\/src\\/Client.php:521; ..... \"}"
   },
   "datetime":"2021-01-15T16:08:14.100203+03:00",
   "message":"Couldn't connect to host, Elasticsearch down?",
   "extra":[
      
   ]
}
```

## Install

Run the composer installer:

```bash
composer require ozTunguska/Tunguska-monolog-handler:^1.0
```

## Usage

```php
use Monolog\Logger;
use Tunguska\Monolog\Handler\LogFileHandler;
use Tunguska\Monolog\Formatter\LogFileFormatter;

// create a log channel
$logger = new Logger('name');
$logLevel = Logger::ALERT;
$handler = new LogFileHandler('name', '/path/to/your/logs/directory', $logLevel);
$handler->setFormatter(new LogFileFormatter());
$logger->pushHandler($handler);

// add records to the log
// $log->warning('Foo');
// $log->error('Bar');
```
