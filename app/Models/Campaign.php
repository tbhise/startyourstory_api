<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single campaign run (see migration create_campaigns_table).
 *
 * Created by the admin Campaign API or the `mail:reengagement` CLI command, then
 * processed asynchronously by ProcessCampaignJob → ReEngagementCampaignService.
 */
class Campaign extends Model
{
    protected $table = 'campaigns';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_RUNNING   = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';

    public const FROM_ADMIN     = 'admin';
    public const FROM_CLI       = 'cli';
    public const FROM_SCHEDULER = 'scheduler';

    protected $fillable = [
        'campaign_type',
        'campaign_name',
        'target_type',
        'verification_status',
        'profile_completion_status',
        'filters',
        'eligible_count',
        'sent_count',
        'failed_count',
        'opened_count',
        'clicked_count',
        'status',
        'initiated_from',
        'executed_by_admin_id',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters'      => 'array',
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
