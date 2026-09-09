<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAlert extends Model
{
    protected $fillable = [
        'type',
        'title',
        'body',
        'speak_text',
        'action_url',
        'meta',
        'read_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'read_at' => 'datetime',
    ];

    public function markRead(): void
    {
        if ($this->read_at) {
            return;
        }

        $this->update(['read_at' => now()]);
    }

    public function toPollArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'speak_text' => $this->speak_text,
            'action_url' => $this->action_url,
            'meta' => $this->meta,
            'is_read' => (bool) $this->read_at,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'created_human' => optional($this->created_at)?->diffForHumans(),
        ];
    }
}
