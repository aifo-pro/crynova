{{-- Brief splash shown on cabinet/admin entry; fades out once the page is ready. --}}
<div id="app-preloader"
     class="fixed inset-0 z-[100] flex flex-col items-center justify-center gap-5 bg-[#f7f9fc] transition-opacity duration-500">
    <div class="animate-pulse-soft">
        <x-logo variant="mark" class="h-16 w-16 rounded-2xl shadow-lg shadow-blue-600/20" />
    </div>
    <p class="text-sm font-semibold text-slate-500">{{ __('ui.loading') }}</p>
</div>

<style>
    @keyframes pulse-soft { 0%,100% { transform: scale(1); opacity: 1 } 50% { transform: scale(1.08); opacity: .75 } }
    .animate-pulse-soft { animation: pulse-soft 1.1s ease-in-out infinite; }
    #app-preloader.is-hidden { opacity: 0; pointer-events: none; }
</style>

<script>
    (function () {
        var el = document.getElementById('app-preloader');
        if (!el) return;
        var hide = function () {
            setTimeout(function () {
                el.classList.add('is-hidden');
                setTimeout(function () { el.remove(); }, 550);
            }, 550); // brief minimum display
        };
        if (document.readyState === 'complete') hide();
        else window.addEventListener('load', hide);
    })();
</script>
