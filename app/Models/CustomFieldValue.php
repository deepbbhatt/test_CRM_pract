<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldValue extends Model
{
     protected $table = 'customfield_values';

    protected $fillable = ['contact_id','field_definition_id','value_text','value_file_path'];


    public function field()
    {
    return $this->belongsTo(CustomFieldDefination::class, 'field_definition_id');
    }
}
