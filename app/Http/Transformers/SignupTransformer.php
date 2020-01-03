<?php

namespace Rogue\Http\Transformers;

use Gate;
use Rogue\Models\Signup;
use Rogue\Services\Registrar;
use League\Fractal\TransformerAbstract;

class SignupTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'posts', 'user', 'accepted_quantity',
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
            'quantity' => $signup->quantity,
            'created_at' => $signup->created_at->toIso8601String(),
            'updated_at' => $signup->updated_at->toIso8601String(),
            'cursor' => $signup->getCursor(),
        ];

        if (Gate::allows('viewAll', $signup)) {
            $response['why_participated'] = $signup->why_participated;
            $response['source'] = $signup->source;
            $response['source_details'] = $signup->source_details;
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
        // Only allow an admin or the user who owns the signup to see the signup's unapproved posts.
        $post = $signup->visiblePosts;

        return $this->collection($post, new PostTransformer);
    }

    /**
     * Include the user data (optionally)
     *
     * @param \Rogue\Models\Signup $signup
     * @return \League\Fractal\Resource\Item
     */
    public function includeUser(Signup $signup)
    {
        $registrar = app(Registrar::class);
        $northstar_id = $signup->northstar_id;

        return $this->item($registrar->find($northstar_id), new UserTransformer);
    }

    /**
     * Include accepted quantity
     *
     * @param \Rogue\Models\Signup $signup
     * @return \League\Fractal\Resource\Collection
     */
    public function includeAcceptedQuantity(Signup $signup)
    {
        return $this->item($signup->getAcceptedQuantity(), new AcceptedQuantityTransformer);
    }
}
