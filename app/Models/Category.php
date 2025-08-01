<?php

namespace App\Models;

use App\Enums\CategoryType;
use App\Models\Traits\HasTableName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model {
	use HasFactory, HasTableName;

	protected $casts = [
		'type' => CategoryType::class,
	];

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = ['uuid', 'name', 'type', 'order'];

	public function cards(): BelongsToMany {
		return $this->belongsToMany(Card::class)->withPivot(['order', 'card_alternate_id', 'ownership'])->orderByPivot('order', 'asc');
	}

	public function deck(): BelongsTo {
		return $this->belongsTo(Deck::class);
	}
}
