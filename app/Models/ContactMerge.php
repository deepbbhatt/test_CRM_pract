<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMerge extends Model
{
    protected $fillable = ['master_contact_id','secondary_contact_id','extra_email','extra_phone'];

     protected $table = 'contact_merges';

}
