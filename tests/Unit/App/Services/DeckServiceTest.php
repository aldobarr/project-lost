<?php

namespace Tests\Unit\App\Services;

use App\Enums\CardType;
use App\Enums\CategoryType;
use App\Enums\DeckType;
use App\Models\Card;
use App\Models\CardAlternate;
use App\Models\Category;
use App\Models\Deck;
use App\Models\Tag;
use App\Services\DeckService;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class DeckServiceTest extends TestCase {
	#[Test]
	public function the_constructor_standardizes_the_categories_array_to_be_an_array_of_card_ids(): void {
		$this->expectNotToPerformAssertions();

		$deck = Deck::factory()
			->has(
				Category::factory()->hasAttached(
					Card::factory()->count(random_int(50, 100)),
					new Sequence(fn($sequence) => ['order' => $sequence->index])
				)->count(5))
			->create();

		$deck->load('categories.cards');
		new DeckService($deck, $deck->categories->toArray());
	}

	#[Test]
	public function a_deck_with_no_categories_is_invalid(): void {
		$deck = Deck::factory()->create();
		$deck->load('categories.cards.tags');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectException(ValidationException::class);
		$this->expectExceptionMessage('Your deck must have a Deck Master, Extra Deck, and Side Deck category.');

		$service->validateDeck();
	}

	#[Test]
	public function a_deck_with_duplicate_categories_is_invalid(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();

		$deck->load('categories.cards.tags');
		$categories = $deck->categories->toArray();
		$categories[] = $categories[0];

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $categories, false);
		$this->expectException(ValidationException::class);
		$this->expectExceptionMessage('This deck has invalid categories.');

		$service->validateDeck();
	}

	#[Test]
	public function a_deck_without_the_correct_categories_configuration_is_invalid(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your deck must have a Deck Master, Extra Deck, and Side Deck category.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your deck must have a Deck Master, Extra Deck, and Side Deck category.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your deck must have a Deck Master, Extra Deck, and Side Deck category.');
	}

	#[Test]
	public function a_deck_with_duplicate_special_categories_is_invalid(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 3])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 4])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Only one DeckMaster category is allowed.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 3])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 3])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 4])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Only one Extra Deck category is allowed.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 3])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 4])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 4])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Only one Side Deck category is allowed.');
	}

	#[Test]
	public function a_deck_without_a_deck_master_is_invalid(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(2, ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory()->count(random_int(0, 100)),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(1, ['type' => CategoryType::EXTRA->value, 'order' => 3])->hasAttached(
			Card::factory()->count(random_int(0, 100)),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(1, ['type' => CategoryType::SIDE->value, 'order' => 4])->hasAttached(
			Card::factory()->count(random_int(0, 100)),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertFalse(DeckService::isDeckValid($deck));

		$service = new DeckService($deck, $deck->categories->toArray(), true);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your Deck Master category must contain exactly one card.');
	}

	#[Test]
	public function the_deck_master_category_must_contain_exactly_1_card(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory()->count(random_int(2, 100)),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your Deck Master category must contain exactly one card.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// requires strict to trigger exception
		$service = new DeckService($deck, $deck->categories->toArray(), true);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your Deck Master category must contain exactly one card.');

		// does not trigger with loose validation when deck master is empty
		$this->assertTrue(DeckService::isDeckValid($deck, false));
	}

	#[Test]
	public function the_deck_master_category_must_be_first_in_order(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your Deck Master category must be the first category.');
	}

	#[Test]
	public function the_extra_deck_category_must_be_second_to_last_in_order(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your Extra Deck category must be the second to last category.');
	}

	#[Test]
	public function the_extra_deck_category_must_have_no_more_than_15_cards(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::EXTRA->value])->count(random_int(16, 100)),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// requires strict validation
		$service = new DeckService($deck, $deck->categories->toArray(), true);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your Extra Deck may not contain more than ' . DeckService::MAX_EXTRA_DECK_CARDS . ' cards.');

		// does not trigger with loose validation
		$this->assertTrue(DeckService::isDeckValid($deck, false));
	}

	#[Test]
	public function the_side_deck_category_must_be_last_in_order(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 3])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 1])->for($deck)->create();

		$deck->load('categories.cards');

		// should throw exception even with loose validation.
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your Side Deck category must be the last category.');
	}

	#[Test]
	public function the_side_deck_category_must_have_no_more_than_15_cards(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->hasAttached(
			Card::factory()->count(random_int(16, 100)),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();

		$deck->load('categories.cards');

		// requires strict validation
		$service = new DeckService($deck, $deck->categories->toArray(), true);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your Side Deck may not contain more than ' . DeckService::MAX_EXTRA_DECK_CARDS . ' cards.');

		// does not trigger with loose validation
		$this->assertTrue(DeckService::isDeckValid($deck, false));
	}

	#[Test]
	public function a_deck_must_contain_exactly_59_main_deck_cards_and_1_deck_master_card(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(60),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// requires strict validation
		$service = new DeckService($deck, $deck->categories->toArray(), true);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Your Main Deck must contain exactly ' . DeckService::MAIN_DECK_CARDS . ' cards including the Deck Master.');

		// does not trigger with loose validation
		$this->assertTrue(DeckService::isDeckValid($deck, false));

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));
	}

	#[Test]
	public function a_normal_card_may_not_be_in_the_extra_deck(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 1])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// throws even with loose validation
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'cannot be in the extra deck.');
	}

	#[Test]
	public function an_extra_deck_card_may_not_be_in_the_main_deck(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory()->count(59)->sequence(fn($sequence) => [
				'deck_type' => $sequence->index === 0 ? DeckType::EXTRA : DeckType::NORMAL
			]),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// throws even with loose validation
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'cannot be in the main deck.');
	}

	#[Test]
	public function only_monster_cards_may_be_in_extra_deck(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->hasAttached(
			Card::factory(state: ['type' => CardType::SPELL->value, 'deck_type' => DeckType::EXTRA->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// throws even with loose validation
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Card) cannot be in the Extra Deck.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->hasAttached(
			Card::factory(state: ['type' => CardType::TRAP->value, 'deck_type' => DeckType::EXTRA->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// throws even with loose validation
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Card) cannot be in the Extra Deck.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::EXTRA->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));
	}

	#[Test]
	public function only_monster_cards_may_be_a_deck_master(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::SPELL->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// throws even with loose validation
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Card) cannot be a Deck Master.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::TRAP->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// throws even with loose validation
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'Card) cannot be a Deck Master.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));
	}

	#[Test]
	public function a_normal_monster_must_be_at_least_level_5_to_be_a_deck_master(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::NORMAL, 'level' => 4])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// throws even with loose validation
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'cannot be a Deck Master.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::TRAP->value, 'deck_type' => DeckType::NORMAL, 'level' => null])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		// throws even with loose validation
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'cannot be a Deck Master.');

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::NORMAL, 'level' => 5])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL, 'level' => 1])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));

		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::EXTRA, 'level' => 1])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));
	}

	#[Test, TestDox('a deck may not contain more copies of a card than the card\'s limit')]
	public function a_deck_may_not_contain_more_copies_of_a_card_than_the_cards_limit(): void {
		$deck = Deck::factory()->create();
		$card = Card::factory()->create(['limit' => 1, 'deck_type' => DeckType::NORMAL->value]);
		$main = Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(57),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 2])
		)->for($deck)->create();

		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();
		DB::table($main->cards()->getTable())->insert([
			['card_id' => $card->id, 'category_id' => $main->id, 'order' => 0],
			['card_id' => $card->id, 'category_id' => $main->id, 'order' => 1]
		]);

		$deck->load('categories.cards');

		// throws even with loose validation
		$service = new DeckService($deck, $deck->categories->toArray(), false);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'You cannot have more than ' . $card->limit . ' copies of "' . $card->name . '".');

		$deck = Deck::factory()->create();
		$card = Card::factory()->create(['limit' => 3, 'deck_type' => DeckType::NORMAL->value]);
		$main = Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(57),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 2])
		)->for($deck)->create();

		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();
		DB::table($main->cards()->getTable())->insert([
			['card_id' => $card->id, 'category_id' => $main->id, 'order' => 0],
			['card_id' => $card->id, 'category_id' => $main->id, 'order' => 1]
		]);

		// valid with higher limit
		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));
	}

	#[Test, TestDox('a deck\'s cards must have matching tags with the deck master')]
	public function a_decks_cards_must_have_matching_tags_with_the_deck_master(): void {
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->has(
				Tag::factory()->count(1)
			)->count(1),
			['order' => 0]
		)->for($deck)->create();

		$main = Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(58),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 1])
		)->for($deck)->create();
		$main->cards()->attach(Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->has(Tag::factory()->count(1))->create(), ['order' => 0]);

		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		$service = new DeckService($deck, $deck->categories->toArray(), true);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'is not compatible with your Deck Master');
		// does not happen with loose validation.
		$this->assertTrue(DeckService::isDeckValid($deck, false));

		// deck master with no tags is valid with any card.
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();

		$main = Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(58),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 1])
		)->for($deck)->create();
		$main->cards()->attach(Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->has(Tag::factory()->count(1))->create(), ['order' => 0]);

		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		$this->assertTrue(DeckService::isDeckValid($deck, true));

		// cards with no tags are valid with any deck master.
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->has(
				Tag::factory()->count(1)
			)->count(1),
			['order' => 0]
		)->for($deck)->create();

		Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
			new Sequence(fn($sequence) => ['order' => $sequence->index])
		)->for($deck)->create();

		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));

		// if cards have the same at least one of the same tags then they are valid.
		$deck = Deck::factory()->create();
		$tag = Tag::factory()->create();
		$dm = Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->has(
			Tag::factory()->count(random_int(2, 10))
		)->create();
		$dm->tags()->attach($tag);

		$dm_category = Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->for($deck)->create();
		$dm_category->cards()->attach($dm, ['order' => 0]);

		$main = Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(58),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 1])
		)->for($deck)->create();

		$test_card = Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->has(Tag::factory()->count(random_int(2, 10)))->create();
		$test_card->tags()->attach($tag);
		$main->cards()->attach($test_card, ['order' => 0]);

		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));
	}

	#[Test]
	public function a_deck_may_only_have_one_legendary_card_per_card_type(): void {
		// monsters
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();

		$main = Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(57),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 2])
		)->for($deck)->create();

		$main->cards()->attach(Card::factory(state: [
			'deck_type' => DeckType::NORMAL,
			'type' => CardType::MONSTER->value,
			'legendary' => true
		])->create(), ['order' => 0]);
		$main->cards()->attach(Card::factory(state: [
			'deck_type' => DeckType::NORMAL,
			'type' => CardType::MONSTER->value,
			'legendary' => true
		])->create(), ['order' => 1]);

		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		$service = new DeckService($deck, $deck->categories->toArray(), true);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'You cannot have more than one Legendary ' . CardType::MONSTER->value . ' in your deck.');
		// does not happen with loose validation.
		$this->assertTrue(DeckService::isDeckValid($deck, false));

		// spells
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();

		$main = Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(57),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 2])
		)->for($deck)->create();

		$main->cards()->attach(Card::factory(state: [
			'deck_type' => DeckType::NORMAL,
			'type' => CardType::SPELL->value,
			'legendary' => true
		])->create(), ['order' => 0]);
		$main->cards()->attach(Card::factory(state: [
			'deck_type' => DeckType::NORMAL,
			'type' => CardType::SPELL->value,
			'legendary' => true
		])->create(), ['order' => 1]);

		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		$service = new DeckService($deck, $deck->categories->toArray(), true);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'You cannot have more than one Legendary ' . CardType::SPELL->value . ' in your deck.');
		// does not happen with loose validation.
		$this->assertTrue(DeckService::isDeckValid($deck, false));

		// traps
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();

		$main = Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(57),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 2])
		)->for($deck)->create();

		$main->cards()->attach(Card::factory(state: [
			'deck_type' => DeckType::NORMAL,
			'type' => CardType::TRAP->value,
			'legendary' => true
		])->create(), ['order' => 0]);
		$main->cards()->attach(Card::factory(state: [
			'deck_type' => DeckType::NORMAL,
			'type' => CardType::TRAP->value,
			'legendary' => true
		])->create(), ['order' => 1]);

		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');

		$service = new DeckService($deck, $deck->categories->toArray(), true);
		$this->expectToThrow($service->validateDeck(...), ValidationException::class, 'You cannot have more than one Legendary ' . CardType::TRAP->value . ' in your deck.');
		// does not happen with loose validation.
		$this->assertTrue(DeckService::isDeckValid($deck, false));

		// can have one of each legendary card type
		$deck = Deck::factory()->create();
		Category::factory(state: ['type' => CategoryType::DECK_MASTER->value, 'order' => 0])->hasAttached(
			Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL->value])->count(1),
			['order' => 0]
		)->for($deck)->create();

		$main = Category::factory(state: ['type' => CategoryType::MAIN->value, 'order' => 1])->hasAttached(
			Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(56),
			new Sequence(fn($sequence) => ['order' => $sequence->index + 3])
		)->for($deck)->create();

		$main->cards()->attach(Card::factory(state: [
			'deck_type' => DeckType::NORMAL,
			'type' => CardType::MONSTER->value,
			'legendary' => true
		])->create(), ['order' => 0]);
		$main->cards()->attach(Card::factory(state: [
			'deck_type' => DeckType::NORMAL,
			'type' => CardType::SPELL->value,
			'legendary' => true
		])->create(), ['order' => 1]);
		$main->cards()->attach(Card::factory(state: [
			'deck_type' => DeckType::NORMAL,
			'type' => CardType::TRAP->value,
			'legendary' => true
		])->create(), ['order' => 1]);

		Category::factory(state: ['type' => CategoryType::EXTRA->value, 'order' => 2])->for($deck)->create();
		Category::factory(state: ['type' => CategoryType::SIDE->value, 'order' => 3])->for($deck)->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));
	}

	#[Test]
	public function trying_to_sync_a_deck_without_validating_fails(): void {
		$deck = Deck::factory()->create();
		$service = new DeckService($deck, [], false);
		$this->expectToThrow($service->syncDeckFromCategories(...), ValidationException::class, 'Invalid deck detected.');
	}

	#[Test]
	public function syncDeck_creates_a_valid_deck(): void {
		$deck = Deck::factory()->create();
		Category::factory()->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached(
				Card::factory()->state([
					'type' => CardType::MONSTER->value,
					'deck_type' => DeckType::RITUAL
				])->count(1),
				['order' => 0]
			)
			->for($deck)
			->create();

		Category::factory()->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory()->state(['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));

		$new_deck = Deck::factory()->create();

		// Expect no exceptions here.
		DeckService::syncDeck($new_deck, $deck->categories->toArray());

		$new_deck->load('categories.cards');
		$this->assertEquals(4, $new_deck->categories->count());
		$this->assertTrue(DeckService::isDeckValid($new_deck, true));
		$this->assertDatabaseHas('decks', ['id' => $new_deck->id]);
		$this->assertDatabaseHas('categories', ['id' => $new_deck->categories->first()->id, 'name' => $deck->categories->first()->name]);
	}

	#[Test]
	public function syncDeck_throws_ValidationException_when_deck_is_invalid(): void {
		$deck = Deck::factory()->create();
		Category::factory()->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached(
				Card::factory()->state([
					'type' => CardType::MONSTER->value,
					'deck_type' => DeckType::RITUAL
				])->count(1),
				['order' => 0]
			)
			->for($deck)
			->create();

		Category::factory()->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory()->state(['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));

		$new_categories = $deck->categories->toArray();
		unset($new_categories[0]);

		$this->expectToThrow(fn() => DeckService::syncDeck($deck, $new_categories), ValidationException::class);
	}

	#[Test]
	public function syncDeck_adds_new_card_when_categories_contains_a_new_card(): void {
		$deck = Deck::factory()->create();
		Category::factory()->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached(
				Card::factory()->state([
					'type' => CardType::MONSTER->value,
					'deck_type' => DeckType::RITUAL
				])->count(1),
				['order' => 0]
			)
			->for($deck)
			->create();

		Category::factory()->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory()->state(['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));

		// add a card to the side deck
		$card = Card::factory()->create();
		$new_categories = $deck->categories->toArray();
		$new_categories[count($new_categories) - 1]['cards'][] = ['id' => $card->id, 'alternate' => null];
		DeckService::syncDeck($deck, $new_categories);

		$this->assertDatabaseHas($card->categories()->getTable(), [
			'card_id' => $card->id,
			'category_id' => $deck->categories()->where('type', CategoryType::SIDE->value)->value('id'),
			'order' => 0
		]);
	}

	#[Test, TestDox('syncDeck removes card when it\'s no longer in its category')]
	public function syncDeck_removes_card_when_its_no_longer_in_its_categort(): void {
		$deck = Deck::factory()->create();
		Category::factory()->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached(
				Card::factory()->state([
					'type' => CardType::MONSTER->value,
					'deck_type' => DeckType::RITUAL
				])->count(1),
				['order' => 0]
			)
			->for($deck)
			->create();

		Category::factory()->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory()->state(['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		$side = Category::factory()->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();
		$side->cards()->attach(Card::factory()->create(), ['order' => 0]);

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));
		$this->assertDatabaseHas($side->cards()->getTable(), [
			'category_id' => $side->id,
			'order' => 0
		]);

		// remove card from the side deck
		$new_categories = $deck->categories->toArray();
		$new_categories[count($new_categories) - 1]['cards'] = [];
		DeckService::syncDeck($deck, $new_categories);

		$this->assertDatabaseMissing($side->cards()->getTable(), [
			'category_id' => $deck->categories()->where('type', CategoryType::SIDE->value)->value('id'),
		]);
	}

	#[Test]
	public function syncDeck_adds_a_category_to_a_deck(): void {
		$deck = Deck::factory()->create();
		Category::factory()->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached(
				Card::factory()->state([
					'type' => CardType::MONSTER->value,
					'deck_type' => DeckType::RITUAL
				])->count(1),
				['order' => 0]
			)
			->for($deck)
			->create();

		Category::factory()->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory()->state(['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));

		// add a new category to the deck
		$new_categories = $deck->categories->toArray();
		$new_categories[count($new_categories) - 2]['order']++;
		$new_categories[count($new_categories) - 1]['order']++;
		$new_categories[] = [
			'id' => fake()->uuid(),
			'name' => fake()->words(asText: true),
			'type' => CategoryType::MAIN->value,
			'order' => 2,
			'cards' => []
		];

		DeckService::syncDeck($deck, $new_categories);
		$this->assertEquals(5, $deck->categories()->count());
	}

	#[Test]
	public function syncDeck_removes_a_category_from_a_deck(): void {
		$deck = Deck::factory()->create();
		Category::factory()->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached(
				Card::factory()->state([
					'type' => CardType::MONSTER->value,
					'deck_type' => DeckType::RITUAL
				])->count(1),
				['order' => 0]
			)
			->for($deck)
			->create();

		Category::factory()->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory()->state(['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::EXTRA->value, 'order' => 3])
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::SIDE->value, 'order' => 4])
			->for($deck)
			->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));

		// add a new category to the deck
		$new_categories = $deck->categories->toArray();
		$new_categories[count($new_categories) - 2]['order']--;
		$new_categories[count($new_categories) - 1]['order']--;
		unset($new_categories[count($new_categories) - 3]);

		DeckService::syncDeck($deck, $new_categories);
		$this->assertEquals(4, $deck->categories()->count());
	}

	#[Test]
	public function syncDeck_works_with_different_types_of_card_formats_in_categories(): void {
		$deck = Deck::factory()->create();
		Category::factory()->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached(
				Card::factory()->state([
					'type' => CardType::MONSTER->value,
					'deck_type' => DeckType::RITUAL
				])->count(1),
				['order' => 0]
			)
			->for($deck)
			->create();

		Category::factory()->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory()->state(['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$deck->load('categories.cards');
		$this->assertTrue(DeckService::isDeckValid($deck, true));

		// add a card to the side deck
		$card1 = Card::factory()->create();
		$card2 = Card::factory()->create();
		$alt1 = CardAlternate::factory()->for($card2)->create();
		$card3 = Card::factory()->create();
		$alt2 = CardAlternate::factory()->for($card3)->create();
		$card4 = Card::factory()->create();
		$alt3 = CardAlternate::factory()->for($card4)->create();
		$alt4 = CardAlternate::factory()->for($card4)->create();
		$alt5 = CardAlternate::factory()->for($card4)->create();

		// we created a new deck with 60 cards in a fresh seed
		$this->assertDatabaseCount('card_category', 60);
		$new_categories = $deck->categories->toArray();
		$new_categories[count($new_categories) - 1]['cards'][] = ['id' => $card1->id, 'alternate' => null];
		$new_categories[count($new_categories) - 1]['cards'][] = ['id' => $card2->id, 'alternate' => $alt1->id];
		$new_categories[count($new_categories) - 1]['cards'][] = ['id' => $card3->id, 'alternate' => ['id' => $alt2->id]];
		$new_categories[count($new_categories) - 1]['cards'][] = [
			'id' => $card4->id,
			'pivot' => ['card_alternate_id' => $alt4->id],
			'alternates' => [
				['id' => $alt3->id],
				['id' => $alt4->id],
				['id' => $alt5->id]
			]
		];

		DeckService::syncDeck($deck, $new_categories);

		// we added 4 copies of our new card with different formats of alternate.
		$this->assertDatabaseCount('card_category', 64);
	}

	#[Test]
	public function encodeDeckToYGOPro_exports_a_valid_deck_correctly(): void {
		$deck = Deck::factory()->create();

		$dm = Card::factory()->create([
			'type' => CardType::MONSTER->value,
			'deck_type' => DeckType::RITUAL->value,
		]);

		Category::factory()
			->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached($dm, ['order' => 0])
			->for($deck)
			->create();

		Category::factory()
			->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		// Export deck to YGOPro format.
		$encoded = DeckService::exportDeckToYGOPro($deck);
		$this->assertIsString($encoded);
		$this->assertStringStartsWith('ydke://', $encoded);

		$payload = substr($encoded, 7, -1);
		$parts = explode('!', $payload);
		$this->assertEquals(3, count($parts));

		$main_bin = base64_decode($parts[0]);
		$this->assertEquals(60 * 4, strlen($main_bin));

		// first 4 bytes should be equivalent to the deck master's passcode.
		$bytes = current(unpack('V', substr($main_bin, 0, 4)));
		$this->assertEquals(intval($dm->passcode), $bytes);

		$extra_bin = base64_decode($parts[1]);
		$side_bin = base64_decode($parts[2]);
		$this->assertEquals('', $extra_bin);
		$this->assertEquals('', $side_bin);

		// test a full deck
		$deck = Deck::factory()->create();
		$dm = Card::factory()->create([
			'type' => CardType::MONSTER->value,
			'deck_type' => DeckType::RITUAL->value,
		]);

		Category::factory()
			->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached($dm, ['order' => 0])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->hasAttached(
				Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::EXTRA->value])->count(15),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->hasAttached(
				Card::factory()->count(15),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();

		$encoded = DeckService::exportDeckToYGOPro($deck);
		$this->assertIsString($encoded);
		$this->assertStringStartsWith('ydke://', $encoded);

		$payload = substr($encoded, 7, -1);
		$parts = explode('!', $payload);
		$this->assertEquals(3, count($parts));

		$categories = $deck->categories->toArray();

		$main_bin = base64_decode($parts[0]);
		$this->assertEquals(60 * 4, strlen($main_bin));

		$main_codes = array_values(unpack('V*', $main_bin));
		foreach ($main_codes as $key => $code) {
			if ($key === 0) {
				$this->assertEquals(intval($categories[0]['cards'][$key]['passcode']), $code);
			} else {
				$this->assertEquals(intval($categories[1]['cards'][$key - 1]['passcode']), $code);
			}
		}

		$extra_bin = base64_decode($parts[1]);
		$this->assertEquals(15 * 4, strlen($extra_bin));

		$extra_codes = array_values(unpack('V*', $extra_bin));
		foreach ($extra_codes as $key => $code) {
			$this->assertEquals(intval($categories[2]['cards'][$key]['passcode']), $code);
		}

		$side_bin = base64_decode($parts[2]);
		$this->assertEquals(15 * 4, strlen($side_bin));

		$side_codes = array_values(unpack('V*', $side_bin));
		foreach ($side_codes as $key => $code) {
			$this->assertEquals(intval($categories[3]['cards'][$key]['passcode']), $code);
		}
	}

	#[Test]
	public function encodeDeckToYGOPro_exports_a_valid_deck_with_alternates_correctly(): void {
		$deck = Deck::factory()->create();

		$dm = Card::factory()->has(CardAlternate::factory()->count(5), 'alternates')->create([
			'type' => CardType::MONSTER->value,
			'deck_type' => DeckType::RITUAL->value,
		]);

		$dm_alt = $dm->alternates->random();
		Category::factory()
			->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached($dm, ['order' => 0, 'card_alternate_id' => $dm_alt->id])
			->for($deck)
			->create();

		Category::factory()
			->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		// Export deck to YGOPro format.
		$encoded = DeckService::exportDeckToYGOPro($deck);
		$this->assertIsString($encoded);
		$this->assertStringStartsWith('ydke://', $encoded);

		$payload = substr($encoded, 7, -1);
		$parts = explode('!', $payload);
		$this->assertEquals(3, count($parts));

		$main_bin = base64_decode($parts[0]);
		$this->assertEquals(60 * 4, strlen($main_bin));

		// first 4 bytes should be equivalent to the deck master's card alternate passcode.
		$bytes = current(unpack('V', substr($main_bin, 0, 4)));
		$this->assertEquals(intval($dm_alt->passcode), $bytes);

		$extra_bin = base64_decode($parts[1]);
		$side_bin = base64_decode($parts[2]);
		$this->assertEquals('', $extra_bin);
		$this->assertEquals('', $side_bin);

		// test a full deck
		$deck = Deck::factory()->create();
		$dm = Card::factory()->create([
			'type' => CardType::MONSTER->value,
			'deck_type' => DeckType::RITUAL->value,
		]);

		Category::factory()
			->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached($dm, ['order' => 0])
			->for($deck)
			->create();
		$main = Category::factory()
			->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->has(CardAlternate::factory()->count(random_int(0, 10)), 'alternates')->count(59),
				new Sequence(fn($sequence, ) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();

		$main->load('cards.alternates');
		foreach ($main->cards as $card) {
			if ($card->alternates->isNotEmpty() && random_int(0, 1) === 1) {
				$card->pivot->card_alternate_id = $card->alternates->random()->id;
				$card->pivot->save();
			}
		}

		Category::factory()
			->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->hasAttached(
				Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::EXTRA->value])->count(15),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->hasAttached(
				Card::factory()->count(15),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();

		$encoded = DeckService::exportDeckToYGOPro($deck);
		$this->assertIsString($encoded);
		$this->assertStringStartsWith('ydke://', $encoded);

		$payload = substr($encoded, 7, -1);
		$parts = explode('!', $payload);
		$this->assertEquals(3, count($parts));

		$categories = $deck->categories->toArray();

		$main_bin = base64_decode($parts[0]);
		$this->assertEquals(60 * 4, strlen($main_bin));

		$main_codes = array_values(unpack('V*', $main_bin));
		foreach ($main_codes as $key => $code) {
			if ($key === 0) {
				$this->assertEquals(intval($categories[0]['cards'][$key]['passcode']), $code);
			} else {
				$card = $categories[1]['cards'][$key - 1];
				$actual_code = $card['passcode'];
				if (isset($card['pivot']) && !empty($card['pivot']['card_alternate_id'])) {
					$actual_code = array_find($card['alternates'], fn($alt) => $alt['id'] === $card['pivot']['card_alternate_id'])['passcode'] ?? $card['passcode'];
				}

				$this->assertEquals(intval($actual_code), $code);
			}
		}

		$extra_bin = base64_decode($parts[1]);
		$this->assertEquals(15 * 4, strlen($extra_bin));

		$extra_codes = array_values(unpack('V*', $extra_bin));
		foreach ($extra_codes as $key => $code) {
			$this->assertEquals(intval($categories[2]['cards'][$key]['passcode']), $code);
		}

		$side_bin = base64_decode($parts[2]);
		$this->assertEquals(15 * 4, strlen($side_bin));

		$side_codes = array_values(unpack('V*', $side_bin));
		foreach ($side_codes as $key => $code) {
			$this->assertEquals(intval($categories[3]['cards'][$key]['passcode']), $code);
		}
	}

	#[Test]
	public function card_to_passcode_returns_default_when_card_has_no_alternate(): void {
		$card = Card::factory()->create();
		$this->assertEquals($card->passcode, DeckService::cardToPasscode($card));

		$card = Card::factory()->has(CardAlternate::factory()->count(10), 'alternates')->create();
		$this->assertEquals($card->passcode, DeckService::cardToPasscode($card));

		$category = Category::factory()->hasAttached($card, ['order' => 0, 'card_alternate_id' => CardAlternate::factory()->create()->id])->create();
		$category->load('cards.alternates');
		$this->assertEquals($card->passcode, DeckService::cardToPasscode($category->cards->first()));
	}

	#[Test]
	public function card_to_passcode_returns_default_when_card_is_errata(): void {
		$card = Card::factory(state: ['is_errata' => true])->create();
		$alt = CardAlternate::factory()->for($card)->create();
		$category = Category::factory()->hasAttached($card, ['order' => 0, 'card_alternate_id' => $alt->id])->create();
		$category->load('cards.alternates');

		$this->assertEquals($card->passcode, DeckService::cardToPasscode($category->cards->first()));
	}

	#[Test]
	public function card_to_passcode_returns_alternate_passcode_when_card_has_alternate(): void {
		$card = Card::factory()->create();
		$alt = CardAlternate::factory()->for($card)->create();
		$category = Category::factory()->hasAttached($card, ['order' => 0, 'card_alternate_id' => $alt->id])->create();
		$category->load('cards.alternates');

		$this->assertEquals($alt->passcode, DeckService::cardToPasscode($category->cards->first()));

		// Even when it's empty.
		$card2 = Card::factory()->create();
		$alt2 = CardAlternate::factory(state: ['passcode' => ''])->for($card2)->create();
		$category2 = Category::factory()->hasAttached($card2, ['order' => 0, 'card_alternate_id' => $alt2->id])->create();
		$category2->load('cards.alternates');
		$this->assertEquals($alt2->passcode, DeckService::cardToPasscode($category2->cards->first()));
	}

	#[Test]
	public function encodeDeckToYGOPro_throws_exception_on_invalid_deck(): void {
		$deck = Deck::factory()->create();
		Category::factory()
			->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached(Card::factory(state: ['type' => CardType::MONSTER->value, 'deck_type' => DeckType::RITUAL]), ['order' => 0])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$deck->load('categories.cards');

		$this->expectToThrow(fn() => DeckService::exportDeckToYGOPro($deck), ValidationException::class, 'Only valid decks are eligible for export.');
	}

	#[Test]
	public function a_deck_with_loaded_content_should_make_no_calls(): void {
		$deck = Deck::factory()->create();

		$dm = Card::factory()->create([
			'type' => CardType::MONSTER->value,
			'deck_type' => DeckType::RITUAL->value,
		]);

		Category::factory()
			->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached($dm, ['order' => 0])
			->for($deck)
			->create();

		Category::factory()
			->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$deck->load('categories.cards.tags');

		$this->expectsDatabaseQueryCount(0);
		DeckService::isDeckValid($deck);
	}

	#[Test]
	public function a_deck_with_no_tags_should_load_tags(): void {
		$deck = Deck::factory()->create();

		$dm = Card::factory()->create([
			'type' => CardType::MONSTER->value,
			'deck_type' => DeckType::RITUAL->value,
		]);

		Category::factory()
			->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached($dm, ['order' => 0])
			->for($deck)
			->create();

		Category::factory()
			->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$deck->load('categories.cards');

		$this->expectsDatabaseQueryCount(1);
		DeckService::isDeckValid($deck);
	}

	#[Test]
	public function a_deck_with_no_cards_should_load_cards_and_tags(): void {
		$deck = Deck::factory()->create();

		$dm = Card::factory()->create([
			'type' => CardType::MONSTER->value,
			'deck_type' => DeckType::RITUAL->value,
		]);

		Category::factory()
			->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached($dm, ['order' => 0])
			->for($deck)
			->create();

		Category::factory()
			->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$deck->load('categories');

		$this->expectsDatabaseQueryCount(2);
		DeckService::isDeckValid($deck);
	}

	#[Test]
	public function a_deck_with_no_categories_should_load_categories_cards_and_tags(): void {
		$deck = Deck::factory()->create();

		$dm = Card::factory()->create([
			'type' => CardType::MONSTER->value,
			'deck_type' => DeckType::RITUAL->value,
		]);

		Category::factory()
			->state(['type' => CategoryType::DECK_MASTER->value, 'order' => 0])
			->hasAttached($dm, ['order' => 0])
			->for($deck)
			->create();

		Category::factory()
			->state(['type' => CategoryType::MAIN->value, 'order' => 1])
			->hasAttached(
				Card::factory(state: ['deck_type' => DeckType::NORMAL->value])->count(59),
				new Sequence(fn($sequence) => ['order' => $sequence->index])
			)
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::EXTRA->value, 'order' => 2])
			->for($deck)
			->create();
		Category::factory()
			->state(['type' => CategoryType::SIDE->value, 'order' => 3])
			->for($deck)
			->create();

		$this->expectsDatabaseQueryCount(3);
		DeckService::isDeckValid($deck);
	}

	#[Test]
	public function alternatesToCards_skips_empty_categories(): void {
		$this->assertEmpty(DeckService::alternatesToCards([]));

		$deck = [['cards' => []]];
		$converted = DeckService::alternatesToCards($deck);
		$this->assertCount(1, $converted);
		$this->assertEmpty($converted[0]['cards']);

		$deck = [['cards' => []], ['cards' => [123]]];
		$converted = DeckService::alternatesToCards($deck);
		$this->assertCount(2, $converted);
		$this->assertEmpty($converted[0]['cards']);
		$this->assertNotEmpty($converted[1]['cards']);
		$this->assertNull($converted[1]['cards'][0]['id']);
		$this->assertNull($converted[1]['cards'][0]['alternate']);
	}

	#[Test]
	public function alternatesToCards_converts_card_alternate_ids_to_card_ids(): void {
		$this->assertEmpty(DeckService::alternatesToCards([]));

		$card = Card::factory()->has(CardAlternate::factory()->count(1), 'alternates')->create();
		$alt = CardAlternate::factory(state: ['passcode' => $card->passcode])->for($card)->create();
		$alt2 = CardAlternate::factory()->for($card)->create();
		$alt3 = CardAlternate::factory()->for($card)->create();

		$deck = [['cards' => []], ['cards' => [$alt->id, $alt2->id, $alt3->id]]];
		$converted = DeckService::alternatesToCards($deck);
		$this->assertCount(2, $converted);
		$this->assertEmpty($converted[0]['cards']);
		$this->assertNotEmpty($converted[1]['cards']);

		$this->assertCount(3, $converted[1]['cards']);
		foreach ($converted[1]['cards'] as $c) {
			$this->assertEquals($card->id, $c['id']);
			$this->assertNull($card['alternate']);
		}
	}
}
