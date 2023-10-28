<?php

namespace App\Http\Requests;

use App\Models\Rank;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreRankRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('rank_create');
    }

    public function rules()
    {
        return [
            'department_id' => [
                'required',
                'integer',
            ],
            'rank' => [
                'string',
                'required',
            ],
        ];
    }
}
