<?php

namespace Rogue\Managers;

use Rogue\Jobs\CreateCustomerIoEvent;
use Rogue\Jobs\SendSignupToCustomerIo;
use Rogue\Repositories\SignupRepository;

class SignupManager
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

    /**
     * Handles all business logic around creating signups.
     *
     * @param array $data
     * @param string $northstarId
     * @param int $campaignId
     * @return Illuminate\Database\Eloquent\Model $model
     */
    public function create($data, $northstarId, $campaignId)
    {
        $signup = $this->signup->create($data, $northstarId, $campaignId);

        // Send to Blink unless 'dont_send_to_blink' is TRUE
        $shouldSendToCustomerIo = !(
            array_key_exists('dont_send_to_blink', $data) &&
            $data['dont_send_to_blink']
        );

        // Save the new signup in Customer.io, via Blink.
        if (config('features.blink') && $shouldSendToCustomerIo) {
            SendSignupToCustomerIo::dispatch($signup);
        }

        if ($signup->referrer_user_id && $shouldSendToCustomerIo) {
            CreateCustomerIoEvent::dispatch(
                $signup->referrer_user_id,
                'referral_signup_created',
                $signup->getReferralSignupEventPayload(),
            );
        }

        // Log that a signup was created.
        info('signup_created', [
            'id' => $signup->id,
            'signup_created_source' => $signup->source,
        ]);

        return $signup;
    }

    /**
     * Handles all business logic around updating signups.
     *
     * @param Rogue\Models\Signup $signup
     * @param array $data
     * @param bool $log
     * @return Rogue\Models\Signup $model
     */
    public function update($signup, $data, $log = true)
    {
        $signup = $this->signup->update($signup, $data);

        if ($log) {
            // Log that a signup was updated.
            info('signup_updated', ['id' => $signup->id]);
        }

        return $signup;
    }

    /**
     * Handle all business logic around deleting a signup.
     *
     * @param int $signupId
     * @return bool
     */
    public function destroy($signupId)
    {
        $trashed = $this->signup->destroy($signupId);

        if ($trashed) {
            info('signup_deleted', [
                'id' => $signupId,
            ]);
        }

        return $trashed;
    }

    /*
     * Handles all business logic around retrieving a signup.
     *
     * @param  string $northstarId
     * @param  int $campaignId
     * @return \Rogue\Models\Signup|null
     */
    public function get($northstarId, $campaignId)
    {
        $signup = $this->signup->get($northstarId, $campaignId);

        return $signup;
    }
}
