<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\SnowflakeId;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use SnowflakeId;
    use Auditable;

    protected $table = 'achievements';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'badge_color',
        'xp_reward',
        'requirements',
        'category',
        'is_active',
        'is_hidden',
        'sort_order',
    ];

    protected $casts = [
        'requirements' => 'array',
        'xp_reward' => 'integer',
        'is_active' => 'boolean',
        'is_hidden' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'badge_style',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot(['unlocked_at', 'progress_data', 'is_seen'])
            ->withTimestamps();
    }

    public function userAchievements()
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function getBadgeStyleAttribute(): string
    {
        $colors = [
            'gray' => 'bg-gray-500',
            'blue' => 'bg-blue-500',
            'green' => 'bg-green-500',
            'yellow' => 'bg-yellow-500',
            'red' => 'bg-red-500',
            'purple' => 'bg-purple-500',
            'pink' => 'bg-pink-500',
            'indigo' => 'bg-indigo-500',
            'orange' => 'bg-orange-500',
            'teal' => 'bg-teal-500',
            'gold' => 'bg-amber-500',
            'cyan' => 'bg-cyan-500',
        ];

        return $colors[$this->badge_color] ?? 'bg-gray-500';
    }

    public function getIconUrlAttribute(): ?string
    {
        if (!$this->icon) {
            return null;
        }

        if (str_starts_with($this->icon, 'http')) {
            return $this->icon;
        }

        return asset("storage/achievements/{$this->icon}");
    }

    public function getProgressForUser(User $user): array
    {
        $userAchievement = $this->userAchievements()
            ->where('user_id', $user->id)
            ->first();

        if (!$userAchievement) {
            return [
                'is_unlocked' => false,
                'progress' => 0,
                'total' => $this->getRequiredTotal(),
                'percentage' => 0,
                'unlocked_at' => null,
            ];
        }

        $progress = $this->calculateProgress($user, $userAchievement->progress_data ?? []);

        return [
            'is_unlocked' => true,
            'progress' => $progress['current'],
            'total' => $progress['total'],
            'percentage' => $progress['percentage'],
            'unlocked_at' => $userAchievement->unlocked_at,
        ];
    }

    public function calculateProgress(User $user, array $progressData = []): array
    {
        $requirements = $this->requirements;
        $type = $requirements['type'] ?? 'count';
        $target = $requirements['target'] ?? 1;

        switch ($type) {
            case 'games_played':
                $current = $user->games_played_count;
                break;
            case 'games_won':
                $current = $user->games_won_count;
                break;
            case 'total_winnings':
                $current = $user->total_winnings;
                break;
            case 'login_streak':
                $current = $user->login_streak_days;
                break;
            case 'level_reached':
                $current = $user->getCurrentLevel();
                break;
            case 'referrals':
                // Solo referidos verificados — mismo criterio que el XP
                // (AwardReferralXpOnVerifiedListener), evita que el logro se
                // desbloquee con cuentas creadas pero nunca confirmadas.
                $current = $user->referrals()->whereNotNull('email_verified_at')->count();
                break;
            case 'custom':
                $current = $progressData['current'] ?? 0;
                break;
            default:
                $current = 0;
        }

        return [
            'current' => $current,
            'total' => $target,
            'percentage' => min(100, ($current / $target) * 100),
        ];
    }

    public function getRequiredTotal(): int
    {
        return $this->requirements['target'] ?? 1;
    }

    public function isUnlockedByUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function unlockForUser(User $user): UserAchievement
    {
        $userAchievement = UserAchievement::create([
            'user_id' => $user->id,
            'achievement_id' => $this->id,
            'unlocked_at' => now(),
            'progress_data' => [],
        ]);

        // Añadir recompensa de XP al usuario (vía servicio para quedar auditado)
        if ($this->xp_reward > 0) {
            app(\App\Services\User\UserLevelService::class)
                ->addXpToUser($user, $this->xp_reward, "Logro desbloqueado: {$this->name}");
        }

        return $userAchievement;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopeOrderByOrder($query, $direction = 'asc')
    {
        return $query->orderBy('sort_order', $direction);
    }
}
