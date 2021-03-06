<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Action;
use App\Models\ActionStat;
use App\Models\Campaign;
use App\Models\Club;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\Signup;
use App\Models\User;
use App\Types\ActionType;
use App\Types\Cause;
use App\Types\PostType;
use App\Types\TimeCommitment;
use Faker\Generator;
use Illuminate\Support\Str;

/*
 * Here you may define all of your model factories. Model factories give
 * you a convenient way to create models for testing and seeding your
 * database. Just tell the factory how a default model should look.
 *
 * @var Illuminate\Database\Eloquent\Factory $factory
 * @see https://laravel.com/docs/5.5/database-testing#writing-factories
 */

// Action Factory
$factory->define(Action::class, function (Generator $faker) {
    return [
        'name' => Str::title($this->faker->unique()->words(3, true)),
        'campaign_id' => factory(Campaign::class)->create()->id,
        'post_type' => 'photo',
        'action_type' => $this->faker->randomElement(ActionType::all()),
        'time_commitment' => $this->faker->randomElement(TimeCommitment::all()),
        'reportback' => true,
        'civic_action' => true,
        'scholarship_entry' => true,
        'anonymous' => false,
        'noun' => 'things',
        'verb' => 'done',
        'collect_school_id' => true,
        'volunteer_credit' => false,
    ];
});

$factory->state(Action::class, 'voter-registration', [
    'action_type' => ActionType::ATTEND_EVENT(),
    'name' => 'VR-' . $this->faker->unique()->year . ' Voter Registrations',
    'noun' => 'registrations',
    'post_type' => PostType::VOTER_REG(),
    'verb' => 'completed',
]);

// Post Factory
$factory->define(Post::class, function (Generator $faker) {
    $faker->addProvider(new FakerNorthstarId($faker));
    $faker->addProvider(new FakerPostUrl($faker));
    $faker->addProvider(new FakerSchoolId($faker));

    return [
        'campaign_id' => function () {
            return factory(Campaign::class)->create()->id;
        },
        'signup_id' => function (array $attributes) {
            // If a 'signup_id' is not provided, create one for the same Campaign & Northstar ID.
            return factory(Signup::class)->create([
                'campaign_id' => $attributes['campaign_id'],
                'northstar_id' => $attributes['northstar_id'],
            ])->id;
        },
        'action_id' => function (array $attributes) {
            return factory(Action::class)->create([
                'campaign_id' => $attributes['campaign_id'],
            ])->id;
        },
        'northstar_id' => $this->faker->northstar_id,
        'text' => $faker->sentence(),
        'location' => 'US-' . $faker->stateAbbr(),
        'source' => 'phpunit',
    ];
});

/*
 * Post type factory states.
 */
$factory->state(Post::class, 'photo', function (Generator $faker) {
    return [
        'type' => 'photo',
        'quantity' => $faker->randomNumber(2),
        'url' => $faker->post_url,
    ];
});

$factory->state(Post::class, 'text', [
    'type' => 'text',
]);

$factory->state(Post::class, 'voter-reg', [
    'type' => 'voter-reg',
]);

/*
 * Post status factory states.
 */
$factory->state(Post::class, 'accepted', [
    'status' => 'accepted',
]);

$factory->state(Post::class, 'pending', [
    'status' => 'pending',
]);

$factory->state(Post::class, 'rejected', [
    'status' => 'rejected',
]);

$factory->state(Post::class, 'step-1', [
    'status' => 'step-1',
]);

$factory->state(Post::class, 'register-form', [
    'status' => 'register-form',
]);

$factory->state(Post::class, 'register-OVR', [
    'status' => 'register-OVR',
]);

// Signup Factory
$factory->define(Signup::class, function (Generator $faker) {
    $faker->addProvider(new FakerNorthstarId($faker));

    return [
        'northstar_id' => $faker->northstar_id,
        'campaign_id' => function () {
            return factory(Campaign::class)->create()->id;
        },
        'why_participated' => $faker->sentence(),
        'source' => 'phpunit',
        'details' => $faker->randomElement([
            null,
            'fun-affiliate-stuff',
            'i-say-the-tails',
        ]),
    ];
});

// Reaction Factory
$factory->define(Reaction::class, function (Generator $faker) {
    $faker->addProvider(new FakerNorthstarId($faker));

    return [
        'northstar_id' => $faker->northstar_id,
        'post_id' => $faker->randomNumber(7),
    ];
});

// Base User Factory
$factory->define(User::class, function (Generator $faker) {
    $faker->addProvider(new FakerNorthstarId($faker));

    return [
        'northstar_id' => $faker->northstar_id,
        'access_token' => Str::random(1024),
        'access_token_expiration' => Str::random(11),
        'refresh_token' => Str::random(1024),
        'remember_token' => Str::random(10),
        'role' => 'user',
    ];
});

$factory->defineAs(User::class, 'admin', function () use ($factory) {
    return array_merge($factory->raw(User::class), ['role' => 'admin']);
});

$factory->defineAs(User::class, 'staff', function () use ($factory) {
    return array_merge($factory->raw(User::class), ['role' => 'staff']);
});

// Campaign Factory
$factory->define(Campaign::class, function (Generator $faker) {
    return [
        'internal_title' => Str::title($faker->unique()->catchPhrase),
        'cause' => $faker->randomElements(Cause::all(), rand(1, 5)),
        'impact_doc' => 'https://www.google.com/',
        // By default, we create an "open" campaign.
        'start_date' => $faker
            ->dateTimeBetween('-6 months', 'now')
            ->setTime(0, 0),
        'end_date' => $faker
            ->dateTimeBetween('+1 months', '+6 months')
            ->setTime(0, 0),
    ];
});

$factory->defineAs(Campaign::class, 'closed', function (Generator $faker) use (
    $factory
) {
    return array_merge($factory->raw(Campaign::class), [
        'start_date' => $faker
            ->dateTimeBetween('-12 months', '-6 months')
            ->setTime(0, 0),
        'end_date' => $faker
            ->dateTimeBetween('-3 months', 'yesterday')
            ->setTime(0, 0),
    ]);
});

$factory->state(Campaign::class, 'voter-registration', function (
    Generator $faker
) {
    return [
        'cause' => [Cause::VOTER_REGISTRATION()],
        'internal_title' =>
            'Voter Registration ' . Str::title($faker->unique()->catchPhrase),
    ];
});

// Group Type Factory
$factory->define(GroupType::class, function (Generator $faker) {
    return [
        'name' =>
            'National ' . Str::title($faker->unique()->jobTitle) . ' Society',
        'filter_by_location' => true,
    ];
});

// Group Factory
$factory->define(Group::class, function (Generator $faker) {
    $faker->addProvider(new FakerSchoolId($faker));

    return [
        'group_type_id' => function () {
            return factory(GroupType::class)->create()->id;
        },
        'name' => Str::title($faker->unique()->company),
        'city' => $faker->city,
        'location' => 'US-' . $faker->stateAbbr,
    ];
});

$factory->state(Group::class, 'school', function (Generator $faker) {
    $school = $faker->school;

    return [
        'city' => $school->city,
        'location' => $school->location,
        'school_id' => $school->school_id,
    ];
});

// Club Factory
$factory->define(Club::class, function (Generator $faker) {
    return [
        'leader_id' => $this->faker->unique()->northstar_id,
        'name' => Str::title($faker->company),
        'city' => $faker->city,
        'location' => 'US-' . $faker->stateAbbr,
        'school_id' => $faker->school->school_id,
    ];
});

// ActionStat Factory
$factory->define(ActionStat::class, function (Generator $faker) {
    $school = $faker->unique()->school;

    return [
        'school_id' => $school->school_id,
        'location' => $school->location,
        'action_id' => function () {
            return factory(Action::class)->create()->id;
        },
        'impact' => $faker->randomNumber(2),
    ];
});
