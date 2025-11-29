<?php

namespace App\Http\Requests\Rider;

use Illuminate\Foundation\Http\FormRequest;

class IngestLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'points' => 'required|array|max:50',
            'points.*.lat' => 'required|numeric|between:-90,90',
            'points.*.lng' => 'required|numeric|between:-180,180',
            'points.*.ts' => 'nullable|date',
            'points.*.accuracy' => 'nullable|numeric',
            'points.*.speed' => 'nullable|numeric',
            'points.*.battery' => 'nullable|integer|between:0,100',
        ];
    }
}
