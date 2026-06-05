<?php
require_once __DIR__ . '/includes/auth.php';

require_login('/login.php');

$pageTitle = 'Scan Transaction QR - ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-10">
    <div class="card p-6 md:p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Scan Transaction QR</h1>
        <p class="text-sm text-gray-500 mb-6">
            Point your camera at the seller's QR code. Once scanned, you will be redirected to the confirmation page.
        </p>

        <div id="scanner" class="w-full max-w-md mx-auto rounded-xl overflow-hidden bg-black"></div>

        <div id="scan-status" class="mt-4 text-sm text-gray-500 text-center">
            Waiting for camera permission...
        </div>

        <div class="mt-8 border-t border-gray-200 pt-6">
            <p class="text-sm font-semibold text-gray-700 mb-2">Manual fallback</p>
            <p class="text-xs text-gray-500 mb-3">Paste a full confirmation link or token if camera scan is unavailable.</p>
            <form id="manual-form" class="flex flex-col sm:flex-row gap-3">
                <input id="manual-token"
                       type="text"
                       class="input"
                       placeholder="https://.../confirm-transaction.php?token=... or token"
                       autocomplete="off">
                <button type="submit" class="btn btn-outline">Open confirmation</button>
            </form>
        </div>

        <div class="mt-6 text-xs text-gray-400">
            Tip: For best results, use good lighting and keep the QR code inside the frame.
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" defer></script>
<script>
(function () {
    const scannerEl = document.getElementById('scanner');
    const statusEl = document.getElementById('scan-status');
    const manualForm = document.getElementById('manual-form');
    const manualToken = document.getElementById('manual-token');

    function setStatus(msg, isError = false) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.classList.toggle('text-red-600', isError);
        statusEl.classList.toggle('text-gray-500', !isError);
    }

    function extractToken(raw) {
        const value = (raw || '').trim();
        if (!value) return null;

        try {
            const url = new URL(value);
            const token = url.searchParams.get('token');
            return token ? token.trim() : null;
        } catch (err) {
            // Not a URL; continue.
        }

        const tokenMatch = value.match(/^[a-f0-9]{64}$/i);
        return tokenMatch ? tokenMatch[0] : null;
    }

    function redirectToConfirm(rawValue) {
        const token = extractToken(rawValue);
        if (!token) {
            setStatus('Could not read a valid token from the QR value.', true);
            return;
        }
        window.location.href = '/confirm-transaction.php?token=' + encodeURIComponent(token);
    }

    if (manualForm) {
        manualForm.addEventListener('submit', function (e) {
            e.preventDefault();
            redirectToConfirm(manualToken ? manualToken.value : '');
        });
    }

    function startScanner() {
        if (!window.Html5Qrcode) {
            setStatus('Scanner library failed to load. Use manual fallback below.', true);
            return;
        }

        const html5QrCode = new Html5Qrcode('scanner');
        const config = {
            fps: 10,
            qrbox: function (viewfinderWidth, viewfinderHeight) {
                const side = Math.floor(Math.min(viewfinderWidth, viewfinderHeight) * 0.7);
                return { width: side, height: side };
            }
        };

        html5QrCode.start(
            { facingMode: 'environment' },
            config,
            function onScanSuccess(decodedText) {
                setStatus('QR scanned. Redirecting...');
                html5QrCode.stop().catch(function () {}).finally(function () {
                    redirectToConfirm(decodedText);
                });
            },
            function onScanFailure() {
                // Keep scanning silently.
            }
        ).then(function () {
            setStatus('Camera ready. Point at the seller QR code.');
        }).catch(function (err) {
            setStatus('Unable to start camera scanner. Use manual fallback below.', true);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startScanner);
    } else {
        startScanner();
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
