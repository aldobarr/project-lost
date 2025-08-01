<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\Admin\CardRequest;
use App\Http\Requests\Admin\ReplaceCardImage;
use App\Http\Resources\Admin\CardResource;
use App\Http\Resources\Admin\Cards;
use App\Models\Card;
use App\Models\Tag;
use App\Services\CardService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CardsController extends AdminController {
	public function cards(Request $request) {
		$cards = Card::with('tags')->orderBy('id');
		if ($request->has('search') && !empty($request->input('search'))) {
			$search = $request->input('search');
			$tags = array_map('trim', explode(',', $search));
			$cards->where(function(Builder $query) use ($tags) {
				foreach ($tags as $tag) {
					$query->whereLike('name', '%' . $tag . '%');
				}

				$query->orWhereHas('tags', function(Builder $q) use ($tags) {
					$q->whereArrayAny('name', $tags);
				});
			});
		}

		return new Cards($cards->paginate(perPage: static::RESULTS_PER_PAGE)->withQueryString());
	}

	public function createCard(CardRequest $request) {
		$link = $request->input('link');
		$card_data = CardService::fromYugipediaLink($link);

		Validator::make(['link' => $card_data->getName()], [
			'link' => ['unique:App\Models\Card,name']
		])->validate();

		DB::transaction(function() use ($card_data, $link, $request) {
			$card = new Card;
			$card->name = $card_data->getName();
			$card->attribute = $card_data->getAttribute();
			$card->type = $card_data->getType();
			$card->deck_type = $card_data->getDeckType();
			$card->property = $card_data->getProperty();
			$card->passcode = $card_data->getPasscode();
			$card->level = $card_data->getLevel();
			$card->attack = $card_data->getAttack();
			$card->defense = $card_data->getDefense();
			$card->description = $card_data->getDescription();
			$card->link = $link;
			$card->limit = $request->input('limit');
			$card->legendary = $request->input('legendary', false);
			$card->save();

			$card->monsterTypes()->sync($card_data->getMonsterTypes());
			if ($request->has('tags')) {
				$tags = Tag::whereIn('id', $request->input('tags'))->get();
				$card->tags()->saveMany($tags);
			}

			$card->alternates()->createMany($card_data->getAllImages());
		});

		return $this->cards($request);
	}

	public function editCard(CardRequest $request, Card $card) {
		$card->limit = $request->input('limit');
		$card->legendary = $request->input('legendary', false);
		if ($request->input('errata')) {
			$card->is_errata = true;
			$card->errata_description = $request->input('description');
		} else {
			$card->is_errata = false;
			$card->errata_description = null;
		}

		$card->save();

		$card->tags()->sync($request->input('tags', []));

		return new CardResource(Card::with('tags')->where('id', $card->id)->first());
	}

	public function imageRules() {
		return response()->json([
			'success' => true,
			'data' => [
				'allowed_extensions' => Card::ALLOWED_IMAGE_EXTENSIONS,
				'max_size' => Card::MAX_IMAGE_SIZE,
			]
		]);
	}

	public function replaceCardImage(ReplaceCardImage $request, Card $card) {
		$file = $request->file('image');

		try {
			$buffer = finfo_open(FILEINFO_MIME_TYPE);
			$type = finfo_file($buffer, $file->getRealPath());
			finfo_close($buffer);

			if ($type === false) {
				throw ValidationException::withMessages([
					'image' => ['Invalid file type.']
				]);
			}

			$allowed = false;
			$type = strtolower($type);
			foreach (Card::ALLOWED_IMAGE_EXTENSIONS as $ext) {
				if (strcmp($type, 'image/' . $ext) === 0) {
					$allowed = true;
					break;
				}
			}

			if (!$allowed) {
				Validator::make(['image' => 'invalid'], [
					'image' => ['mimes:' . implode(',', Card::ALLOWED_IMAGE_EXTENSIONS)]
				])->validate();
			}

			$ext = explode('/', $type)[1];
			$ext = strcasecmp($type, 'image/jpeg') !== 0 ? $ext : 'jpg';
			$alternate = $card->alternates()->where('passcode', $card->passcode)->first();
			if (!$alternate) {
				throw ValidationException::withMessages([
					'image' => ['The image data for this card is possibly corrupt.']
				]);
			}

			$image_path = "{$alternate->card_id}/{$alternate->id}.{$ext}";
			if (Storage::disk('r2')->put($image_path, $file->getContent(), 'public')) {
				$alternate->image = $image_path;
				$alternate->save();
			}
		} finally {
			unlink($file->getRealPath());
		}

		return new CardResource($card->load('alternates'));
	}

	public function restoreCardImage(Card $card) {
		$alternate = $card->alternates()->where('passcode', $card->passcode)->first();
		if (!$alternate) {
			throw ValidationException::withMessages([
				'image' => ['The image data for this card is possibly corrupt.']
			]);
		}

		if (empty($alternate->link)) {
			throw ValidationException::withMessages([
				'image' => ['No original image link found for this card.']
			]);
		}

		try {
			$alternate->deleteImage();
			$alternate->storeImage();
		} catch (\Exception $e) {
			throw ValidationException::withMessages([
				'image' => [$e->getMessage()]
			]);
		}

		return new CardResource($card->load('alternates'));
	}

	public function deleteCard(Card $card) {
		$card->delete();

		return $this->cards($this->getRequest('admin.cards'));
	}
}
