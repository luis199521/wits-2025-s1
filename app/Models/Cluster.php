<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperCluster
 */
class Cluster extends Model
{
    /** @use HasFactory<\Database\Factories\ClusterFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'qualification',
        'state_code',
        // 'course_id',
    ];

    /**
     * @return BelongsToMany
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_cluster',
            'cluster_id', 'course_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'cluster_unit',
            'cluster_id', 'unit_id')
            ->withTimestamps();
    }

    // A cluster can have many timetables.
    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }

    //A cluster can share timetables with other clusters (cluster 1, 2,3 can share Monday 1pm- 4pm).

    public function sharedTimetables()
    {
        return $this->belongsToMany(Timetable::class, 'timetable_cluster');
    }

    /*public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'id');
    }*/

    /*public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }*/
}
