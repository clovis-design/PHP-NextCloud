async function refreshBars() {
    try {
        const res = await fetch('apitest.php'); // ici on récupère la valeur envoyé pour calculer le % de la barre.
        const data = await res.json();
        const value = Math.max(0, Math.min(data.value, 100));
        document.querySelectorAll('[data-progress-bar]').forEach(bar => {
            bar.style.width = value + '%';
        });
    } catch (e) {
        console.error('Progress refresh failed', e);
    }
}

setInterval(refreshBars, 2000);