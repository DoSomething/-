<?php

namespace Rogue\Services;

use Rogue\Jobs\SendSignupToCustomerIo;
use Rogue\Jobs\SendSignupToQuasar;
use Rogue\Repositories\SignupRepository;

class SignupService
{
    /*
     * SignupRepository Instance
     *
     * @var Rogue\Repositories\SignupRepository;
     */
    protected $signup;

    /**
     * Constructor
     *
     * @param SignupRepository $signup
     * @param Blink $blink
     */
    public function __construct(SignupRepository $signup)
    {
        $this->signup = $signup;
    }

    /*
     * Handles all business logic around creating signups.
     *
     * @param array $data
     * @return Illuminate\Database\Eloquent\Model $model
     */
    public function create($data)
    {
        $signup = $this->signup->create($data);

        // Send to Blink unless 'dont_send_to_blink' is TRUE
        $should_send_to_blink = ! (array_key_exists('dont_send_to_blink', $data) && $data['dont_send_to_blink']);

        // Save the new signup in Customer.io, via Blink.
        if (config('features.blink') && $should_send_to_blink) {
            SendSignupToCustomerIo::dispatch($signup);
        }

        // Dispatch job to send signup to Quasar
        SendSignupToQuasar::dispatch($signup);

        // Log that a signup was created.
        info('signup_created', ['id' => $signup->id, 'northstar_id' => $signup->northstar_id]);

        return $signup;
    }

    /*
     * Handles all business logic around retrieving a signup.
     *
     * @param  string $northstarId
     * @param  int $campaignId
     * @param  int $campaignRunId
     * @return \Rogue\Models\Signup|null
     */
    public function get($northstarId, $campaignId, $campaignRunId)
    {
        $signup = $this->signup->get($northstarId, $campaignId, $campaignRunId);

        return $signup;
    }
}
