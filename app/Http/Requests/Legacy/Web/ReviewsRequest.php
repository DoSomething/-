<?php

namespace Rogue\Http\Requests\Legacy\Web;

use Rogue\Http\Requests\Request;

class ReviewsRequest extends Request
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
            'post_id' => 'required',
            'status' => 'in:pending,accepted,rejected',
        ];
    }
}