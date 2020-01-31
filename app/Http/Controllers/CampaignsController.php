<?php

namespace Rogue\Http\Controllers;

use Rogue\Models\Campaign;
use Illuminate\Http\Request;
use Rogue\Http\Requests\CampaignRequest;
use Rogue\Http\Transformers\CampaignTransformer;

class CampaignsController extends ApiController
{
    /**
     * @var Rogue\Http\Transformers\CampaignTransformer;
     */
    protected $transformer;

    /**
     * Create a controller instance.
     */
    public function __construct()
    {
        $this->transformer = new CampaignTransformer;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = $this->newQuery(Campaign::class);

        $filters = $request->query('filter');
        $query = $this->filter($query, $filters, Campaign::$indexes);

        // Apply scope for the "computed" is_open field:
        if (isset($filters['is_open'])) {
            // We can use this to find only open campaigns with a truthy filter
            // applied, e.g. `?filter[is_open]=true`, or closed campaigns with
            // a falsy filter, e.g. `?filter[is_open]=false`.
            if (filter_var($filters['is_open'], FILTER_VALIDATE_BOOLEAN)) {
                $query->whereOpen();
            } else {
                $query->whereClosed();
            }
        }

        // Experimental: Allow paginating by cursor (e.g. `?cursor[after]=OTAxNg==`):
        if ($cursor = array_get($request->query('cursor'), 'after')) {
            $query->whereAfterCursor($cursor);

            // Using 'cursor' implies cursor pagination:
            $this->useCursorPagination = true;
        }

        // Allow ordering results:
        $orderBy = $request->query('orderBy');
        $query = $this->orderBy($query, $orderBy, Campaign::$sortable);

        return $this->paginatedCollection($query, $request);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Rogue\Models\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function show(Campaign $campaign, Request $request)
    {
        return $this->item($campaign);
    }

    /**
     * Updates a specific campaign
     * PATCH /api/campaigns/:id
     *
     * @param CampaignRequest $request
     * @param  \Rogue\Models\Campaign  $campaign
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CampaignRequest $request, Campaign $campaign)
    {
        // Only allow an admin/staff to update.
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        return $this->item($campaign);
    }
}
