<?php
require_once 'lib/Database.php';
$db = new Database();
$records = $db->getRecords();

include 'header.php';
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h2>Netzwerk Topologie</h2>
    <p style="color: var(--text-muted);">Grafische Darstellung deiner Netzwerkstruktur.</p>
</div>

<div class="glass-card" style="height: 700px; position: relative; padding: 0; overflow: hidden;">
    <div id="mynetwork" style="width: 100%; height: 100%;"></div>
    
    <!-- Legend / Context Info -->
    <div style="position: absolute; bottom: 20px; left: 20px; background: rgba(255,255,255,0.8); backdrop-filter: blur(4px); padding: 15px; border-radius: 12px; border: 1px solid var(--glass-border); font-size: 12px; pointer-events: none;">
        <h4 style="margin: 0 0 10px 0;">Interaktive Karte</h4>
        <ul style="margin: 0; padding-left: 15px;">
            <li>Scrollen zum Zoomen</li>
            <li>Ziehen zum Bewegen</li>
            <li>Klicken für Details</li>
        </ul>
    </div>
</div>

<!-- Vis.js Library -->
<script type="text/javascript" src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

<script type="text/javascript">
    // Daten aus PHP aufbereiten
    const nodes = new vis.DataSet([
        <?php foreach ($records as $record): ?>
        {
            id: <?php echo $record['id']; ?>,
            label: '<?php echo addslashes($record['Name']); ?>',
            title: 'IP: <?php echo $record['IP']; ?><br>Hersteller: <?php echo addslashes($record['Hersteller']); ?>',
            <?php if (!empty($record['icon_id'])): ?>
            shape: 'circularImage',
            image: 'icon.php?id=<?php echo $record['icon_id']; ?>',
            <?php else: ?>
            shape: 'dot',
            <?php endif; ?>
            color: {
                background: '#ffffff',
                border: '#3b82f6',
                highlight: { background: '#ffffff', border: '#2563eb' }
            },
            font: { color: '#1f2937', size: 14, face: 'Inter' }
        },
        <?php endforeach; ?>
    ]);

    const edges = new vis.DataSet([
        <?php foreach ($records as $record): ?>
        <?php if (!empty($record['parent_id'])): ?>
        { 
            from: <?php echo $record['parent_id']; ?>, 
            to: <?php echo $record['id']; ?>,
            color: { color: '#cbd5e1', highlight: '#3b82f6' },
            width: 2
        },
        <?php endif; ?>
        <?php endforeach; ?>
    ]);

    // Container erstellen
    const container = document.getElementById('mynetwork');
    const data = { nodes: nodes, edges: edges };
    const options = {
        nodes: {
            borderWidth: 2,
            size: 30,
            shadow: true
        },
        edges: {
            arrows: { to: { enabled: true, scaleFactor: 0.5 } },
            smooth: { type: 'cubicBezier', forceDirection: 'vertical', roundness: 0.4 }
        },
        physics: {
            forceAtlas2Based: {
                gravitationalConstant: -50,
                centralGravity: 0.01,
                springLength: 100,
                springConstant: 0.08
            },
            maxVelocity: 50,
            solver: 'forceAtlas2Based',
            timestep: 0.35,
            stabilization: { iterations: 150 }
        },
        interaction: {
            hover: true,
            tooltipDelay: 200
        }
    };

    const network = new vis.Network(container, data, options);

    // Event: Klick auf einen Knoten
    network.on("click", function (params) {
        if (params.nodes.length > 0) {
            const nodeId = params.nodes[0];
            // Später: Weiterleitung zur Detailseite oder Öffnen eines Modals
            // window.location.href = 'details.php?id=' + nodeId;
        }
    });
</script>

<?php include 'footer.php'; ?>
