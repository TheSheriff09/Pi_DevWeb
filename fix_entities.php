<?php
$files = ['Booking.php', 'Schedule.php', 'Session.php', 'MentorEvaluations.php', 'MentorFavorites.php', 'SessionNotes.php', 'SessionTodos.php'];
foreach ($files as $file) {
    $path = "src/Entity/$file";
    if (!file_exists($path)) {
        continue;
    }
    $lines = file($path);
    $columnLineIdx = -1;
    foreach ($lines as $i => $line) {
        if (strpos($line, '#[ORM\Column(') !== false && strpos($line, 'name:') === false) {
            $columnLineIdx = $i;
        } elseif (strpos($line, '#[ORM\Column]') !== false) {
            $columnLineIdx = $i;
        }
        
        if (preg_match('/private\s+\??[a-zA-Z0-9_\\\\]+\s+\$(\w+)/', $line, $m)) {
            $prop = $m[1];
            if ($columnLineIdx !== -1) {
                $colLine = $lines[$columnLineIdx];
                if (strpos($colLine, '#[ORM\Column(') !== false) {
                    $lines[$columnLineIdx] = str_replace('#[ORM\Column(', "#[ORM\Column(name: '`$prop`', ", $colLine);
                } else {
                    $lines[$columnLineIdx] = str_replace('#[ORM\Column]', "#[ORM\Column(name: '`$prop`')]", $colLine);
                }
            }
            $columnLineIdx = -1; // reset for next property
        }
    }
    file_put_contents($path, implode("", $lines));
    echo "Fixed $file\n";
}
