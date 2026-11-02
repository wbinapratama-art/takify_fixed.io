</div>
</main>
<script>
    document.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href');
            const isInternalPage = href && !href.startsWith('#') && !href.startsWith('http') && !href.startsWith('mailto:') && !href.startsWith('javascript:');
            if (!isInternalPage || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target === '_blank') return;

            event.preventDefault();
            document.body.classList.add('app-page-leaving');
            setTimeout(() => { window.location.href = link.href; }, 130);
        });
    });

    (function () {
        if (document.body.classList.contains('account-page')) return;
        if (!document.querySelector('.sidebar')) return;

        let lastPing = 0;
        const PING_INTERVAL = 60000;

        function sendPing() {
            const now = Date.now();
            if (now - lastPing < PING_INTERVAL - 5000) return;
            lastPing = now;
            fetch('ping.php', { credentials: 'same-origin', cache: 'no-store' }).catch(() => { });
        }

        ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt =>
            window.addEventListener(evt, sendPing, { passive: true })
        );

        sendPing();
        setInterval(sendPing, PING_INTERVAL);

        window.addEventListener('pagehide', () => {
            if (navigator.sendBeacon) {
                navigator.sendBeacon('ping.php');
            }
        });
    })();
</script>
</body>

</html>