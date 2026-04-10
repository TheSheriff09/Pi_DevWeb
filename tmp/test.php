<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$sessions = $em->getRepository(App\Entity\Session::class)->findBy([], ['sessionID' => 'DESC'], 5);
foreach ($sessions as $s) {
    echo "ID: " . $s->getSessionID() . "\n";
    echo "Type: '" . $s->getSessionType() . "'\n";
    echo "Status: '" . $s->getStatus() . "'\n";
    echo "Date: " . ($s->getSessionDate() ? $s->getSessionDate()->format('Y-m-d') : 'null') . "\n";
    echo "----\n";
}
