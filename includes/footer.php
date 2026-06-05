</main>

<footer class="bg-white border-t border-gray-200 mt-12">
    <div class="max-w-7xl mx-auto px-4 py-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-gray-500">
        <span>&copy; <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?> &mdash; Vossie students only.</span>
        <span>Only <strong class="text-gray-700">@vossie.net</strong> accounts are permitted.</span>
    </div>
</footer>

<script src="/assets/script.js"></script>
<script>
function toggleAvatarMenu() {
    const dropdown = document.getElementById('avatar-dropdown');
    if (dropdown) dropdown.classList.toggle('hidden');
}

function toggleMobileMenu() {
    const menu      = document.getElementById('mobile-menu');
    const iconOpen  = document.getElementById('menu-icon-open');
    const iconClose = document.getElementById('menu-icon-close');
    if (menu) {
        menu.classList.toggle('hidden');
        iconOpen?.classList.toggle('hidden');
        iconClose?.classList.toggle('hidden');
    }
}

// Close avatar dropdown when clicking outside
document.addEventListener('click', (e) => {
    const avatarMenu = document.getElementById('avatar-menu');
    const dropdown   = document.getElementById('avatar-dropdown');
    if (avatarMenu && dropdown && !avatarMenu.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});
</script>
</body>
</html>
