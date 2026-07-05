<?php

namespace App\Services\Campaign;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * Recipient resolution for bulk email campaigns.
 *
 * Single reusable source for "who gets this campaign": role-filtered, deleted
 * accounts excluded, blank emails excluded at the SQL level. Full-audience
 * fetches stream via lazyById (bounded memory) so a 100k-user campaign never
 * loads everyone at once; --limit runs use a plain bounded query.
 *
 * Future segments (creators, inactive-30d, incomplete-profile, …) belong here
 * as new methods so every campaign shares one recipient vocabulary.
 */
class CampaignRecipientService
{
    /** Rows per chunk when streaming a full audience. */
    private const CHUNK = 500;

    public function students(?int $limit = null): LazyCollection
    {
        return $this->byRole('student', $limit);
    }

    public function firms(?int $limit = null): LazyCollection
    {
        return $this->byRole('firm', $limit);
    }

    /**
     * All sendable users of a role — `id, name, email` — streamed lazily.
     *
     * @return LazyCollection<int, object>
     */
    public function byRole(string $role, ?int $limit = null): LazyCollection
    {
        if ($limit !== null) {
            return LazyCollection::make(
                $this->baseQuery($role)->orderBy('users.id')->limit($limit)->get()
            );
        }

        return $this->baseQuery($role)->lazyById(self::CHUNK, 'users.id', 'id');
    }

    /**
     * Single-recipient set for --email runs. Uses the matching user's real name
     * when the address belongs to a user of this role; otherwise a stub row so
     * arbitrary test addresses still work (the campaign's fallback name applies).
     *
     * @return LazyCollection<int, object>
     */
    public function byEmail(string $role, string $email): LazyCollection
    {
        $row = $this->baseQuery($role)->where('users.email', $email)->first();

        return LazyCollection::make([
            $row ?? (object) ['id' => null, 'name' => null, 'email' => $email],
        ]);
    }

    public function count(string $role): int
    {
        return $this->baseQuery($role)->count();
    }

    /** @return array<int, object{id:int,name:?string,email:string}> */
    public function sample(string $role, int $n = 5): array
    {
        return $this->baseQuery($role)->orderBy('users.id')->limit($n)->get()->all();
    }

    private function baseQuery(string $role)
    {
        return DB::table('users')
            ->where('users.role', $role)
            ->where('users.is_deleted', 0)
            ->whereNotNull('users.email')
            ->where('users.email', '<>', '')
            ->select(['users.id', 'users.name', 'users.email']);
    }
}
