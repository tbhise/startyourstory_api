<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled daily at 02:00.
 * Finds engagements in 'submitted' / 'resubmitted' status whose latest
 * submission has been waiting for firm review for more than 7 days, then
 * auto-approves them and notifies both creator and firm.
 *
 * Prevents firms from downloading work and ghosting creators.
 */
class AutoApproveEngagementsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    private const DAYS = 7;

    public function handle(): void
    {
        $deadline = now()->subDays(self::DAYS);

        // Find engagements whose latest submission (highest revision_round)
        // is still 'submitted' and was created more than DAYS days ago.
        $engagements = DB::table('creator_engagements as ce')
            ->join('engagement_submissions as es', function ($join) {
                $join->on('es.engagement_id', '=', 'ce.id')
                     ->whereRaw('es.revision_round = (
                         SELECT MAX(revision_round)
                         FROM engagement_submissions
                         WHERE engagement_id = ce.id
                     )');
            })
            ->join('firm_profiles as fp', 'fp.id', '=', 'ce.firm_id')
            ->where('es.status', 'submitted')
            ->whereIn('ce.status', ['submitted', 'resubmitted'])
            ->where('es.created_at', '<', $deadline)
            ->select([
                'ce.id                  as engagement_id',
                'ce.creator_id',
                'ce.creator_requirement_id',
                'fp.user_id             as firm_user_id',
                'es.id                  as submission_id',
            ])
            ->get();

        if ($engagements->isEmpty()) {
            return;
        }

        Log::info('AutoApproveEngagementsJob: auto-approving stale submissions', [
            'count' => $engagements->count(),
        ]);

        foreach ($engagements as $eng) {
            try {
                DB::table('engagement_submissions')->where('id', $eng->submission_id)->update([
                    'status'      => 'approved',
                    'reviewed_by' => null,
                    'reviewed_at' => now(),
                    'updated_at'  => now(),
                ]);

                DB::table('creator_engagements')->where('id', $eng->engagement_id)->update([
                    'status'     => 'approved',
                    'updated_at' => now(),
                ]);

                DB::table('engagement_timeline')->insert([
                    'engagement_id' => $eng->engagement_id,
                    'user_id'       => null,
                    'role'          => 'system',
                    'event'         => 'auto_approved',
                    'note'          => 'Auto-approved after ' . self::DAYS . ' days without firm review.',
                    'meta'          => json_encode([
                        'days'          => self::DAYS,
                        'submission_id' => (int) $eng->submission_id,
                    ]),
                    'created_at'    => now(),
                ]);

                $title = DB::table('creator_projects')
                    ->where('id', $eng->creator_requirement_id)
                    ->value('title') ?? 'your project';

                DB::table('creator_marketplace_notifications')->insert([
                    [
                        'user_id'    => $eng->creator_id,
                        'type'       => 'work_approved',
                        'title'      => 'Your work has been auto-approved',
                        'body'       => "Your submission for \"{$title}\" was automatically approved after "
                                        . self::DAYS . " days of no firm response.",
                        'data'       => json_encode(['engagement_id' => (int) $eng->engagement_id]),
                        'read_at'    => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'user_id'    => $eng->firm_user_id,
                        'type'       => 'auto_approved',
                        'title'      => 'Work auto-approved — no review action',
                        'body'       => "The submission for \"{$title}\" was auto-approved after "
                                        . self::DAYS . " days without your review.",
                        'data'       => json_encode(['engagement_id' => (int) $eng->engagement_id]),
                        'read_at'    => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);

            } catch (\Throwable $e) {
                Log::error('AutoApproveEngagementsJob: failed for engagement ' . $eng->engagement_id, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
