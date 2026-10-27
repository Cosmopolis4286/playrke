<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLevel extends Model
{
    use Auditable;
    use SnowflakeId;

    public $incrementing = false;

    protected $appends = [
        'progress_percentage',
        'xp_needed_for_next_level',
    ];

    protected $casts = [
        'current_level' => 'integer',
        'current_xp' => 'integer',
        'total_xp' => 'integer',
        'xp_to_next_level' => 'integer',
        'leveled_up_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'current_level',
        'current_xp',
        'total_xp',
        'xp_to_next_level',
        'leveled_up_at',
    ];

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $table = 'user_levels';

    public static function createForUser(User $user) : self
    {
        return static::create([
            'user_id' => $user->id,
            'current_level' => 1,
            'current_xp' => 0,
            'total_xp' => 0,
            'xp_to_next_level' => 1000,
        ]);
    }

    public function addXp(int $amount) : bool
    {
        if ($amount <= 0) {
            return false;
        }

        $this->current_xp += $amount;
        $this->total_xp += $amount;

        $leveledUp = false;
        while ($this->current_xp >= $this->xp_to_next_level) {
            $this->current_xp -= $this->xp_to_next_level;
            $this->current_level++;
            $this->xp_to_next_level = $this->calculateXpForNextLevel();
            $this->leveled_up_at = now();
            $leveledUp = true;
        }

        $this->save();

        return $leveledUp;
    }

    public function calculateXpForNextLevel() : int
    {
        return (int) (1000 * 1.2 ** ($this->current_level - 1));
    }

    public function getProgressPercentageAttribute() : float
    {
        if ($this->xp_to_next_level === 0) {
            return 100.0;
        }

        return min(100.0, ($this->current_xp / $this->xp_to_next_level) * 100);
    }

    public function getXpNeededForNextLevelAttribute() : int
    {
        return max(0, $this->xp_to_next_level - $this->current_xp);
    }

    public function recalculateLevel() : void
    {
        $this->current_level = 1;
        $this->current_xp = $this->total_xp;
        $this->xp_to_next_level = $this->calculateXpForNextLevel();

        while ($this->current_xp >= $this->xp_to_next_level) {
            $this->current_xp -= $this->xp_to_next_level;
            $this->current_level++;
            $this->xp_to_next_level = $this->calculateXpForNextLevel();
        }

        $this->save();
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('current_level', $level);
    }

    public function scopeByTotalXp($query, $operator = '>=', int $xp = 0)
    {
        return $query->where('total_xp', $operator, $xp);
    }

    public function scopeOrderByLevel($query, $direction = 'desc')
    {
        return $query->orderBy('current_level', $direction)
            ->orderBy('total_xp', $direction);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
