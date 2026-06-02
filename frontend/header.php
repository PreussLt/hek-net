<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="hek-net Netzwerk Dokumentation & Status">
    <title>hek-net | Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="app-container">
        <header class="glass-header">
            <div class="container header-content">
                <div class="logo">
                    <!-- The HEK-IT Logo from the local assets folder -->
                    <img src="assets/logo_full.png" alt="HEK-IT Logo">
                </div>
                <nav class="main-nav">
                    <ul>
                        <?php 
                        $current_page = basename($_SERVER['PHP_SELF']); 
                        $nav_items = [
                            'index.php' => 'Dashboard',
                            'hardware.php' => 'Hardware',
                            'docker.php' => 'Docker',
                            'stammdaten.php' => 'Stammdaten',
                            'scanner.php' => 'Scanner',
                            'topology.php' => 'Topologie',
                            '#' => 'Services',
                            '##' => 'Dokumentation',
                            '###' => 'Einstellungen'
                        ];
                        foreach ($nav_items as $url => $label): 
                            $active = ($current_page == $url) ? 'active' : '';
                        ?>
                            <li><a href="<?php echo $url; ?>" class="<?php echo $active; ?>"><?php echo $label; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
        </header>
        <main class="container main-content">
