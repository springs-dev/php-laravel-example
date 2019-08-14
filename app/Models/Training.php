<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{

    const TYPE_PLANNED = 1;
    const TYPE_EXECUTED = 2;

    const STATUS_INACTIVE = 0;
    const STATUS_PLANNED = 1;
    const STATUS_IN_PLANNING = 2;
    const STATUS_IMPLEMENTED = 3;
    const STATUS_IN_PROGRESS = 4;
    const STATUS_POSTPONED = 5;



    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'status',
        'lecturer',
        'date_start',
        'date_end',
        'location',
        'days',
        'cost',
        'planned_participants',
        'provider_company',
        'training_group_id',
        'training_course_id',
        'cost_per_participant',
        'cost_per_day',
        'is_day',
        'is_week',
        'is_change_date'
    ];

    protected $table = 'trainings';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function participants()
    {
        return $this->belongsToMany('App\Models\Employee','training_participants','training_id', 'employee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function participants_fill_in()
    {
        return $this->belongsToMany('App\Models\Employee','trainings_fillin','training_id', 'employee_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function training_course()
    {
        return $this->hasOne('App\Models\TrainingCourse', 'id', 'training_course_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function training_group()
    {
        return $this->hasOne('App\Models\TrainingGroup', 'id', 'training_group_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function responsive_users()
    {
        return $this->belongsToMany('App\Models\Employee','users_trainings','training_id', 'user_id');
    }

}
