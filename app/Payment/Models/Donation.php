<?php

namespace App\Payment\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
	protected $fillable = [
		'user_id', 'order_id', 'amount',
		'currency', 'status', 'paid_at', 'payload',
	];

	protected $casts = [
		'paid_at' => 'datetime',
		'payload' => 'array',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
