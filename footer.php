</div> <!-- End page-content -->
</main>
</div>

<style>
    body {
        opacity: 1;
        transition: opacity 0.3s ease-in-out;
    }

    body.page-fade-out {
        opacity: 0;
    }
</style>

<script>
    (function () {
        if (!document.querySelector('.sidebar')) return;
        let lastPing = 0;
        const PING_INTERVAL = 60000;
        function sendPing() {
            const now = Date.now();
            if (now - lastPing < PING_INTERVAL - 5000) return;
            lastPing = now;
            fetch('ping.php', { credentials: 'same-origin', cache: 'no-store' }).catch(() => { });
        }
        ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => window.addEventListener(evt, sendPing, { passive: true }));
        sendPing();
        setInterval(sendPing, PING_INTERVAL);
    })();

    document.addEventListener('DOMContentLoaded', () => {
        document.body.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link &&
                link.href &&
                link.hostname === window.location.hostname &&
                !link.hasAttribute('target') &&
                !link.href.includes('#') &&
                !link.href.startsWith('javascript:')) {
                e.preventDefault();
                document.body.classList.add('page-fade-out');
                setTimeout(() => { window.location.href = link.href; }, 300);
            }
        });

        document.body.addEventListener('submit', (e) => {
            if (e.defaultPrevented) return;
            const form = e.target;
            const msg = form.dataset.confirm;
            if (msg && !confirm(msg)) {
                e.preventDefault();
                return;
            }
            document.body.classList.add('page-fade-out');
        });

        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                document.body.classList.remove('page-fade-out');
            }
        });
    });
</script>
</body>

</html>