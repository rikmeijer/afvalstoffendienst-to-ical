<?php

isset($_GET['postcode']) || exit('postcode ontbreekt');
isset($_GET['huisnummer']) || exit('huisnummer ontbreekt');

$postcode = escapeshellarg($_GET['postcode']);
$huisnummer = escapeshellarg($_GET['huisnummer']);
$toevoeging = escapeshellarg($_GET['toevoeging'] ?? '');

header('content-type: text/calendar');
`/usr/bin/php afvalstoffendienst2ical.php {$postcode} {$huisnummer} {$toevoeging}`;