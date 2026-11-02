<?php

namespace App\Models;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    protected $fillable = ['event_id', 'user_id', 'code', 'attended_at', 'checked_in_by'];

    protected function casts(): array
    {
        return ['attended_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function hasAttended(): bool
    {
        return $this->attended_at !== null;
    }

    /** "482193" shown as "482 193" so it is easy to read out loud. */
    public function spacedCode(): string
    {
        return substr($this->code, 0, 3) . ' ' . substr($this->code, 3);
    }

    /** What the QR contains. The officer's scanner (step 4) will read this. */
    public function qrPayload(): string
    {
        return 'CE:' . $this->event_id . ':' . $this->code;
    }

    /** Inline SVG, generated on the server. No JavaScript or image extension needed. */
    public function qrSvg(int $size = 220): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd()));
        $svg = $writer->writeString($this->qrPayload());

        return preg_replace('/^<\?xml.*?\?>\s*/', '', $svg);
    }
}
