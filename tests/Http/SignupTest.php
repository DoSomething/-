<?php

namespace Tests\Http;

use Tests\TestCase;
use Rogue\Models\Post;
use Rogue\Models\User;
use Rogue\Models\Group;
use Rogue\Models\Signup;
use DoSomething\Gateway\Blink;

class SignupTest extends TestCase
{
    /**
     * Test that a POST request to /signups creates a new signup.
     *
     * POST /api/v3/signups
     * @return void
     */
    public function testCreatingASignup()
    {
        $northstarId = $this->faker->northstar_id;
        $campaignId = $this->faker->randomNumber(4);
        $referrerUserId = $this->faker->northstar_id;

        // Mock the Blink API call.
        $this->mock(Blink::class)->shouldReceive('userSignup');

        $response = $this->withAccessToken($northstarId)->postJson('api/v3/signups', [
            'campaign_id' => $campaignId,
            'details' => 'affiliate-messaging',
            'referrer_user_id' => $referrerUserId,
        ]);

        // Make sure we get the 201 Created response
        $response->assertStatus(201);
        $response->assertJson([
            'data' => [
                'northstar_id' => $northstarId,
                'campaign_id' => $campaignId,
                'quantity' => null,
                'source' => 'phpunit',
                'why_participated' => null,
                'referrer_user_id' => $referrerUserId,
                'group_id' => null,
            ],
        ]);

        // Make sure the signup is persisted.
        $this->assertDatabaseHas('signups', [
            'northstar_id' => $northstarId,
            'campaign_id' => $campaignId,
            'quantity' => null,
            'details' => 'affiliate-messaging',
        ]);
    }

    /**
     * Test that a POST request to /signups creates a new signup with group_id if passed.
     *
     * POST /api/v3/signups
     * @return void
     */
    public function testCreatingASignupWithGroupId()
    {
        $northstarId = $this->faker->northstar_id;
        $campaignId = $this->faker->randomNumber(4);
        $group = factory(Group::class)->create();

        $this->mock(Blink::class)->shouldReceive('userSignup');

        $response = $this->withAccessToken($northstarId)->postJson('api/v3/signups', [
            'campaign_id' => $campaignId,
            'group_id' => $group->id,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'data' => [
                'northstar_id' => $northstarId,
                'campaign_id' => $campaignId,
                'group_id' => $group->id,
            ],
        ]);
    }

    /**
     * Test that a POST request to /signups doesn't create a new signup without activity scope.
     *
     * POST /api/v3/signups
     * @return void
     */
    public function testCreatingASignupWithoutActivityScope()
    {
        $northstarId = $this->faker->northstar_id;
        $campaignId = str_random(22);

        // Mock the Blink API call.
        $this->mock(Blink::class)->shouldReceive('userSignup');

        $response = $this->postJson('api/v3/signups', [
            'campaign_id' => $campaignId,
            'details' => 'affiliate-messaging',
        ]);

        // Make sure we get the 401 Unauthenticated response
        $response->assertStatus(401);
        $this->assertEquals('Unauthenticated.', $response->decodeResponseJson()['message']);
    }

    /**
     * Test that a POST request to /signups doesn't create duplicate signups.
     *
     * POST /api/v3/signups
     * @return void
     */
    public function testNotCreatingDuplicateSignups()
    {
        $signup = factory(Signup::class)->create();

        // Mock the Blink API call.
        $this->mock(Blink::class)->shouldReceive('userSignup');

        $response = $this->withAccessToken($signup->northstar_id)->postJson('api/v3/signups', [
            'northstar_id' => $signup->northstar_id,
            'campaign_id' => $signup->campaign_id,
            'source' => 'the-fox-den',
            'details' => 'affiliate-messaging',
        ]);

        // Make sure we get the 200 response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'campaign_id' => $signup->campaign_id,
                'quantity' => $signup->quantity,
            ],
        ]);
    }

    /**
     * Test that non-authenticated user's/apps can't post signups.
     *
     * @return void
     */
    public function testUnauthenticatedUserCreatingASignup()
    {
        $response = $this->postJson('api/v3/signups', [
            'northstar_id'     => $this->faker->northstar_id,
            'campaign_id'      => $this->faker->randomNumber(4),
            'source'           => 'the-fox-den',
            'details'          => 'affiliate-messaging',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test for retrieving all signups as non-admin and non-owner.
     * Non-admins/non-owners should not see why_participated, source, or details in response.
     *
     * GET /api/v3/signups
     * @return void
     */
    public function testSignupsIndexAsNonAdminNonOwner()
    {
        factory(Signup::class, 10)->create();

        $response = $this->getJson('api/v3/signups');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'northstar_id',
                    'campaign_id',
                    'quantity',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'cursor' => [
                    'current',
                    'prev',
                    'next',
                    'count',
                ],
            ],
        ]);
    }

    /**
     * Test for retrieving all signups as admin.
     * Admins should see why_participated, source, and details in response.
     *
     * GET /api/v3/signups
     * @return void
     */
    public function testSignupsIndexAsAdmin()
    {
        factory(Signup::class, 10)->create();

        $response = $this->withAdminAccessToken()->getJson('api/v3/signups');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'northstar_id',
                    'campaign_id',
                    'quantity',
                    'created_at',
                    'updated_at',
                    'why_participated',
                    'source',
                    'details',
                ],
            ],
            'meta' => [
                'cursor' => [
                    'current',
                    'prev',
                    'next',
                    'count',
                ],
            ],
        ]);
    }

    /**
     * Test for retrieving all signups as owner.
     * Signup owner should see why_participated, source, and details in response.
     *
     * GET /api/v3/signups
     * @return void
     */
    public function testSignupsIndexAsOwner()
    {
        $northstarId = $this->faker->northstar_id;
        $signups = factory(Signup::class, 10)->create([
            'northstar_id' => $northstarId,
        ]);

        $response = $this->withAccessToken($northstarId)->getJson('api/v3/signups');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'northstar_id',
                    'campaign_id',
                    'quantity',
                    'created_at',
                    'updated_at',
                    'why_participated',
                    'source',
                    'details',
                ],
            ],
            'meta' => [
                'cursor' => [
                    'current',
                    'prev',
                    'next',
                    'count',
                ],
            ],
        ]);
    }

    /**
     * Test for signup index with included pending post info. as non-admin/non-owner.
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups?include=posts
     * @return void
     */
    public function testSignupIndexWithIncludedPostsAsNonAdminNonOwner()
    {
        $post = factory(Post::class)->create();
        $signup = $post->signup;

        // Test with annoymous user that no posts are returned.
        $response = $this->getJson('api/v3/signups' . '?include=posts');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(true, empty($decodedResponse['data'][0]['posts']['data']));
    }

    /**
     * Test for signup index with included pending post info. as admin
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups?include=posts
     * @return void
     */
    public function testSignupIndexWithIncludedPostsAsAdmin()
    {
        $post = factory(Post::class)->create();
        $signup = $post->signup;

        // Test with admin that posts are returned.
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?include=posts');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(false, empty($decodedResponse['data'][0]['posts']['data']));
    }

    /**
     * Test for signup index with included pending post info. as owner
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups?include=posts
     * @return void
     */
    public function testSignupIndexWithIncludedPostsAsOwner()
    {
        $post = factory(Post::class)->create();
        $signup = $post->signup;

        // Test with owner that posts are returned.
        $response = $this->withAccessToken($post->northstar_id)->getJson('api/v3/signups?include=posts');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(false, empty($decodedResponse['data'][0]['posts']['data']));
    }

    /**
     * Test for signup index with included post info. and include params as non-admin/non-owner.
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups?include=posts:type(text|photo)
     * @return void
     */
    public function testSignupIndexWithIncludedPostsAndParamsAsNonAdminNonOwner()
    {
        $signup = factory(Signup::class)->create();

        // Create a voter-reg post that is accepted.
        $post = factory(Post::class)->create();
        $post->type = 'voter-reg';
        $post->status = 'accepted';
        $post->signup()->associate($signup);
        $post->save();

        // Create a second photo post that is pending.
        $pendingPost = factory(Post::class)->create();
        $pendingPost->signup()->associate($signup);

        // Test with annoymous user that no posts are returned when using the include params.
        $response = $this->getJson('api/v3/signups' . '?include=posts:type(text|photo)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(true, empty($decodedResponse['data'][0]['posts']['data']));

        // Test with annoymous user that the voter reg post is returned since it is accepted.
        $response = $this->getJson('api/v3/signups' . '?include=posts');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();
        $this->assertEquals(false, empty($decodedResponse['data'][0]['posts']['data']));
    }

    /**
     * Test for signup index with included pending post info. and include params as admin
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups?include=posts:type(text|photo)
     * @return void
     */
    public function testSignupIndexWithIncludedPostsAndParamsAsAdmin()
    {
        $signup = factory(Signup::class)->create();

        // Create three voter registration posts, a text post, and a photo post.
        factory(Post::class, 3)->create(['type' => 'voter-reg', 'signup_id' => $signup->id]);
        factory(Post::class)->create(['type' => 'text', 'signup_id' => $signup->id]);
        factory(Post::class)->create(['type' => 'photo', 'signup_id' => $signup->id]);

        // Test with admin that only photo and text posts are returned.
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?include=posts:type(text|photo)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();
        $this->assertEquals(2, count($decodedResponse['data'][0]['posts']['data']));

        // Test with admin that only voter-reg posts are returned.
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?include=posts:type(voter-reg)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();
        $this->assertEquals(3, count($decodedResponse['data'][0]['posts']['data']));
    }

    /**
     * Test for signup index with included pending post info. and include params as owner
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups?include=posts:type(text|photo)
     * @return void
     */
    public function testSignupIndexWithIncludedPostsAndParamsAsOwner()
    {
        $signup = factory(Signup::class)->create();

        // Create a voter reg post
        $post = factory(Post::class)->create();
        $post->type = 'voter-reg';
        $post->signup()->associate($signup);
        $post->save();

        // Test with owner that voter reg post is returned.
        $response = $this->withAccessToken($post->northstar_id)->getJson('api/v3/signups?include=posts:type(voter-reg)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(false, empty($decodedResponse['data'][0]['posts']['data']));

        // Test with owner that no posts are returned (because it does not match include params).
        $response = $this->withAccessToken($post->northstar_id)->getJson('api/v3/signups?include=posts:type(text|photo)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(true, empty($decodedResponse['data'][0]['posts']['data']));
    }

    /**
     * Test for signup show with included pending post info. as non-admin/non-owner.
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups/:signup_id?include=posts
     * @return void
     */
    public function testSignupShowWithIncludedPostsAsNonAdminNonOwner()
    {
        $post = factory(Post::class)->create();
        $signup = $post->signup;

        // Test with annoymous user that no posts are returned.
        $response = $this->getJson('api/v3/signups/' . $signup->id . '?include=posts');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(true, empty($decodedResponse['data']['posts']['data']));
    }

    /**
     * Test for signup show with included pending post info. as admin
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups/:signup_id?include=posts
     * @return void
     */
    public function testSignupShowWithIncludedPostsAsAdmin()
    {
        $post = factory(Post::class)->create();
        $signup = $post->signup;

        // Test with admin that posts are returned.
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups/' . $signup->id . '?include=posts');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(false, empty($decodedResponse['data']['posts']['data']));
        $this->assertEquals($signup->id, $decodedResponse['data']['posts']['data'][0]['signup_id']);
    }

    /**
     * Test for signup show with included pending post info. as owner
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups/:signup_id?include=posts
     * @return void
     */
    public function testSignupShowWithIncludedPostsAsOwner()
    {
        $post = factory(Post::class)->create();
        $signup = $post->signup;

        // Test with admin that posts are returned.
        $response = $this->withAccessToken($post->northstar_id)->getJson('api/v3/signups/' . $signup->id . '?include=posts');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(false, empty($decodedResponse['data']['posts']['data']));
        $this->assertEquals($signup->id, $decodedResponse['data']['posts']['data'][0]['signup_id']);
    }

    /**
     * Test for signup show with included post info. and include params as non-admin/non-owner.
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups/:signup?include=posts:type(text|photo)
     * @return void
     */
    public function testSignupShowWithIncludedPostsAndParamsAsNonAdminNonOwner()
    {
        $signup = factory(Signup::class)->create();

        // Create a voter-reg post that is accepted.
        $post = factory(Post::class)->create();
        $post->type = 'voter-reg';
        $post->status = 'accepted';
        $post->signup()->associate($signup);
        $post->save();

        // Create a second photo post that is pending.
        $pendingPost = factory(Post::class)->create();
        $pendingPost->signup()->associate($signup);

        // Test with annoymous user that no posts are returned when using the include params.
        $response = $this->getJson('api/v3/signups/' . $signup->id . '?include=posts:type(text|photo)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(true, empty($decodedResponse['data']['posts']['data']));

        // Test with annoymous user that the voter reg post is returned since it is accepted.
        $response = $this->getJson('api/v3/signups/' . $signup->id . '?include=posts');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(false, empty($decodedResponse['data']['posts']['data']));
    }

    /**
     * Test for signup show with included pending post info. and include params as admin
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups/:signup?include=posts:type(text|photo)
     * @return void
     */
    public function testSignupShowWithIncludedPostsAndParamsAsAdmin()
    {
        $signup = factory(Signup::class)->create();

        // Create three voter registration posts, a text post, and a photo post.
        factory(Post::class, 3)->create(['type' => 'voter-reg', 'signup_id' => $signup->id]);
        factory(Post::class)->create(['type' => 'text', 'signup_id' => $signup->id]);
        factory(Post::class)->create(['type' => 'photo', 'signup_id' => $signup->id]);

        // Test with admin that only photo and text posts are returned.
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups/' . $signup->id . '?include=posts:type(text|photo)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();
        $this->assertEquals(2, count($decodedResponse['data']['posts']['data']));

        // Test with admin that only voter-reg posts are returned.
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups/' . $signup->id . '?include=posts:type(voter-reg)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();
        $this->assertEquals(3, count($decodedResponse['data']['posts']['data']));
    }

    /**
     * Test for signup show with included pending post info. and include params as owner
     * Only admins/owners should be able to see pending/rejected posts.
     *
     * GET /api/v3/signups/:signup?include=posts:type(text|photo)
     * @return void
     */
    public function testSignupShowWithIncludedPostsAndParamsAsOwner()
    {
        $signup = factory(Signup::class)->create();

        // Create a voter reg post
        $post = factory(Post::class)->create();
        $post->type = 'voter-reg';
        $post->signup()->associate($signup);
        $post->save();

        // Test with owner that voter reg post is returned.
        $response = $this->withAccessToken($post->northstar_id)->getJson('api/v3/signups/' . $signup->id . '?include=posts:type(voter-reg)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(false, empty($decodedResponse['data']['posts']['data']));

        // Test with owner that no posts are returned (because it does not match include params).
        $response = $this->withAccessToken($post->northstar_id)->getJson('api/v3/signups/' . $signup->id . '?include=posts:type(text|photo)');
        $response->assertStatus(200);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(true, empty($decodedResponse['data']['posts']['data']));
    }

    /**
     * Test for signup index with included user info. as admin.
     * Only admins/owners should be able to see all user info.
     *
     * GET /api/v3/signups?include=user
     * @return void
     */
    public function testSignupIndexWithIncludedUser()
    {
        $post = factory(Post::class)->create();
        $signup = $post->signup;

        // Test with admin that entire user is returned.
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?include=user');
        $response->assertStatus(200);

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(false, empty($decodedResponse['data'][0]['user']['data']['birthdate']));
    }

    /**
     * Test for retrieving all signups as admin with northstar_id & campaign_id filters (and a combinations of all).
     *
     * GET /api/v3/signups?filter[northstar_id]=56d5baa7a59dbf106b8b45aa
     * GET /api/v3/signups?filter[campaign_id]=1
     * GET /api/v3/signups?filter[campaign_id]=1&filter[northstar_id]=56d5baa7a59dbf106b8b45aa
     * GET /api/v3/signups?filter[campaign_id]=1,2
     *
     * @return void
     */
    public function testSignupsIndexAsAdminWithFilters()
    {
        $northstarId = $this->faker->unique()->northstar_id;
        $campaignId = str_random(22);

        // Create two signups
        factory(Signup::class, 2)->create([
            'northstar_id' => $northstarId,
            'campaign_id' => $campaignId,
        ]);

        // Create three more signups with different northstar_id & campaign_id
        $secondNorthstarId = $this->faker->unique()->northstar_id;
        $secondCampaignId = str_random(22);

        factory(Signup::class, 3)->create([
            'northstar_id' => $secondNorthstarId,
            'campaign_id' => $secondCampaignId,
        ]);

        // Filter by northstar_id
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?filter[northstar_id]=' . $northstarId);
        $decodedResponse = $response->decodeResponseJson();

        $response->assertStatus(200);

        // Assert only 2 signups are returned
        $this->assertEquals(2, $decodedResponse['meta']['cursor']['count']);

        // Filter by campaign_id
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?filter[campaign_id]=' . $secondCampaignId);
        $decodedResponse = $response->decodeResponseJson();

        $response->assertStatus(200);

        // Assert only 3 signups are returned
        $this->assertEquals(3, $decodedResponse['meta']['cursor']['count']);

        // Filter by campaign_id and northstar_id
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?filter[campaign_id]=' . $campaignId . '&filter[northstar_id]=' . $northstarId);
        $decodedResponse = $response->decodeResponseJson();

        $response->assertStatus(200);

        // Assert only 2 signups are returned
        $this->assertEquals(2, $decodedResponse['meta']['cursor']['count']);

        // Filter by multiple campaign_id
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?filter[campaign_id]=' . $campaignId . ',' . $secondCampaignId);
        $decodedResponse = $response->decodeResponseJson();

        $response->assertStatus(200);

        // Assert all signups are returned
        $this->assertEquals(5, $decodedResponse['meta']['cursor']['count']);
    }

    /**
     * Test for retrieving a specific signup as non-admin and non-owner.
     *
     * GET /api/v3/signups/:signup_id
     * @return void
     */
    public function testSignupShowAsNonAdminNonOwner()
    {
        $signup = factory(Signup::class)->create();
        $response = $this->getJson('api/v3/signups/' . $signup->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'northstar_id',
                'campaign_id',
                'quantity',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    /**
     * Test for retrieving all signups & ordering them.
     *
     * GET /api/v3/signups?orderBy=quantity,desc
     * GET /api/v3/signups?orderBy=quantity,asc
     * @return void
     */
    public function testSignupsIndexAsAdminWithOrderBy()
    {
        $signup = factory(Signup::class)->create(); // quantity=6
        factory(Post::class, 3)->create(['signup_id' => $signup->id, 'quantity' => 2]);

        $signup2 = factory(Signup::class)->create(); // quantity=12
        factory(Post::class, 4)->create(['signup_id' => $signup2->id, 'quantity' => 3]);

        // Order results by descending quantity
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?orderBy=quantity,desc');

        $response->assertSuccessful();
        $this->seeJsonField($response, 'data.0.quantity', 12);
        $this->seeJsonField($response, 'data.1.quantity', 6);

        // Order results by ascending quantity
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups?orderBy=quantity,asc');

        $response->assertSuccessful();
        $this->seeJsonField($response, 'data.0.quantity', 6);
        $this->seeJsonField($response, 'data.1.quantity', 12);
    }

    /**
     * Test for retrieving a specific signup as admin.
     *
     * GET /api/v3/signups/:signup_id
     * @return void
     */
    public function testSignupShowAsAdmin()
    {
        $signup = factory(Signup::class)->create();
        $response = $this->withAdminAccessToken()->getJson('api/v3/signups/' . $signup->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'northstar_id',
                'campaign_id',
                'quantity',
                'created_at',
                'updated_at',
                'why_participated',
                'source',
                'details',
            ],
        ]);
    }

    /**
     * Test for retrieving a specific signup as owner.
     *
     * GET /api/v3/signups/:signup_id
     * @return void
     */
    public function testSignupShowAsOwner()
    {
        $signup = factory(Signup::class)->create();
        $response = $this->withAccessToken($signup->northstar_id)->getJson('api/v3/signups/' . $signup->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'northstar_id',
                'campaign_id',
                'quantity',
                'created_at',
                'updated_at',
                'why_participated',
                'source',
                'details',
            ],
        ]);
    }

    /**
     * Test that a signup gets deleted when hitting the DELETE endpoint.
     *
     * @return void
     */
    public function testDeletingASignup()
    {
        $signup = factory(Signup::class)->create();

        // Mock time of when the signup is soft deleted.
        $this->mockTime('8/3/2017 14:00:00');

        $response = $this->withAdminAccessToken()->deleteJson('api/v3/signups/' . $signup->id);

        $response->assertStatus(200);

        // Make sure that the signup's deleted_at gets persisted in the database.
        $this->assertEquals($signup->fresh()->deleted_at->toTimeString(), '14:00:00');
    }

    /**
     * Test that a signup cannot be deleted without activity scope
     *
     * @return void
     */
    public function testDeletingASignupWithoutActivityScope()
    {
        $signup = factory(Signup::class)->create();

        $response = $this->deleteJson('api/v3/signups/' . $signup->id);

        $response->assertStatus(401);
        $this->assertEquals($response->decodeResponseJson()['message'], 'Unauthenticated.');
    }

    /**
     * Test that non-authenticated user's/apps can't delete signups.
     *
     * @return void
     */
    public function testUnauthenticatedUserDeletingASignup()
    {
        $signup = factory(Signup::class)->create();

        $response = $this->deleteJson('api/v3/signups/' . $signup->id);

        $response->assertStatus(401);
    }

    /**
     * Test for updating a signup successfully.
     *
     * PATCH /api/v3/signups/186
     * @return void
     */
    public function testUpdatingASignup()
    {
        $signup = factory(Signup::class)->create();

        $response = $this->withAdminAccessToken()->patchJson('api/v3/signups/' . $signup->id, [
            'why_participated'  => 'new why participated',
        ]);

        $response->assertStatus(200);

        // Make sure that the signup's new why_participated gets persisted in the database.
        $this->assertEquals($signup->fresh()->why_participated, 'new why participated');
    }

    /**
     * Test that a signup cannot be updated without the activity scope.
     *
     * PATCH /api/v3/signups/186
     * @return void
     */
    public function testUpdatingASignupWithoutActivityScope()
    {
        $signup = factory(Signup::class)->create();

        $response = $this->patchJson('api/v3/signups/' . $signup->id, [
            'why_participated'  => 'new why participated',
        ]);

        $response->assertStatus(401);
        $this->assertEquals('Unauthenticated.', $response->decodeResponseJson()['message']);
    }

    /**
     * Test valudation for updating a signup.
     *
     * PATCH /api/v3/signups/186
     * @return void
     */
    public function testValidationForUpdatingASignup()
    {
        $signup = factory(Signup::class)->create();

        $response = $this->withAdminAccessToken()->patchJson('api/v3/signups/' . $signup->id);

        $response->assertStatus(422);
    }

    /**
     * Test that a non-admin or user that doesn't own the signup can't update signup.
     *
     * @return void
     */
    public function testUnauthenticatedUserUpdatingASignup()
    {
        $user = factory(User::class)->create();
        $signup = factory(Signup::class)->create();

        $response = $this->withAccessToken($user->id)->patchJson('api/v3/signups/' . $signup->id, [
            'why_participated' => 'new why participated',
        ]);

        $response->assertStatus(403);

        $json = $response->json();

        $this->assertEquals('You don\'t have the correct role to update this signup!', $json['message']);
    }
}
