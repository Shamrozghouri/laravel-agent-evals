<?php

namespace Ali\LaravelAgentEvals\Http\Controllers;

use Ali\LaravelAgentEvals\BaselineStore;
use Ali\LaravelAgentEvals\EvalResult;
use Ali\LaravelAgentEvals\EvalRunner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

final class AgentEvalsDashboardController
{
    public function __construct(
        private readonly BaselineStore $baselineStore,
    ) {}

    public function index(Request $request): View
    {
        $results = $request->session()->get('agent-evals.latest-results');
        $error = $request->session()->get('agent-evals.latest-error');

        if (! is_array($results)) {
            try {
                $results = array_map(
                    static fn (EvalResult $result): array => $result->toArray(),
                    $this->baselineStore->load()
                );
            } catch (Throwable $e) {
                $results = [];
                $error = 'Unable to load the baseline: '.$e->getMessage();
            }
        }

        $passed = count(array_filter($results, static fn (array $result): bool => $result['passed']));

        return view('agent-evals::dashboard', [
            'results' => $results,
            'passed' => $passed,
            'failed' => count($results) - $passed,
            'error' => $error,
            'ran' => $request->session()->has('agent-evals.latest-results'),
        ]);
    }

    public function run(Request $request, EvalRunner $runner): RedirectResponse
    {
        try {
            $report = $runner->run();
            $results = array_map(
                static fn (EvalResult $result): array => $result->toArray(),
                $report['results']
            );

            $failed = count(array_filter($results, static fn (array $result): bool => ! $result['passed']));

            if ($failed === 0 && $report['regressions'] === []) {
                $runner->saveBaseline($report['results']);
            }

            return redirect()
                ->route('agent-evals.dashboard')
                ->with('agent-evals.latest-results', $results)
                ->with('agent-evals.latest-error', $failed > 0
                    ? 'The baseline was not updated because one or more evals failed.'
                    : null);
        } catch (Throwable $e) {
            return redirect()
                ->route('agent-evals.dashboard')
                ->with('agent-evals.latest-error', 'Agent eval run failed: '.$e->getMessage());
        }
    }
}
