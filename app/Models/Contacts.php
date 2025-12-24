<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contacts extends Model
{
    protected $fillable = ['name','email','phone','gender','profile_image_path','additional_file_path', 'is_active', 'merged_id'];

     protected $table = 'contacts';

    public function customValues(): HasMany
    {
    return $this->hasMany(CustomFieldValue::class, 'contact_id');
    }

public function mergedExtras()
{
    return $this->hasMany(ContactMerge::class, 'master_contact_id');
}

}
