<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TabResource extends JsonResource {
	/**
	 * Transform the resource into an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'content' => $this->content,
			'createdAt' => $this->created_at,
			'updatedAt' => $this->updated_at,
		];
	}
}
