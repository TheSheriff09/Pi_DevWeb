<?php
require_once 'vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$repo = $em->getRepository(App\Entity\Startup::class);

try {
    $startups = $repo->findBy(['userId' => 1]); // fake user query
    echo "Success: " . count($startups) . " startup(s) found.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
