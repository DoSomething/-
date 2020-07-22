<?php

namespace Rogue\Http\Requests\Legacy\Two;

use Rogue\Http\Requests\Request;

class SignupRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'northstar_id' => 'required|string',
            'referrer_user_id' => 'nullable|objectid',
            'campaign_id' => 'required|integer',
            'quantity' => 'int',
            'why_participated' => 'string',
            'source' => 'string|nullable',
        ];
    }
}
