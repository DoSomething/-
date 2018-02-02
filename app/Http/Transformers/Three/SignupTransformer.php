<?php

namespace Rogue\Http\Transformers\Three;

use Rogue\Models\Signup;
use League\Fractal\TransformerAbstract;
use Rogue\Http\Transformers\PostTransformer;

class SignupTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'posts',
    ];

    /**
     * Transform resource data.
     *
     * @param \Rogue\Models\Signup $signup
     * @return array
     */
    public function transform(Signup $signup)
    {
        $response = [
            'id' => $signup->id,
            'northstar_id' => $signup->northstar_id,
            'campaign_id' => $signup->campaign_id,
            'campaign_run_id' => $signup->campaign_run_id,
            'quantity' => $signup->getQuantity(),
            'created_at' => $signup->created_at->toIso8601String(),
            'updated_at' => $signup->updated_at->toIso8601String(),
        ];

        if (is_staff_user() || auth()->id() === $signup->northstar_id) {
            $response['why_participated'] = $signup->why_participated;
            $response['source'] = $signup->source;
            $response['details'] = $signup->details;
        }

        return $response;
    }

    /**
     * Include posts
     *
     * @param \Rogue\Models\Signup $signup
     * @return \League\Fractal\Resource\Collection
     */
    public function includePosts(Signup $signup)
    {
        $post = $signup->posts;

        return $this->collection($post, new PostTransformer);
    }
}
