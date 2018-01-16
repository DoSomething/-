<?php

namespace Rogue\Http\Controllers;

use Rogue\Services\Fastly;
use Rogue\Services\PostService;
use Rogue\Repositories\SignupRepository;
use Rogue\Http\Transformers\PostTransformer;
use Rogue\Http\Controllers\Traits\PostRequests;

class PostController extends Controller
{
    use PostRequests;

    /**
     * The Fastly service instance
     *
     * @var Rogue\Services\Fastly
     */
    protected $fastly;

    /**
     * The post service instance.
     *
     * @var Rogue\Services\PostService
     */
    protected $posts;

    /**
     * The signup repository instance.
     *
     * @var Rogue\Repositories\SignupRepository
     */
    protected $signups;

    /**
     * @var \League\Fractal\TransformerAbstract;
     */
    protected $transformer;

    /**
     * Create a controller instance.
     *
     * @param  PostContract  $posts
     * @return void
     */
    public function __construct(PostService $posts, SignupRepository $signups, Fastly $fastly)
    {
        $this->middleware('auth');
        $this->middleware('role:admin,staff');

        $this->fastly = $fastly;
        $this->posts = $posts;
        $this->signups = $signups;

        $this->transformer = new PostTransformer;
    }

    /**
     * Delete a resource in storage
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($postId)
    {
        $postDeleted = $this->posts->destroy($postId);

        if ($postDeleted) {
            $purgeResponse = $this->fastly->purgeKey('post-'.$postId);

            // Log the fastly response
            info('image_cache_purged', ['fastly_response' => $purgeResponse]);

            return response()->json(['code' => 200, 'message' => 'Post deleted.']);
        } else {
            return response()->json(['code' => 500, 'message' => 'There was an error deleting the post']);
        }
    }
}
