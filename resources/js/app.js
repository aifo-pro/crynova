import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

document.addEventListener('click', async (event) => {
    // Copy from an element's text (data-copy-target="elementId")
    const targetBtn = event.target.closest('[data-copy-target]');
    if (targetBtn) {
        const target = document.getElementById(targetBtn.dataset.copyTarget);
        if (!target) return;
        await navigator.clipboard.writeText(target.innerText.trim());
        flash(targetBtn);
        return;
    }

    // Copy a literal value (data-copy-text="...")
    const textBtn = event.target.closest('[data-copy-text]');
    if (textBtn) {
        await navigator.clipboard.writeText(textBtn.dataset.copyText);
        flash(textBtn);
    }
});

function flash(button) {
    const previous = button.innerHTML;
    button.innerHTML = '<span class="text-xs font-semibold">OK</span>';
    setTimeout(() => { button.innerHTML = previous; }, 1200);
}

document.documentElement.classList.remove('dark');
localStorage.removeItem('crynova-theme');
