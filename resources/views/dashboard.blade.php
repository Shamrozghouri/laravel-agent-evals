<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel Agent Evals</title>
    <style>
        :root { color-scheme: light; font-family: ui-sans-serif, system-ui, sans-serif; color: #172033; background: #f5f7fb; }
        body { margin: 0; }
        main { max-width: 1080px; margin: 0 auto; padding: 3rem 1.25rem; }
        header, .panel { background: #fff; border: 1px solid #e3e8f2; border-radius: 14px; box-shadow: 0 2px 8px rgb(30 41 59 / 5%); }
        header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.5rem; }
        h1 { font-size: 1.5rem; margin: 0; } p { color: #5e6c84; } button { background: #2563eb; color: #fff; border: 0; padding: .7rem 1rem; border-radius: 8px; cursor: pointer; font-weight: 700; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin: 1.25rem 0; }
        .stat { padding: 1.1rem; } .number { font-size: 1.8rem; font-weight: 800; } .label { color: #5e6c84; font-size: .9rem; }
        .panel { overflow: hidden; } table { width: 100%; border-collapse: collapse; } th, td { text-align: left; padding: 1rem; border-bottom: 1px solid #e8edf5; vertical-align: top; } th { color: #5e6c84; font-size: .8rem; text-transform: uppercase; }
        .pass { color: #15803d; font-weight: 700; } .fail, .alert { color: #b91c1c; font-weight: 700; } code { white-space: pre-wrap; overflow-wrap: anywhere; color: #334155; }
        .empty { padding: 2rem; text-align: center; } @media (max-width: 640px) { header { align-items: flex-start; flex-direction: column; } .stats { grid-template-columns: 1fr; } th:nth-child(3), td:nth-child(3) { display: none; } }
    </style>
</head>
<body>
    <main>
        <header>
            <div>
                <h1>Laravel Agent Evals</h1>
                <p>{{ $ran ? 'Latest evaluation run' : 'Current saved baseline' }}</p>
            </div>
            <form method="POST" action="{{ route('agent-evals.dashboard.run') }}">
                @csrf
                <button type="submit">Run evaluations</button>
            </form>
        </header>

        @if ($error)
            <p class="alert">{{ $error }}</p>
        @endif

        <section class="stats">
            <div class="panel stat"><div class="number">{{ count($results) }}</div><div class="label">Total cases</div></div>
            <div class="panel stat"><div class="number pass">{{ $passed }}</div><div class="label">Passed</div></div>
            <div class="panel stat"><div class="number fail">{{ $failed }}</div><div class="label">Failed</div></div>
        </section>

        <section class="panel">
            @if ($results === [])
                <div class="empty">No baseline exists yet. Run the evaluations to create one.</div>
            @else
                <table>
                    <thead><tr><th>Case</th><th>Status</th><th>Tags</th><th>Response / reason</th></tr></thead>
                    <tbody>
                    @foreach ($results as $result)
                        <tr>
                            <td>{{ $result['name'] }}</td>
                            <td class="{{ $result['passed'] ? 'pass' : 'fail' }}">{{ $result['passed'] ? 'PASS' : 'FAIL' }}</td>
                            <td>{{ implode(', ', $result['tags'] ?? []) ?: '—' }}</td>
                            <td><code>{{ $result['passed'] ? $result['response'] : $result['reason'] }}</code></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>
</body>
</html>
