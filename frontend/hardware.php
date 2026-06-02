<?php 
require_once 'lib/Database.php';

$db = new Database();

// Formular wurde abgesendet (Neues Gerät speichern)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $newRecord = [
        'Name' => $_POST['name'] ?? '',
        'IP' => $_POST['ip'] ?? '',
        'Hersteller' => $_POST['hersteller'] ?? '',
        'Services' => $_POST['services'] ?? [],
        'icon_id' => $_POST['icon_id'] ?? null,
        'Tags' => $_POST['tags'] ?? []
    ];
    $db->createRecord($newRecord);
    header("Location: hardware.php");
    exit;
}

// Daten abrufen
$records = $db->getRecords();
$masterIcons = $db->getMasterIcons();
$availableTags = $db->getTags();

include 'header.php'; 
?>

<div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Hardware & Geräte</h2>
    <button class="btn btn-primary btn-glow" onclick="document.getElementById('addModal').style.display='block'">+ Neues Gerät</button>
</div>

<div class="glass-card table-card">
    <table class="modern-table">
        <thead>
            <tr>
                <th style="width: 50px;">Icon</th>
                <th>Geräte Name</th>
                <th>IP Adresse</th>
                <th>Hersteller</th>
                <th>Services</th>
                <th style="text-align: right;">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
            <tr>
                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">
                    Keine Geräte gefunden. Füge dein erstes Netzwerk-Gerät hinzu!
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($records as $record): ?>
                <tr>
                    <td style="text-align: center;">
                        <?php if (!empty($record['icon_id'])): ?>
                            <img src="icon.php?id=<?php echo $record['icon_id']; ?>" alt="Icon" style="width: 32px; height: 32px; border-radius: 6px; object-fit: contain; background: rgba(255,255,255,0.5);">
                        <?php else: ?>
                            <div style="width: 32px; height: 32px; border-radius: 6px; background: rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; font-size: 10px; color: var(--text-muted);">NA</div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 500;">
                        <?php echo htmlspecialchars($record['Name'] ?? 'Unbekannt'); ?>
                        <?php if (!empty($record['TagsList'])): ?>
                            <div style="display: flex; gap: 4px; margin-top: 5px;">
                                <?php foreach ($record['TagsList'] as $tag): ?>
                                    <span style="font-size: 10px; padding: 2px 6px; border-radius: 4px; background: <?php echo $tag['color']; ?>22; color: <?php echo $tag['color']; ?>; border: 1px solid <?php echo $tag['color']; ?>44;">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-success"><?php echo htmlspecialchars($record['IP'] ?? '-'); ?></span></td>
                    <td><?php echo htmlspecialchars($record['Hersteller'] ?? '-'); ?></td>
                    <td>
                        <?php if (!empty($record['ServicesList'])): ?>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <?php foreach ($record['ServicesList'] as $svc): ?>
                                    <?php 
                                        $color = htmlspecialchars($svc['status_color'] ?? 'muted');
                                        $bgMap = [
                                            'success' => 'rgba(16, 185, 129, 0.1)',
                                            'danger' => 'rgba(239, 68, 68, 0.1)',
                                            'warning' => 'rgba(245, 158, 11, 0.1)',
                                            'muted' => 'rgba(107, 114, 128, 0.1)'
                                        ];
                                        $bgColor = $bgMap[$color] ?? 'rgba(37, 99, 235, 0.1)';
                                    ?>
                                    <span class="badge badge-<?php echo $color; ?>" style="background: <?php echo $bgColor; ?>; color: var(--<?php echo $color; ?>-color);" title="Status: <?php echo htmlspecialchars($svc['status_name'] ?? 'Unbekannt'); ?>">
                                        <?php echo htmlspecialchars($svc['name']); ?>
                                        <?php if (!empty($svc['port'])): ?>
                                            <span style="opacity: 0.7; font-size: 0.9em; margin-left: 2px;">:<?php echo htmlspecialchars($svc['port']); ?></span>
                                        <?php endif; ?>
                                        <span style="display:inline-block; width: 6px; height: 6px; border-radius: 50%; background: currentColor; margin-left: 4px;"></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <a href="details.php?id=<?php echo $record['id']; ?>" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px; text-decoration: none;">Doku</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog zum Hinzufügen -->
<div id="addModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
    <div class="glass-card" style="max-width: 500px; margin: 10% auto; padding: 32px; position: relative; border-radius: 12px;">
        <span onclick="document.getElementById('addModal').style.display='none'" style="position: absolute; right: 20px; top: 15px; cursor: pointer; font-size: 24px; color: var(--text-muted);">&times;</span>
        <h3 style="margin-top: 0; margin-bottom: 24px; color: var(--text-main);">Neues Gerät hinzufügen</h3>
        
        <form method="POST" action="hardware.php" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 16px;">
            <input type="hidden" name="action" value="add">
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 14px; font-weight: 500; color: var(--text-muted);">Name des Geräts</label>
                <input type="text" name="name" required placeholder="z.B. Router EG" style="padding: 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8); font-family: 'Inter', sans-serif;">
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 14px; font-weight: 500; color: var(--text-muted);">IP Adresse</label>
                <input type="text" name="ip" required placeholder="192.168.1.X" style="padding: 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8); font-family: 'Inter', sans-serif;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 14px; font-weight: 500; color: var(--text-muted);">Hersteller</label>
                <input type="text" name="hersteller" placeholder="Cisco, Ubiquiti, etc." style="padding: 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8); font-family: 'Inter', sans-serif;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 14px; font-weight: 500; color: var(--text-muted);">Verbunden mit (Parent)</label>
                <select name="parent_id" style="padding: 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8);">
                    <option value="">-- Direktverbindung / Root --</option>
                    <?php foreach ($records as $hw): ?>
                        <option value="<?php echo $hw['id']; ?>"><?php echo htmlspecialchars($hw['Name']); ?> (<?php echo htmlspecialchars($hw['IP']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 14px; font-weight: 500; color: var(--text-muted);">Icon auswählen</label>
                <select name="icon_id" style="padding: 12px; border-radius: 8px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8);">
                    <option value="">-- Kein Icon --</option>
                    <?php foreach ($masterIcons as $icon): ?>
                        <option value="<?php echo $icon['id']; ?>"><?php echo htmlspecialchars($icon['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 14px; font-weight: 500; color: var(--text-muted);">Tags</label>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; background: rgba(255,255,255,0.5); padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border);">
                    <?php foreach ($availableTags as $tag): ?>
                        <label style="display: flex; align-items: center; gap: 5px; font-size: 12px; cursor: pointer;">
                            <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>">
                            <span style="color: <?php echo $tag['color']; ?>;"><?php echo htmlspecialchars($tag['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($availableTags)): ?>
                        <span style="font-size: 12px; color: var(--text-muted);">Keine Tags in Stammdaten definiert.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 14px; font-weight: 500; color: var(--text-muted);">Services</label>
                <div id="services-container" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Dynamische Service Zeilen werden hier per JS eingefügt -->
                </div>
                <button type="button" onclick="addServiceRow()" class="btn btn-secondary" style="margin-top: 5px; padding: 8px; align-self: flex-start; font-size: 13px; background: rgba(0,0,0,0.05); border: 1px dashed var(--glass-border); color: var(--text-main); border-radius: 6px; cursor: pointer;">+ Service hinzufügen</button>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 16px; width: 100%;">Gerät Speichern</button>
        </form>
    </div>
</div>

<script>
const serviceTemplates = {
    'http': { name: 'HTTP', port: 80 },
    'https': { name: 'HTTPS', port: 443 },
    'ssh': { name: 'SSH', port: 22 },
    'ftp': { name: 'FTP', port: 21 },
    'dns': { name: 'DNS', port: 53 },
    'rdp': { name: 'RDP', port: 3389 },
    'smb': { name: 'SMB', port: 445 },
    'ping': { name: 'ICMP/Ping', port: '' },
    'mysql': { name: 'MySQL', port: 3306 },
    'postgres': { name: 'PostgreSQL', port: 5432 }
};

let serviceIndex = 0;

function addServiceRow() {
    const container = document.getElementById('services-container');
    const index = serviceIndex++;
    
    const row = document.createElement('div');
    row.style.display = 'flex';
    row.style.gap = '8px';
    row.style.alignItems = 'center';
    
    let options = '<option value="">-- Typ wählen --</option>';
    for (const [key, val] of Object.entries(serviceTemplates)) {
        options += `<option value="${key}">${val.name}</option>`;
    }
    options += '<option value="custom">Benutzerdefiniert</option>';

    row.innerHTML = `
        <select onchange="handleTemplateChange(this, ${index})" style="padding: 10px; border-radius: 6px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8); flex: 1;">
            ${options}
        </select>
        <input type="text" name="services[${index}][name]" placeholder="Name" required style="flex: 2; padding: 10px; border-radius: 6px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8);">
        <input type="number" name="services[${index}][port]" placeholder="Port" style="flex: 1; padding: 10px; border-radius: 6px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8);">
        <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--danger-color); cursor: pointer; font-size: 20px; padding: 0 5px;" title="Entfernen">&times;</button>
    `;
    container.appendChild(row);
}

function handleTemplateChange(selectEl, index) {
    const val = selectEl.value;
    const row = selectEl.parentElement;
    const nameInput = row.querySelector(`input[name="services[${index}][name]"]`);
    const portInput = row.querySelector(`input[name="services[${index}][port]"]`);
    
    if (val && serviceTemplates[val]) {
        nameInput.value = serviceTemplates[val].name;
        portInput.value = serviceTemplates[val].port;
    } else if (val === 'custom') {
        nameInput.value = '';
        portInput.value = '';
        nameInput.focus();
    }
}

// Füge beim ersten Öffnen direkt eine leere Zeile hinzu
document.addEventListener('DOMContentLoaded', () => {
    addServiceRow();
});
</script>

<?php include 'footer.php'; ?>
