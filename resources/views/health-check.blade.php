<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAPIntake — Environment Health Check</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; padding: 2rem 1rem; }
        .container { max-width: 640px; margin: 0 auto; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
        .subtitle { color: #64748b; margin-bottom: 2rem; }
        .check { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 1rem 1.25rem; margin-bottom: 0.75rem; }
        .check-header { display: flex; align-items: center; gap: 0.75rem; font-weight: 600; }
        .icon { width: 1.5rem; height: 1.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #fff; flex-shrink: 0; }
        .icon-pass { background: #22c55e; }
        .icon-fail { background: #ef4444; }
        .check-message { color: #64748b; font-size: 0.875rem; margin-top: 0.25rem; padding-left: 2.25rem; }
        .check-fix { background: #fef3c7; border: 1px solid #fde68a; border-radius: 0.375rem; padding: 0.75rem 1rem; margin-top: 0.5rem; margin-left: 2.25rem; font-size: 0.8125rem; }
        .check-fix strong { color: #92400e; }
        .check-fix code { background: #fef9c3; padding: 0.1em 0.3em; border-radius: 0.25rem; font-size: 0.8125rem; }
        .summary { background: #fff; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 1.25rem; margin-top: 1.5rem; text-align: center; }
        .summary-pass { border-color: #bbf7d0; background: #f0fdf4; }
        .summary-fail { border-color: #fecaca; background: #fef2f2; }
        .summary h2 { font-size: 1.125rem; margin-bottom: 0.25rem; }
        .summary p { color: #64748b; font-size: 0.875rem; }
        .summary a { display: inline-block; margin-top: 0.75rem; background: #3b82f6; color: #fff; padding: 0.5rem 1.5rem; border-radius: 0.375rem; text-decoration: none; font-weight: 600; font-size: 0.875rem; }
        .summary a:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>CAPIntake — Environment Health Check</h1>
        <p class="subtitle">Verifying your server meets all requirements before setup.</p>

        @foreach ($checks as $key => $check)
            <div class="check">
                <div class="check-header">
                    <span class="icon {{ $check['passed'] ? 'icon-pass' : 'icon-fail' }}">
                        {!! $check['passed'] ? '&#10003;' : '&#10007;' !!}
                    </span>
                    {{ $check['label'] }}
                </div>
                <div class="check-message">{{ $check['message'] }}</div>
                @if (!$check['passed'] && !empty($check['fix']))
                    <div class="check-fix">
                        <strong>How to fix:</strong> {{ $check['fix'] }}
                    </div>
                @endif
            </div>
        @endforeach

        <div class="summary {{ $allPassed ? 'summary-pass' : 'summary-fail' }}">
            @if ($allPassed)
                <h2>All checks passed</h2>
                <p>Your environment is ready. Proceed to set up your agency.</p>
                <a href="/admin">Continue to Setup</a>
            @else
                <h2>Some checks failed</h2>
                <p>Fix the issues above, then refresh this page to re-check.</p>
            @endif
        </div>
    </div>
</body>
</html>
