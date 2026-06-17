<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supervisor extends Model
{
    protected $fillable = [
        'kddsn', 'name', 'email', 'scholar_url', 'specific_topics',
        'active_titles', 'is_global_class', 'views_total', 'contacts_total',
    ];

    protected $casts = ['is_global_class' => 'boolean'];

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'supervisor_program');
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'supervisor_topic');
    }

    public function theses(): HasMany
    {
        return $this->hasMany(Thesis::class)->orderBy('position');
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function views30(): int
    {
        return $this->pageViews()
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
    }
}
