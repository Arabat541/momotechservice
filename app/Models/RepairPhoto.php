<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairPhoto extends Model
{
    protected $fillable = ['repair_id', 'chemin', 'legende', 'type'];

    public function repair()
    {
        return $this->belongsTo(Repair::class, 'repair_id');
    }

    public function url(): string
    {
        return route('repair-photos.serve', $this->id);
    }
}
