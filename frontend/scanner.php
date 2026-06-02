<?php
require_once 'lib/Database.php';
$db = new Database();

$results = [];
$scanning = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'scan') {
        $range = trim($_POST['range']);
        if (!empty($range)) {
            $scanning = true;
            // nmap -F (Fast Scan), -oX (XML Output)
            // Wir nutzen -sn für Host Discovery oder -F für schnelle Port-Scans
            $cmd = "nmap -F -oX - " . escapeshellarg($range);
            
            $output = shell_exec($cmd);
            if ($output) {
                $xml = simplexml_load_string($output);
                if ($xml) {
                    foreach ($xml->host as $host) {
                        $ip = (string)$host->address['addr'];
                        $status = (string)$host->status['state'];
                        
                        if ($status === 'up') {
                            $ports = [];
                            if (isset($host->ports->port)) {
                                foreach ($host->ports->port as $port) {
                                    $portId = (string)$port['portid'];
                                    $state = (string)$port->state['state'];
                                    $service = (string)$port->service['name'];
                                    if ($state === 'open') {
                                        $ports[] = [
                                            'port' => $portId,
                                            'name' => $service
                                        ];
                                    }
                                }
                            }
                            
                            $hostname = "";
                            if (isset($host->hostnames->hostname)) {
                                $hostname = (string)$host->hostnames->hostname[0]['name'];
                            }

                            $results[] = [
                                'ip' => $ip,
                                'hostname' => $hostname,
                                'ports' => $ports
                            ];
                        }
                    }
                } else {
                    $error = "Fehler beim Parsen der Scanner-Ergebnisse.";
                }
            } else {
                $error = "Scanner konnte nicht gestartet werden. Prüfe die IP-Range.";
            }
            $scanning = false;
        }
    }

    if ($_POST['action'] === 'import') {
        $ip = $_POST['ip'];
        $name = $_POST['name'] ?: $ip;
        $ports = json_decode($_POST['ports'], true);
        
        $newRecord = [
            'Name' => $name,
            'IP' => $ip,
            'Hersteller' => 'Gescannter Host',
            'Services' => $ports,
            'icon_id' => null,
            'Tags' => []
        ];
        $db->createRecord($newRecord);
        header("Location: hardware.php");
        exit;
    }
}

include 'header.php';
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h2>Netzwerk Scanner</h2>
    <p style="color: var(--text-muted);">Scanne dein Subnetz nach aktiven Geräten und offenen Ports.</p>
</div>

<div class="glass-card" style="padding: 24px; margin-bottom: 30px;">
    <form method="POST" style="display: flex; gap: 15px; align-items: flex-end;">
        <input type="hidden" name="action" value="scan">
        <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
            <label style="font-size: 14px; font-weight: 500; color: var(--text-muted);">IP Range oder Subnetz (z.B. 192.168.178.0/24)</label>
            <input type="text" name="range" placeholder="192.168.1.0/24" required 
                   value="<?php echo htmlspecialchars($_POST['range'] ?? '172.17.0.0/24'); ?>"
                   style="padding: 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8); font-family: 'Inter', sans-serif;">
        </div>
        <button type="submit" class="btn btn-primary btn-glow" style="padding: 12px 30px;">Scan starten</button>
    </form>
</div>

<?php if ($error): ?>
    <div class="glass-card" style="padding: 15px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; margin-bottom: 20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if (!empty($results)): ?>
    <div class="glass-card table-card">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>IP Adresse</th>
                    <th>Hostname</th>
                    <th>Offene Ports</th>
                    <th style="text-align: right;">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $host): ?>
                    <tr>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($host['ip']); ?></td>
                        <td style="color: var(--text-muted);"><?php echo htmlspecialchars($host['hostname'] ?: 'Unbekannt'); ?></td>
                        <td>
                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                <?php foreach ($host['ports'] as $p): ?>
                                    <span class="badge" style="font-size: 10px; background: rgba(37, 99, 235, 0.1); color: var(--primary-color);">
                                        <?php echo htmlspecialchars($p['port']); ?> (<?php echo htmlspecialchars($p['name']); ?>)
                                    </span>
                                <?php endforeach; ?>
                                <?php if (empty($host['ports'])): ?>
                                    <span style="font-size: 11px; color: var(--text-muted);">Keine offenen Ports gefunden</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <form method="POST">
                                <input type="hidden" name="action" value="import">
                                <input type="hidden" name="ip" value="<?php echo htmlspecialchars($host['ip']); ?>">
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($host['hostname']); ?>">
                                <input type="hidden" name="ports" value='<?php echo json_encode($host['ports']); ?>'>
                                <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;">Zu Hardware hinzufügen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$scanning): ?>
    <div class="glass-card" style="padding: 40px; text-align: center; color: var(--text-muted);">
        Keine aktiven Hosts im angegebenen Bereich gefunden.
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>
