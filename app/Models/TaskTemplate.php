<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTemplate extends Model
{
    protected $fillable = [
        'name',
        'type_task',
        'id_project',
        'start_at',
        'end_at',
        'location',
        'skills',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'start_at' => 'datetime:H:i',
        'end_at' => 'datetime:H:i',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $template) {
            if ($template->is_default) {
                static::where('id', '!=', $template->id ?? 0)->update(['is_default' => false]);
            }
        });
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::orderBy('id')->first();
    }

    public function toMultipartBase(): array
    {
        return [
            ['name' => 'type_task', 'contents' => $this->type_task],
            ['name' => 'id_project', 'contents' => $this->id_project],
            ['name' => 'start_at', 'contents' => $this->start_at->format('H:i')],
            ['name' => 'end_at', 'contents' => $this->end_at->format('H:i')],
            ['name' => 'location', 'contents' => $this->location],
            ['name' => 'skills', 'contents' => $this->skills],
            ['name' => 'custom_location', 'contents' => null],
        ];
    }
}
