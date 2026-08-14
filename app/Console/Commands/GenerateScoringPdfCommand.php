<?php

namespace App\Console\Commands;

use App\Models\DailyScore;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Console\Command;

class GenerateScoringPdfCommand extends Command
{
    protected $signature = 'scoring:pdf {--user= : User email to include their 30-day score data}';

    protected $description = 'Generate a PDF explaining how the daily productivity score works.';

    public function handle(): int
    {
        $email = $this->option('user') ?? 'demo30@example.com';
        $user = User::where('email', $email)->first();

        $scoreData = [];
        $avgScore = null;

        if ($user) {
            $scores = DailyScore::forUser($user->id)
                ->orderBy('date')
                ->get();

            foreach ($scores as $score) {
                $scoreData[] = [
                    'date' => $score->date->format('M j'),
                    'score' => round($score->score, 1),
                    'completed' => $score->completed_tasks,
                    'total' => $score->total_tasks,
                    'planned' => $score->total_planned_minutes,
                    'actual' => $score->total_actual_minutes,
                ];
            }

            $avgScore = $scores->isNotEmpty() ? round($scores->avg('score'), 1) : 0;
        }

        $html = $this->buildHtml($user, $scoreData, $avgScore);
        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'daily-productivity-scoring-guide.pdf';
        $path = storage_path("app/public/{$filename}");
        file_put_contents($path, $dompdf->output());

        $this->info("PDF saved to: storage/app/public/{$filename}");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{date: string, score: float, completed: int, total: int, planned: int, actual: int}>  $scoreData
     */
    private function buildHtml(?User $user, array $scoreData, ?float $avgScore): string
    {
        $userSection = '';
        if ($user && $scoreData) {
            $first = $scoreData[0]['score'];
            $last = $scoreData[count($scoreData) - 1]['score'];
            $trend = $last >= $first ? '+'.number_format($last - $first, 1) : number_format($last - $first, 1);

            $rows = '';
            foreach ($scoreData as $row) {
                $rows .= <<<HTML
            <tr>
                <td style="text-align:center;">{$row['date']}</td>
                <td style="text-align:center;">{$row['score']}</td>
                <td style="text-align:center;">{$row['completed']} / {$row['total']}</td>
                <td style="text-align:center;">{$row['planned']}m</td>
                <td style="text-align:center;">{$row['actual']}m</td>
            </tr>
HTML;
            }

            $userName = htmlspecialchars($user->name);

            $userSection = <<<HTML
        <hr>
        <h2 style="color:#4f46e5;">Demo Account &amp; Score Data</h2>
        <table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
            <tr>
                <td style="background:#f3f4f6; padding:8px 12px; border:1px solid #d1d5db; width:33%;"><b>Name:</b> {$userName}</td>
                <td style="background:#f3f4f6; padding:8px 12px; border:1px solid #d1d5db; width:33%;"><b>Email:</b> demo30@example.com</td>
                <td style="background:#f3f4f6; padding:8px 12px; border:1px solid #d1d5db; width:34%;"><b>Password:</b> password</td>
            </tr>
        </table>

        <p><b>30-Day Score Trend</b></p>
        <p style="background:#ecfdf5; padding:10px 14px; border-left:4px solid #10b981; margin:8px 0 16px;">
            Starting score: <b>{$first}</b> &rarr; Latest score: <b>{$last}</b>
            (Change: <b>{$trend}</b>, 30-day average: <b>{$avgScore}</b>)
        </p>

        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#4f46e5; color:#fff;">
                    <th style="padding:8px; border:1px solid #4338ca;">Date</th>
                    <th style="padding:8px; border:1px solid #4338ca;">Score</th>
                    <th style="padding:8px; border:1px solid #4338ca;">Done / Total</th>
                    <th style="padding:8px; border:1px solid #4338ca;">Planned</th>
                    <th style="padding:8px; border:1px solid #4338ca;">Actual</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>
HTML;
        }

        $generatedDate = Carbon::now()->format('M j, Y');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { font-family: 'Helvetica', 'Arial', sans-serif; }
body { color: #1f2937; font-size: 14px; line-height: 1.6; }
h1 { color: #4f46e5; font-size: 24px; margin-bottom: 4px; }
h2 { color: #374151; font-size: 18px; margin-top: 28px; }
h3 { color: #6366f1; font-size: 15px; margin-top: 20px; margin-bottom: 6px; }
code, .mono { font-family: 'Courier New', monospace; background: #f3f4f6; padding: 1px 4px; border-radius: 3px; font-size: 13px; }
.formula { background: #f0f4ff; border: 2px solid #4f46e5; border-radius: 8px; padding: 16px; margin: 12px 0; text-align: center; font-size: 16px; }
.box { border: 1px solid #d1d5db; border-radius: 6px; padding: 12px 16px; margin: 16px 0; background: #fafafa; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; border: 1px solid #d1d5db; }
th { background: #e5e7eb; font-weight: bold; }
hr { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
.badge { display: inline-block; padding: 2px 10px; border-radius: 10px; color: #fff; font-size: 12px; }
</style>
</head>
<body>

<h1>Daily Productivity Score &mdash; How It Works</h1>
<p style="color:#6b7280; font-size:12px;">Generated on {$generatedDate}</p>
<p>The Daily Planner PWA calculates a <b>0&ndash;100 productivity score</b> every day based on your tasks.
The score blends two components: <b>Task Achievement</b> (85%) and <b>Time Performance</b> (15%).</p>

<div class="formula">
    <b>Final Score = (Achievement Score &times; 0.85) + (Time Score &times; 0.15)</b>
</div>

<h2>1. Task Achievement Score (85% Weight)</h2>
<p>This measures how much of your planned work you actually completed. Each task is weighted by its
<b>priority</b> and <b>planned minutes</b>, giving more importance to high-priority and time-intensive tasks.</p>

<h3>Priority Weights</h3>
<table>
    <tr><th>Priority</th><th>Weight</th><th>Badge</th></tr>
    <tr><td>High</td><td>1.5</td><td><span class="badge" style="background:#e02424;">High</span></td></tr>
    <tr><td>Medium</td><td>1.0</td><td><span class="badge" style="background:#2563eb;">Medium</span></td></tr>
    <tr><td>Low</td><td>0.7</td><td><span class="badge" style="background:#71717a;">Low</span></td></tr>
</table>

<h3>Achievement Value by Status</h3>
<table>
    <tr><th>Task Status</th><th>Achievement Value</th><th>Explanation</th></tr>
    <tr><td><span class="badge" style="background:#059669;">Completed</span></td><td>1.0 (100%)</td><td>Task fully completed</td></tr>
    <tr><td><span class="badge" style="background:#d97706;">Partially Completed</span></td><td>progress %</td><td>Based on <code>progress_percentage</code></td></tr>
    <tr><td><span class="badge" style="background:#2563eb;">In Progress</span></td><td>progress %</td><td>Based on <code>progress_percentage</code></td></tr>
    <tr><td><span class="badge" style="background:#e02424;">Skipped</span></td><td>0.0 (0%)</td><td>No contribution</td></tr>
    <tr><td><span class="badge" style="background:#71717a;">Pending</span></td><td>0.0 (0%)</td><td>Not started yet</td></tr>
</table>

<h3>Formula</h3>
<div class="box mono" style="font-size:13px;">
Achievement Score = &Sigma;(Weight &times; Achievement) / &Sigma;(Weight) &times; 100<br><br>
where Weight = Priority Weight &times; max(1, Planned Minutes)
</div>

<h2>2. Time Performance Score (15% Weight)</h2>
<p>This measures how well you tracked your planned time. It compares actual minutes spent vs. planned minutes,
capped at 100%.</p>

<div class="box mono" style="font-size:13px;">
Time Score = min(100, (Total Actual Minutes / Total Planned Minutes) &times; 100)
</div>

<p>If there are 0 planned minutes, the time score is 0.</p>

<h2>3. Worked Example</h2>
<p>Suppose you planned 3 tasks for the day:</p>

<table>
    <tr>
        <th>Task</th>
        <th>Priority</th>
        <th>Planned Min</th>
        <th>Status</th>
        <th>Progress</th>
        <th>Actual Min</th>
        <th>Weight</th>
        <th>Weighted Achievement</th>
    </tr>
    <tr>
        <td>Study textbook</td><td>High (1.5)</td><td>120</td>
        <td><span class="badge" style="background:#059669;">Completed</span></td>
        <td>100%</td><td>110</td>
        <td>1.5 &times; 120 = 180</td>
        <td>180 &times; 1.0 = 180</td>
    </tr>
    <tr>
        <td>Build Laravel</td><td>High (1.5)</td><td>180</td>
        <td><span class="badge" style="background:#2563eb;">In Progress</span></td>
        <td>60%</td><td>108</td>
        <td>1.5 &times; 180 = 270</td>
        <td>270 &times; 0.6 = 162</td>
    </tr>
    <tr>
        <td>Learn vocabulary</td><td>Medium (1.0)</td><td>45</td>
        <td><span class="badge" style="background:#e02424;">Skipped</span></td>
        <td>0%</td><td>0</td>
        <td>1.0 &times; 45 = 45</td>
        <td>45 &times; 0.0 = 0</td>
    </tr>
    <tr style="font-weight:bold; background:#f0f4ff;">
        <td colspan="6">Totals</td>
        <td>495</td>
        <td>342</td>
    </tr>
</table>

<h3>Calculation</h3>
<table style="margin-top:12px;">
    <tr>
        <th>Component</th>
        <th>Calculation</th>
        <th>Result</th>
    </tr>
    <tr>
        <td>Achievement Score</td>
        <td>(342 / 495) &times; 100</td>
        <td><b>69.1</b></td>
    </tr>
    <tr>
        <td>Time Score</td>
        <td>(218 / 345) &times; 100, capped at 100</td>
        <td><b>63.2</b></td>
    </tr>
    <tr style="background:#f0f4ff; font-weight:bold;">
        <td colspan="2">Final Score = (69.1 &times; 0.85) + (63.2 &times; 0.15)</td>
        <td><b>68.2</b></td>
    </tr>
</table>

<h2>4. How the Score Increases</h2>
<p>Your score improves when you:</p>
<ul>
    <li><b>Complete more tasks</b> &mdash; completed tasks contribute 100% of their weighted value.</li>
    <li><b>Work on high-priority tasks</b> &mdash; they carry a 1.5&times; weight vs. 0.7&times; for low priority.</li>
    <li><b>Update progress</b> &mdash; even partial completion (setting progress to 60%) raises the score.</li>
    <li><b>Log actual minutes</b> &mdash; tracking time closer to planned minutes bumps the time component.</li>
    <li><b>Avoid skipping</b> &mdash; skipped and pending tasks contribute 0% to the achievement score.</li>
</ul>

<p style="background:#fef3c7; padding:10px 14px; border-left:4px solid #f59e0b; margin:12px 0;">
    <b>Tip:</b> The biggest single jump comes from marking a high-priority, long-duration task as
    "Completed" &mdash; it adds <code>1.5 &times; planned_minutes</code> of weighted achievement with
    a 100% achievement factor.
</p>

<h2>5. Category Breakdown</h2>
<p>Each daily score also stores a per-category breakdown of planned minutes, actual minutes, and completion
percentage. This powers the analytics charts (score trend, category doughnut, weekly stacked bar) on the
analytics page.</p>

{$userSection}

<hr>
<p style="color:#9ca3af; font-size:11px; text-align:center;">
    Daily Planner PWA &mdash; Generated by <code>php artisan scoring:pdf</code>
</p>

</body>
</html>
HTML;
    }
}
