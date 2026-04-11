<?php
$files = ['Booking.php', 'Schedule.php', 'Session.php', 'MentorEvaluations.php', 'MentorFavorites.php', 'SessionNotes.php', 'SessionTodos.php'];
foreach ($files as $file) {
    $path = "src/Entity/$file";
    if (!file_exists($path)) {
        continue;
    }
    $content = file_get_contents($path);
    if (strpos($content, '#[ORM\GeneratedValue]') !== false) {
        $content = str_replace("    #[ORM\GeneratedValue]\n", "", $content);
        $content = str_replace("    #[ORM\GeneratedValue(strategy: 'IDENTITY')]\n", "", $content);
        file_put_contents($path, $content);
        echo "Reverted $file\n";
    }
}
