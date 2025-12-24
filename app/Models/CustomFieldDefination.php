<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldDefination extends Model
{
        protected $fillable = ['name','slug','type','options','validation'];
        protected $table = 'customfield_definations';

        protected $casts = [
        'options' => 'array'
        ];


        public function values(): HasMany
        {
        return $this->hasMany(CustomFieldValue::class, 'field_definition_id');
        }
}
