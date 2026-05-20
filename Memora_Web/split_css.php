<?php
$inputFile = __DIR__ . '/public/assets/css/style.css';
$content = file_get_contents($inputFile);

// Define output directories
$baseDir = __DIR__ . '/resources/css/base';
$layoutsDir = __DIR__ . '/resources/css/layouts';
$componentsDir = __DIR__ . '/resources/css/components';

if (!is_dir($baseDir)) mkdir($baseDir, 0777, true);
if (!is_dir($layoutsDir)) mkdir($layoutsDir, 0777, true);
if (!is_dir($componentsDir)) mkdir($componentsDir, 0777, true);

// Function to extract section between comments
function extractSection($content, $startKeyword, $endKeyword = null) {
    $startPos = strpos($content, $startKeyword);
    if ($startPos === false) return '';
    
    if ($endKeyword) {
        $endPos = strpos($content, $endKeyword, $startPos);
        if ($endPos === false) $endPos = strlen($content);
        return trim(substr($content, $startPos, $endPos - $startPos));
    } else {
        return trim(substr($content, $startPos));
    }
}

// Extract variables and reset
$variables = trim(substr($content, 0, strpos($content, '/* ================ LAYOUT ================ */')));
file_put_contents("$baseDir/variables.css", $variables);

$layout = extractSection($content, '/* ================ LAYOUT ================ */', '/* ================ SIDEBAR ================ */');
$sidebar = extractSection($content, '/* ================ SIDEBAR ================ */', '/* ================ MAIN CONTENT ================ */');
$header = extractSection($content, '/* --- HEADER --- */', '/* --- CONTENT GRID --- */');
$grid = extractSection($content, '/* --- CONTENT GRID --- */', '/* --- FEED COLUMN --- */');
$feed = extractSection($content, '/* --- FEED COLUMN --- */', '/* --- RIGHT COLUMN / WIDGETS --- */');
$widgets = extractSection($content, '/* --- RIGHT COLUMN / WIDGETS --- */', '/* --- PROFILE PAGE SPECIFIC --- */');
$profile = extractSection($content, '/* --- PROFILE PAGE SPECIFIC --- */', '/* ================ EVENT & RSVP CARDS ================ */');
$events = extractSection($content, '/* ================ EVENT & RSVP CARDS ================ */', '/* RESPONSIVE Adjustments */');
$responsive = extractSection($content, '/* RESPONSIVE Adjustments */');

// Additional layout parts
file_put_contents("$layoutsDir/grid.css", $layout . "\n\n" . $grid);
file_put_contents("$layoutsDir/sidebar.css", $sidebar);
file_put_contents("$layoutsDir/header.css", $header);

// Components
file_put_contents("$componentsDir/feed.css", $feed);
file_put_contents("$componentsDir/widgets.css", $widgets);
file_put_contents("$componentsDir/profile.css", $profile);
file_put_contents("$componentsDir/events.css", $events);
file_put_contents("$componentsDir/responsive.css", $responsive);

echo "CSS Splitting Complete!\n";
?>
