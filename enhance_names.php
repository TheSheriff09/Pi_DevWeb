<?php
require 'vendor/autoload.php';

$sessionController = file_get_contents('src/Controller/SessionController.php');
$sessionController = str_replace(
    "\$sessions = \$em->getRepository(Session::class)->findBy([",
    "\$sessions = \$em->getRepository(Session::class)->findBy([\n            \$role === 'MENTOR' ? 'mentorID' : 'entrepreneurID' => \$userId\n        ], ['sessionDate' => 'DESC']);\n\n        \$enhancedSessions = [];\n        foreach (\$sessions as \$s) {\n            \$otherId = \$role === 'MENTOR' ? \$s->getEntrepreneurID() : \$s->getMentorID();\n            \$otherUser = \$em->getRepository(\\App\\Entity\\Users::class)->find(\$otherId);\n            \$enhancedSessions[] = [\n                'session' => \$s,\n                'counterpartName' => \$otherUser ? \$otherUser->getFullName() : 'Unknown'\n            ];\n        }",
    $sessionController,
    $count
);
if ($count > 0) {
    // Clear the original findBy payload that was incomplete
    $sessionController = preg_replace("/\n        ], \['sessionDate' => 'DESC'\]\);\n\n        return \\$this->render\('FrontOffice\/mentorship\/sessions\.html\.twig', \[/", "\n        return \$this->render('FrontOffice/mentorship/sessions.html.twig', [", $sessionController);
}
file_put_contents('src/Controller/SessionController.php', $sessionController);

$bookingController = file_get_contents('src/Controller/BookingController.php');
$bookingController = str_replace(
    "\$bookings = \$em->getRepository(Booking::class)->findBy(['mentorID' => \$userId], ['creationDate' => 'DESC']);",
    "\$bookingsRaw = \$em->getRepository(Booking::class)->findBy(['mentorID' => \$userId], ['creationDate' => 'DESC']);",
    $bookingController
);
$bookingController = str_replace(
    "\$bookings = \$em->getRepository(Booking::class)->findBy(['entrepreneurID' => \$userId], ['creationDate' => 'DESC']);",
    "\$bookingsRaw = \$em->getRepository(Booking::class)->findBy(['entrepreneurID' => \$userId], ['creationDate' => 'DESC']);",
    $bookingController
);
$bookingReplacement = <<<PHP
        \$bookings = [];
        foreach (\$bookingsRaw as \$b) {
            \$otherId = \$role === 'MENTOR' ? \$b->getEntrepreneurID() : \$b->getMentorID();
            \$otherUser = \$em->getRepository(\App\Entity\Users::class)->find(\$otherId);
            \$bookings[] = [
                'booking' => \$b,
                'counterpartName' => \$otherUser ? \$otherUser->getFullName() : 'Unknown'
            ];
        }

        return \$this->render('FrontOffice/mentorship/bookings.html.twig', [
PHP;
$bookingController = str_replace("        return \$this->render('FrontOffice/mentorship/bookings.html.twig', [", $bookingReplacement, $bookingController);
file_put_contents('src/Controller/BookingController.php', $bookingController);

echo "Controllers updated successfully.\n";
