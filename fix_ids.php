<?php
$files = ['Booking.php', 'Schedule.php', 'Session.php', 'MentorEvaluations.php', 'MentorFavorites.php', 'SessionNotes.php', 'SessionTodos.php'];
foreach ($files as $file) {
    $path = "src/Entity/$file";
    if (!file_exists($path)) {
        continue;
    }
    $content = file_get_contents($path);
    if (strpos($content, '#[ORM\Id]') !== false && strpos($content, '#[ORM\GeneratedValue]') === false) {
        $content = preg_replace('/#\[ORM\\\\Id\]\n/', "#[ORM\Id]\n    #[ORM\GeneratedValue]\n", $content);
        file_put_contents($path, $content);
        echo "Fixed $file\n";
    }
}
