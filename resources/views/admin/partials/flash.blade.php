@if (session('ok'))
    <div class="mb-5 rounded-xl border border-success-500 bg-success-50 p-4 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-500">✓ {{ session('ok') }}</div>
@endif
@if (session('error'))
    <div class="mb-5 rounded-xl border border-error-500 bg-error-50 p-4 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-500">⚠ {{ session('error') }}</div>
@endif
