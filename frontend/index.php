<?php include 'header.php'; ?>

<div class="hero-section">
    <div class="hero-text">
        <h1 class="gradient-text">Netzwerk-Status auf einen Blick</h1>
        <p>Überwachen und verwalten Sie alle Services in Ihrem Netzwerk in Echtzeit. Automatische Dokumentation und Health-Checks in einem zentralen Dashboard.</p>
    </div>
    <div class="hero-actions">
        <button class="btn btn-primary btn-glow">Netzwerk Scannen</button>
        <button class="btn btn-secondary">Bericht generieren</button>
    </div>
</div>

<div class="dashboard-grid">
    <div class="glass-card stat-card">
        <div class="stat-icon pulse">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        </div>
        <div class="stat-info">
            <h3>System Status</h3>
            <div class="status-indicator">
                <span class="dot healthy"></span> <span>Alle Systeme Online</span>
            </div>
        </div>
    </div>

    <div class="glass-card stat-card">
        <div class="stat-icon blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
        </div>
        <div class="stat-info">
            <h3>Erkannte Services</h3>
            <p class="stat-number">24</p>
        </div>
    </div>

    <div class="glass-card stat-card">
        <div class="stat-icon purple">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <h3>Letztes Update</h3>
            <p class="stat-value">Vor 2 Minuten</p>
        </div>
    </div>
</div>

<div class="section-header">
    <h2>Kürzliche Aktivitäten</h2>
</div>

<div class="glass-card table-card">
    <table class="modern-table">
        <thead>
            <tr>
                <th>Service</th>
                <th>IP Adresse</th>
                <th>Status</th>
                <th>Zuletzt geprüft</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Nginx Reverse Proxy</td>
                <td>10.39.94.50</td>
                <td><span class="badge badge-success">Online</span></td>
                <td>Gerade eben</td>
                <td><button class="btn-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></button></td>
            </tr>
            <tr>
                <td>PostgreSQL Database</td>
                <td>10.39.94.51</td>
                <td><span class="badge badge-success">Online</span></td>
                <td>Vor 1 Min</td>
                <td><button class="btn-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></button></td>
            </tr>
            <tr>
                <td>Legacy Print Server</td>
                <td>10.39.94.102</td>
                <td><span class="badge badge-warning">Latenz</span></td>
                <td>Vor 5 Min</td>
                <td><button class="btn-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></button></td>
            </tr>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
