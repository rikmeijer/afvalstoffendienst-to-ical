<?php

require_once __DIR__ . '/vendor/autoload.php';


use OpenCal\iCal\Domain\Entity\Event;
use OpenCal\iCal\Domain\Entity\Calendar;
use OpenCal\iCal\Domain\ValueObject\SingleDay;
use OpenCal\iCal\Domain\ValueObject\Date;
use OpenCal\iCal\Presentation\Factory\CalendarFactory;
use OpenCal\iCal\Domain\ValueObject\Alarm;

$url = "http://www.afvalstoffendienst.nl/afvalkalender";
$months = [
    1=>'januari',
    'februari',
    'maart',
    'april',
    'mei',
    'juni',
    'juli',
    'augustus',
    'september',
    'oktober',
    'november',
    'december',
];

$afvaltypes = [
    'restafval',
    'papier',
    'pd',
    'kerstbomen',
    'gft'
];

$_SERVER['argc'] > 2 || exit('gebruik: ' . basename(__FILE__) . ' <POSTCODE> <HUISNUMMER> [TOEVOEGING]' . PHP_EOL);
$credentials = [
    'postcode'=> $_SERVER['argv'][1],
    'huisnummer' => $_SERVER['argv'][2],
    'toevoeging' => $_SERVER['argc'] > 3 ? $_SERVER['argv'][3] : ''
];

$afvalstoffendienst_handle = curl_init();
curl_setopt_array($afvalstoffendienst_handle, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => true,
    CURLOPT_COOKIEJAR => "/dev/null", // keep cookie in memory
    CURLOPT_SSL_VERIFYPEER => str_starts_with($url, 'https')
]); 

curl_setopt($afvalstoffendienst_handle, CURLOPT_URL, "https://www.afvalstoffendienst.nl/bewoners/s-hertogenbosch");
curl_exec($afvalstoffendienst_handle);

curl_setopt($afvalstoffendienst_handle, CURLOPT_URL, "https://www.afvalstoffendienst.nl/login");
curl_setopt($afvalstoffendienst_handle, CURLOPT_POST, true);
curl_setopt($afvalstoffendienst_handle, CURLOPT_POSTFIELDS, http_build_query([
    'LoginForm' => $credentials
]));
curl_exec($afvalstoffendienst_handle);

curl_setopt($afvalstoffendienst_handle, CURLOPT_URL, $url);
curl_setopt($afvalstoffendienst_handle, CURLOPT_HEADER, false);
curl_setopt($afvalstoffendienst_handle, CURLOPT_POST, false);
curl_setopt($afvalstoffendienst_handle, CURLOPT_POSTFIELDS, "");
$afvalstoffendienst_kalender = curl_exec($afvalstoffendienst_handle);
curl_close($afvalstoffendienst_handle);

$afvalstoffendienst_kalender;

$afvalstoffendienst_dom = Dom\HTMLDocument::createFromString($afvalstoffendienst_kalender);
$ophaaldag_finder = new \Dom\XPath($afvalstoffendienst_dom);


$classes = array_reduce($afvaltypes, function(array $carry, string $item) { 
    $carry[] = '@class=\'' . $item . '\'';
    return $carry;
}, []);

$nodes = $ophaaldag_finder->query("//*[".join(' or ', $classes)."]");

$calendar = new Calendar();
$calendar->setCalendarName("Afvalstoffendienst");
foreach ($nodes as $node) {
    if (preg_match('/(?<weekdag>\w+)\s+(?<dayofmonth>\d{2})\s+(?<maand>\w+)/', $node->childNodes->item(0)->textContent, $date_parsed) !== 1) {
        continue;
    }
    
    $date = new DateTimeImmutable(date('Y') . '-' . array_search($date_parsed['maand'], $months) . '-' . $date_parsed['dayofmonth']);
    $trigger_date = $date->sub(new DateInterval('P1D'))->setTime(15, 0);
    
    $action = new Alarm\DisplayAction('Vergeet "' . $node->childNodes->item(3)->textContent. '" niet aan de straat te zetten voor 7:00');
    $trigger = new Alarm\AbsoluteDateTimeTrigger(new \OpenCal\iCal\Domain\ValueObject\Timestamp($trigger_date));
    $alarm = new Alarm($action, $trigger);
    
    
    $calendar->addEvent((new Event())
        ->setSummary($node->childNodes->item(3)->textContent)
            ->addAlarm($alarm)
        ->setOccurrence(
            new SingleDay(
                new Date(
                    $date
                )
            )
        ));
}

$calendarComponent = (new CalendarFactory())->createCalendar($calendar);

print (string) $calendarComponent;
