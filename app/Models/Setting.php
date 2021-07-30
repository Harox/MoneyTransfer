<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
	protected $table = 'settings';
	protected $fillable = ['name', 'value', 'type'];
    public $timestamps = false;

    public function getSingleSetting($constraints, $selectOptions)
    {
        return $this->where($constraints)->first($selectOptions);
    }
}
