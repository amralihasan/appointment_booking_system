<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceQuestionAnswer extends Model
{
    protected $fillable = [
        'appointment_id',
        'service_question_id',
        'answer_value',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ServiceQuestion::class, 'service_question_id');
    }
}
